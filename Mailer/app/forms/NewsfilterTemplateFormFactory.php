<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\SegmentsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class NewsfilterTemplateFormFactory
{
    private $activeUsersSegment;

    private $inactiveUsersSegment;

    private $mailTemplatesRepository;

    private $mailLayoutsRepository;

    private $segmentsRepository;

    private $mailJobsRepository;

    private $mailJobBatchRepository;

    private $mailTypesRepository;

    public $onUpdate;

    public $onSave;

    public function __construct(
        $activeUsersSegment,
        $inactiveUsersSegment,
        TemplatesRepository $mailTemplatesRepository,
        LayoutsRepository $mailLayoutsRepository,
        ListsRepository $mailTypesRepository,
        SegmentsRepository $segmentsRepository,
        JobsRepository $mailJobsRepository,
        BatchesRepository $mailJobBatchRepository
    ) {
        $this->activeUsersSegment = $activeUsersSegment;
        $this->inactiveUsersSegment = $inactiveUsersSegment;
        $this->mailTemplatesRepository = $mailTemplatesRepository;
        $this->mailLayoutsRepository = $mailLayoutsRepository;
        $this->mailTypesRepository = $mailTypesRepository;
        $this->segmentsRepository = $segmentsRepository;
        $this->mailJobsRepository = $mailJobsRepository;
        $this->mailJobBatchRepository = $mailJobBatchRepository;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        $form->addText('name', 'Name')
            ->setAttribute('placeholder', 'Newsfilter 25.4.2018');

        $form->addText('code', 'Identifier')
            ->setAttribute('placeholder', 'mail.data.mail_templates.placeholder.code');

        $form->addSelect('mail_layout_id', 'Template', $this->mailLayoutsRepository->all()->fetchPairs('id', 'name'));
        
        $form->addSelect('locked_mail_layout_id', 'Template for non-payers', $this->mailLayoutsRepository->all()->fetchPairs('id', 'name'));

        $mailTypes = $this->mailTypesRepository->getTable()->where(['is_public' => true])->order('sorting ASC')->fetchPairs('id', 'code');

        $form->addSelect('mail_type_id', 'Type', $mailTypes);

        $form->addText('from', 'Sender')
            ->setAttribute('placeholder', 'e.g. info@domena.com');

        $form->addText('subject', 'Subject');

        $form->addHidden('html_content');
        $form->addHidden('text_content');
        $form->addHidden('locked_html_content');
        $form->addHidden('locked_text_content');
        $form->addHidden('with_jobs');

        $layoutForNonPayers = $this->mailTemplatesRepository->getByCode('weekly_newsletter_290515');
        if (!$layoutForNonPayers){
            throw new \Exception("Missing email template with code 'weekly_newsletter_290515'");
        }

        $layoutForPayers = $this->mailTemplatesRepository->getByCode('weekly_newsletter_120615');
        if (!$layoutForPayers){
            throw new \Exception("Missing email template with code 'weekly_newsletter_120615'");
        }

        $defaults = [
            'name' => 'Newsfilter ' . date('j.n.Y'),
            'code' => 'nwsf_' . date('dmY'),
            'mail_layout_id' => $layoutForNonPayers->id, // layout for payed subscribers
            'locked_mail_layout_id' => $layoutForNonPayers->id, // layout for non-payers
            'mail_type_id' => 9, // newsfilter,
            'from' => 'Denník N <info@dennikn.sk>',
        ];

        $form->setDefaults($defaults);

        $withJobs = $form->addSubmit('generate_emails_jobs', 'system.save');
        $withJobs->getControlPrototype()
            ->setName('button')
            ->setHtml('Generate emails and mailing lists');
        $withJobs->onClick[] = [$this, 'processWithJobs'];

        $withoutJobs = $form->addSubmit('generate_emails', 'system.save');
        $withoutJobs->getControlPrototype()
            ->setName('button')
            ->setHtml('Generate only emails');
        $withoutJobs->onClick[] = [$this, 'processWithoutJobs'];

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function processWithJobs(SubmitButton $button)
    {
        $button->getForm()->getComponent('with_jobs')->setValue(true);
    }

    public function processWithoutJobs(SubmitButton $button)
    {
        $button->getForm()->getComponent('with_jobs')->setValue(false);
    }

    public function formSucceeded(Form $form, $values)
    {
        if ($this->mailTemplatesRepository->exists($values['code'])) {
            $form->addError("Identifier '{$values['code']}' already exists");
            return;
        }

        $generate = function ($htmlBody, $textBody, $mailLayoutId, $segmentCode = null) use ($values) {
            $mailTemplate = $this->mailTemplatesRepository->add(
                $values['code'],
                $values['name'],
                $mailLayoutId,
                '',
                $values['from'],
                $values['subject'],
                $textBody,
                $htmlBody,
                $values['mail_type_id'],
                true
            );

            if ($values['with_jobs']) {
                $segmentRow = $this->segmentsRepository->findBy('code', $segmentCode);
                $mailJob = $this->mailJobsRepository->add($segmentRow);
                $batch = $this->mailJobBatchRepository->add($mailJob, MailJobBatchRepository::METHOD_RANDOM);
                $this->mailJobBatchRepository->addTemplate($batch, $mailTemplate);
            }
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

        $this->onSave->__invoke($values['with_jobs']);
    }
}
