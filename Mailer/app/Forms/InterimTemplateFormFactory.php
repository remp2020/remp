<?php
declare(strict_types=1);

namespace Remp\Mailer\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Request;
use Nette\Security\User;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Mailer;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class InterimTemplateFormFactory
{
    private const FORM_ACTION_WITH_JOBS_CREATED = 'generate_emails_jobs_created';
    private const FORM_ACTION_WITH_JOBS_STARTED = 'generate_emails_jobs';

    public $onUpdate;

    public $onSave;

    public function __construct(
        private TemplatesRepository $templatesRepository,
        private LayoutsRepository $layoutsRepository,
        private ListsRepository $listsRepository,
        private JobsRepository $jobsRepository,
        private BatchesRepository $batchesRepository,
        private PermissionManager $permissionManager,
        private User $user,
        private Request $request,
        private SourceTemplatesRepository $sourceTemplatesRepository,
    ) {
    }

    public function overrideSegment(string $segment): void
    {
        $this->segment = $segment;
    }

    public function create(): Form
    {
        $form = new Form;
        $form->addProtection();

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.");

        $form->addText('code', 'Identifier')
            ->setRequired("Field 'Identifier' is required.");

        $form->addSelect('mail_layout_id', 'Template', $this->layoutsRepository->all()->fetchPairs('id', 'name'));

        $mailTypes = $this->listsRepository->all()->where(['public_listing' => true])->fetchPairs('id', 'title');

        $form->addSelect('mail_type_id', 'Type', $mailTypes)
            ->setRequired("Field 'Type' is required.");

        $form->addText('from', 'Sender')
            ->setHtmlAttribute('placeholder', 'e.g. info@domain.com')
            ->setRequired("Field 'Sender' is required.");

        $form->addText('subject', 'Subject')
            ->setRequired("Field 'Subject' is required.");

        $form->addText('send_at', 'Send at')->setNullable();

        $form->addHidden('html_content');
        $form->addHidden('text_content');

        $sourceTemplate = $this->sourceTemplatesRepository->find((int) $this->request->getPost('source_template_id'));
        $form->addHidden('source_template_id', $sourceTemplate->id);

        $defaults = array_filter($this->getDefaults($sourceTemplate->code));
        $form->setDefaults($defaults);

        if ($this->permissionManager->isAllowed($this->user, 'batch', 'start')) {
            $withJobs = $form->addSubmit(self::FORM_ACTION_WITH_JOBS_STARTED);
            $withJobs->getControlPrototype()
                ->setName('button')
                ->setHtml('Generate newsletter batch and start sending');
        }

        $withJobsCreated = $form->addSubmit(self::FORM_ACTION_WITH_JOBS_CREATED);
        $withJobsCreated->getControlPrototype()
            ->setName('button')
            ->setHtml('Generate newsletter batch');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, $values): void
    {
        $generate = function ($htmlBody, $textBody, $mailLayoutId, $segmentCode = null) use ($values, $form) {
            $mailTemplate = $this->templatesRepository->add(
                $values['name'],
                $this->templatesRepository->getUniqueTemplateCode($values['code']),
                '',
                $values['from'],
                $values['subject'],
                $textBody,
                $htmlBody,
                $mailLayoutId,
                $values['mail_type_id']
            );

            if (!$segmentCode) {
                $segmentCode = Mailer::mailTypeSegment($mailTemplate->mail_type->code);
            }
            $jobContext = $mailTemplate->mail_type->code . '.' . date('Ymd');

            $mailJob = $this->jobsRepository->add((new JobSegmentsManager())->includeSegment($segmentCode, Mailer::PROVIDER_ALIAS), $jobContext);
            $batch = $this->batchesRepository->add($mailJob->id, null, $values['send_at'], BatchesRepository::METHOD_RANDOM);
            $this->batchesRepository->addTemplate($batch, $mailTemplate);

            $batchStatus = BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND;

            /** @var SubmitButton $withJobsCreatedSubmit */
            $withJobsCreatedSubmit = $form[self::FORM_ACTION_WITH_JOBS_CREATED];
            if ($withJobsCreatedSubmit->isSubmittedBy()) {
                $batchStatus = BatchesRepository::STATUS_CREATED;
            }

            $this->batchesRepository->updateStatus($batch, $batchStatus);
        };

        $generate(
            $values['html_content'],
            $values['text_content'],
            $values['mail_layout_id'],
            $values['segment_code'] ?? null,
        );

        $this->onSave->__invoke();
    }

    private function getDefaults(string $sourceTemplateCode): array
    {
        $tomorrowMorning = new \DateTime('tomorrow 7:30AM');
        $now = new \DateTime();

        $defaults =  match ($sourceTemplateCode) {
            'euobserver_daily' => [
                'name' => 'Daily minute ' . $tomorrowMorning->format('j.n.Y'),
                'code' => 'daily_minute_' . $tomorrowMorning->format('dmy'),
                'mail_layout_code' => 'empty-layout',
                'mail_type_code' => 'euobserver-daily',
                'from' => 'EUobserver daily news <noreply@euobserver.com>',
                'send_at' => $tomorrowMorning->format('m/d/Y H:i A'),
            ],
            'friday_five' => [
                'name' => 'Friday Five ' . $now->format('j.n.Y'),
                'code' => 'friday_five_' . $now->format('dmy'),
                'mail_layout_code' => 'default',
                'mail_type_code' => 'the-friday-five',
                'from' => 'The Friday Five <noreply@euobserver.com>',
            ]
        };

        $mailLayout = $this->layoutsRepository->findBy('code', $defaults['mail_layout_code']);
        if (!$mailLayout) {
            throw new \Exception("No mail layout found with code: '{$defaults['mail_layout_code']}'");
        }
        $defaults['mail_layout_id'] = $mailLayout->id;

        $mailType = $this->listsRepository->findByCode($defaults['mail_type_code'])->fetch();
        if (!$mailType) {
            throw new \Exception("No mail type found with code: '{$defaults['mail_type_code']}'");
        }
        $defaults['mail_type_id'] = $mailType->id;

        return $defaults;
    }
}
