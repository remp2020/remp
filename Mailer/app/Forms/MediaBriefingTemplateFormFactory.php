<?php
declare(strict_types=1);

namespace Remp\Mailer\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Crm;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class MediaBriefingTemplateFormFactory
{
    private const FORM_ACTION_WITH_JOBS_CREATED = 'generate_emails_jobs_created';
    private const FORM_ACTION_WITH_JOBS_STARTED = 'generate_emails_jobs';

    private $activeUsersSegment;

    private $inactiveUsersSegment;

    private $templatesRepository;

    private $layoutsRepository;

    private $jobsRepository;

    private $batchesRepository;

    private $listsRepository;

    private $permissionManager;

    private $user;

    public $onUpdate;

    public $onSave;

    public function __construct(
        $activeUsersSegment,
        $inactiveUsersSegment,
        TemplatesRepository $templatesRepository,
        LayoutsRepository $layoutsRepository,
        ListsRepository $listsRepository,
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        PermissionManager $permissionManager,
        User $user
    ) {
        $this->activeUsersSegment = $activeUsersSegment;
        $this->inactiveUsersSegment = $inactiveUsersSegment;
        $this->templatesRepository = $templatesRepository;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->permissionManager = $permissionManager;
        $this->user = $user;
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

        $form->addSelect('locked_mail_layout_id', 'Template for non-subscribers', $this->layoutsRepository->all()->fetchPairs('id', 'name'));

        $mailTypes = $this->listsRepository->all()->fetchPairs('id', 'code');

        $form->addSelect('mail_type_id', 'Type', $mailTypes)
            ->setRequired("Field 'Type' is required.");

        $form->addText('from', 'Sender')
            ->setHtmlAttribute('placeholder', 'e.g. info@domain.com')
            ->setRequired("Field 'Sender' is required.");

        $form->addText('subject', 'Subject')
            ->setRequired("Field 'Subject' is required.");

        $form->addHidden('html_content');
        $form->addHidden('text_content');
        $form->addHidden('locked_html_content');
        $form->addHidden('locked_text_content');
        $form->addHidden('article_id');

        $defaults = [
            'name' => 'MediaBrifing ' . date('j.n.Y'),
            'code' => 'mbrf_' . date('dmY'),
            'mail_layout_id' => 33, // layout for subscribers
            'locked_mail_layout_id' => 33, // layout for non-subscribers
            'mail_type_id' => 16, // mediabrifing
            'from' => 'Denník N <info@dennikn.sk>',
        ];

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

    public function formSucceeded(Form $form, ArrayHash $values): void
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

            $jobContext = null;
            if ($values['article_id']) {
                $jobContext = 'newsletter.' . $values['article_id'];
            }

            $mailJob = $this->jobsRepository->add((new JobSegmentsManager())->includeSegment($segmentCode, Crm::PROVIDER_ALIAS), $jobContext);
            $batch = $this->batchesRepository->add($mailJob->id, null, null, BatchesRepository::METHOD_RANDOM);
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
            $values['locked_html_content'],
            $values['locked_text_content'],
            $values['locked_mail_layout_id'],
            $this->inactiveUsersSegment
        );
        $generate(
            $values['html_content'],
            $values['text_content'],
            $values['mail_layout_id'],
            $this->activeUsersSegment
        );

        $this->onSave->__invoke();
    }
}
