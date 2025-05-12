<?php
declare(strict_types=1);

namespace Remp\Mailer\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Request;
use Nette\Security\User;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Crm;
use Remp\MailerModule\Models\Segment\Mailer;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class RespektArticleParserTemplateFormFactory
{
    private const FORM_ACTION_WITH_JOBS_CREATED = 'generate_emails_jobs_created';
    private const FORM_ACTION_WITH_JOBS_STARTED = 'generate_emails_jobs';

    private string $layoutCode;

    private string $activeUsersSegmentCode;

    private string $inactiveUsersSegmentCode;

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
        private readonly User $user,
        private readonly Request $request,
    ) {
    }

    public function setLayoutCode(string $layoutCode): void
    {
        $this->layoutCode = $layoutCode;
    }

    public function setActiveUserSegmentCode(string $activeUsersSegmentCode): void
    {
        $this->activeUsersSegmentCode = $activeUsersSegmentCode;
    }

    public function setInactiveUserSegmentCode(string $inactiveUsersSegmentCode): void
    {
        $this->inactiveUsersSegmentCode = $inactiveUsersSegmentCode;
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
        if (!$this->activeUsersSegmentCode) {
            $form->addError("Default value 'active users segment code' is missing.");
        }
        if (!$this->inactiveUsersSegmentCode) {
            $form->addError("Default value 'inactive users segment code' is missing.");
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

        $form->addText('start_at', 'Start date')
            ->setNullable();

        $form->addHidden('mail_layout_id');
        $form->addHidden('locked_mail_layout_id');
        $form->addHidden('source_template_id');
        $form->addHidden('html_content');
        $form->addHidden('text_content');
        $form->addHidden('locked_html_content');
        $form->addHidden('locked_text_content');

        $sourceTemplate = $this->sourceTemplatesRepository->find($this->request->getPost('source_template_id'));
        $mailType = $this->mailTypesRepository->findBy('code', $this->defaultMailTypeCode);

        $defaults = [
            'source_template_id' => $sourceTemplate->id,
            'name' => "{$sourceTemplate->title} " . date('d. m. Y'),
            'code' => "{$sourceTemplate->code}_" . date('Y-m-d'),
            'mail_type_id' => $mailType->id,
            'from' => $mailType->mail_from,
        ];

        if ($this->layoutCode) {
            $mailLayout = $this->layoutsRepository->findBy('code', $this->layoutCode);
            $defaults['mail_layout_id'] = (int)$mailLayout->id;
            $defaults['locked_mail_layout_id'] = (int)$mailLayout->id;
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
        $generate = function ($htmlBody, $textBody, $mailLayoutId, $segmentCode) use ($values, $form) {
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

            $jobContext = null;
            $mailJob = $this->jobsRepository->add((new JobSegmentsManager())
                ->includeSegment($segmentCode, Crm::PROVIDER_ALIAS), $jobContext);

            $batch = $this->batchesRepository->add(
                $mailJob->id,
                null,
                $values['start_at'],
                BatchesRepository::METHOD_RANDOM
            );
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
            $this->activeUsersSegmentCode,
        );

        $generate(
            $values['locked_html_content'],
            $values['locked_text_content'],
            $values['mail_layout_id'],
            $this->inactiveUsersSegmentCode,
        );

        $this->onSave->__invoke();
    }
}
