<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Segment\Crm;

class NewsfilterTemplateFormFactory
{
    private $activeUsersSegment;

    private $inactiveUsersSegment;

    private $templatesRepository;

    private $layoutsRepository;

    private $jobsRepository;

    private $batchesRepository;

    private $listsRepository;

    public $onUpdate;

    public $onSave;

    public function __construct(
        $activeUsersSegment,
        $inactiveUsersSegment,
        TemplatesRepository $templatesRepository,
        LayoutsRepository $layoutsRepository,
        ListsRepository $listsRepository,
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository
    ) {
        $this->activeUsersSegment = $activeUsersSegment;
        $this->inactiveUsersSegment = $inactiveUsersSegment;
        $this->templatesRepository = $templatesRepository;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        $form->addText('name', 'Name');

        $form->addText('code', 'Identifier');

        $form->addSelect('mail_layout_id', 'Template', $this->layoutsRepository->all()->fetchPairs('id', 'name'));
        
        $form->addSelect('locked_mail_layout_id', 'Template for non-payers', $this->layoutsRepository->all()->fetchPairs('id', 'name'));

        $mailTypes = $this->listsRepository->getTable()->where(['is_public' => true])->order('sorting ASC')->fetchPairs('id', 'code');

        $form->addSelect('mail_type_id', 'Type', $mailTypes);

        $form->addText('from', 'Sender')
            ->setAttribute('placeholder', 'e.g. info@domain.com');

        $form->addText('subject', 'Subject');

        $form->addHidden('html_content');
        $form->addHidden('text_content');
        $form->addHidden('locked_html_content');
        $form->addHidden('locked_text_content');

        $defaults = [
            'name' => 'Newsfilter ' . date('j.n.Y'),
            'code' => 'nwsf_' . date('dmY'),
            'mail_layout_id' => 27, // layout for payers
            'locked_mail_layout_id' => 21, // layout for non-payers
            'mail_type_id' => 9, // newsfilter,
            'from' => 'Denník N <info@dennikn.sk>',
        ];

        $form->setDefaults($defaults);

        $withJobs = $form->addSubmit('generate_emails_jobs', 'system.save');
        $withJobs->getControlPrototype()
            ->setName('button')
            ->setHtml('Generate newsletter batch and start sending');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, $values)
    {
        if ($this->templatesRepository->exists($values['code'])) {
            $form->addError("Identifier '{$values['code']}' already exists");
            return;
        }

        $generate = function ($htmlBody, $textBody, $mailLayoutId, $segmentCode = null) use ($values) {
            $mailTemplate = $this->templatesRepository->add(
                $values['code'],
                $values['name'],
                '',
                $values['from'],
                $values['subject'],
                $textBody,
                $htmlBody,
                $mailLayoutId,
                $values['mail_type_id'],
                true
            );

            $mailJob = $this->jobsRepository->add($segmentCode, Crm::PROVIDER_ALIAS);
            $batch = $this->batchesRepository->add($mailJob->id, null, null, BatchesRepository::METHOD_RANDOM);
            $this->batchesRepository->addTemplate($batch, $mailTemplate);
            $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATE_READY]);
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
