<?php
declare(strict_types=1);

namespace Remp\Mailer\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Security\User;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Mailer;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class RespektUrlParserTemplateFormFactory
{
    private const FORM_ACTION_WITH_JOBS_CREATED = 'generate_emails_jobs_created';
    private const FORM_ACTION_WITH_JOBS_STARTED = 'generate_emails_jobs';

    private string $layoutCode;

    private string $segmentCode;

    private string $segmentProvider = Mailer::PROVIDER_ALIAS;

    private string $defaultMailTypeCode;

    public $onUpdate;

    public $onSave;

    public function __construct(
        private readonly TemplatesRepository $templatesRepository,
        private readonly LayoutsRepository $layoutsRepository,
        private readonly ListsRepository $listsRepository,
        private readonly JobsRepository $jobsRepository,
        private readonly BatchesRepository $batchesRepository,
        private readonly SourceTemplatesRepository $sourceTemplatesRepository,
        private readonly PermissionManager $permissionManager,
        private readonly MailTypesRepository $mailTypesRepository,
        private readonly User $user
    ) {
    }

    public function setLayoutCode(string $layoutCode): void
    {
        $this->layoutCode = $layoutCode;
    }

    public function setSegmentCode(string $segmentCode, string $segmentProvider = Mailer::PROVIDER_ALIAS): void
    {
        $this->segmentCode = $segmentCode;
        $this->segmentProvider = $segmentProvider;
    }

    public function setDefaultMailTypeCode(string $defaultMailTypeCode): void
    {
        $this->defaultMailTypeCode = $defaultMailTypeCode;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        if (!$this->layoutCode) {
            $form->addError("Default value 'layout code' is missing.");
        }

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.");

        $form->addText('code', 'Identifier')
            ->setRequired("Field 'Identifier' is required.");

        $mailTypes = $this->listsRepository->all()
            ->where(['public_listing' => true])
            ->fetchPairs('id', 'title');

        $form->addSelect('mail_type_id', 'Type', $mailTypes)
            ->setPrompt('Select type')
            ->setRequired("Field 'Type' is required.");

        $form->addText('from', 'Sender')
            ->setHtmlAttribute('placeholder', 'e.g. info@domain.com')
            ->setRequired("Field 'Sender' is required.");

        $form->addText('subject', 'Subject')
            ->setRequired("Field 'Subject' is required.");

        $form->addText('subject_b', 'Subject (B version)')
            ->setNullable();

        $form->addText('email_count', 'Batch size')
            ->setNullable();

        $form->addText('start_at', 'Start date')
            ->setNullable();

        $form->addHidden('mail_layout_id');
        $form->addHidden('source_template_id');
        $form->addHidden('html_content');
        $form->addHidden('text_content');

        $sourceTemplate = $this->sourceTemplatesRepository->find($_POST['source_template_id']);
        $mailType = $this->mailTypesRepository->findBy('code', $this->defaultMailTypeCode);

        $defaults = [
            'source_template_id' => $sourceTemplate->id,
            'name' => "{$sourceTemplate->title} " . date('d. m. Y'),
            'code' => "{$sourceTemplate->code}_" . date('Y-m-d'),
            'mail_type_id' => $mailType->id,
            'from' => $mailType->mail_from,
        ];

        if ($this->layoutCode) {
            $defaults['mail_layout_id'] = (int)$this->layoutsRepository->findBy('code', $this->layoutCode)->id;
        }

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

    public function formSucceeded(Form $form, $values)
    {
        $generate = function ($htmlBody, $textBody, $mailLayoutId) use ($values, $form) {
            $mailTemplate = $this->templatesRepository->add(
                $values['name'],
                $this->templatesRepository->getUniqueTemplateCode($values['code']),
                '',
                $values['from'],
                $values['subject'],
                $textBody,
                $htmlBody,
                (int) $mailLayoutId,
                $values['mail_type_id']
            );

            if (isset($this->segmentCode)) {
                $segmentCode = $this->segmentCode;
                $segmentProvider = $this->segmentProvider;
            } else {
                $segmentCode = Mailer::mailTypeSegment($mailTemplate->mail_type->code);
                $segmentProvider = Mailer::PROVIDER_ALIAS;
            }

            $jobContext = null;

            $mailJob = $this->jobsRepository->add((new JobSegmentsManager())->includeSegment($segmentCode, $segmentProvider), $jobContext);
            $batch = $this->batchesRepository->add(
                $mailJob->id,
                (int)$values['email_count'],
                $values['start_at'],
                BatchesRepository::METHOD_RANDOM
            );
            $this->batchesRepository->addTemplate($batch, $mailTemplate);

            if (isset($values['subject_b'])) {
                $mailTemplateB = $this->templatesRepository->add(
                    $values['name'],
                    $this->templatesRepository->getUniqueTemplateCode($values['code']),
                    '',
                    $values['from'],
                    $values['subject_b'],
                    $textBody,
                    $htmlBody,
                    (int)$mailLayoutId,
                    $values['mail_type_id']
                );
                $this->batchesRepository->addTemplate($batch, $mailTemplateB);
            }

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
        );

        $this->onSave->__invoke();
    }
}
