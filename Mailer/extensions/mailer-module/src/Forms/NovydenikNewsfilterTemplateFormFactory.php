<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Models\Segment\Crm;

class NovydenikNewsfilterTemplateFormFactory
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

        $mailTypes = $this->listsRepository->getTable()->where(['is_public' => true])->order('sorting ASC')->fetchPairs('id', 'code');

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

        if (isset($_POST['source_template_id']) && $_POST['source_template_id'] == 21) {
            $defaults = [
                'name' => 'Brněnský orloj ' . date('j.n.Y'),
                'code' => 'nwsf_brnensky_orloj_' . date('dmY'),
                'mail_layout_id' => 2, // empty layout
                'locked_mail_layout_id' => 2, // empty layout
                'mail_type_id' => 15, // Brnensky orloj
                'from' => 'Deník N <info@denikn.cz>',
            ];
        } elseif (isset($_POST['source_template_id']) && $_POST['source_template_id'] == 17) {
            $defaults = [
                'name' => 'PolitikoN ' . date('j.n.Y'),
                'code' => 'nwsf_politikon_' . date('dmY'),
                'mail_layout_id' => 2, // empty layout
                'locked_mail_layout_id' => 2, // empty layout
                'mail_type_id' => 14, // Politikon
                'from' => 'Deník N <info@denikn.cz>',
            ];
        } elseif (isset($_POST['source_template_id']) && $_POST['source_template_id'] == 16) {
            $defaults = [
                'name' => 'Americký týdeník ' . date('j.n.Y'),
                'code' => 'nwsf_amer_tydenik_' . date('dmY'),
                'mail_layout_id' => 2, // empty layout
                'locked_mail_layout_id' => 2, // empty layout
                'mail_type_id' => 13, // Americký týdeník
                'from' => 'Jana Ciglerová Deník N <jana.ciglerova@denikn.cz>',
            ];
        } elseif (isset($_POST['source_template_id']) && $_POST['source_template_id'] == 15) {
            $defaults = [
                'name' => 'Částečný součet ' . date('j.n.Y'),
                'code' => 'nwsf_cs_' . date('dmY'),
                'mail_layout_id' => 2, // empty layout
                'locked_mail_layout_id' => 2, // empty layout
                'mail_type_id' => 12, // Částečný součet Petra Koubského,
                'from' => 'Petr Koubský <petr.koubsky@denikn.cz>',
            ];
        } elseif (isset($_POST['source_template_id']) && $_POST['source_template_id'] == 14) {
            $defaults = [
                'name' => 'Newsfilter Německo ' . date('j.n.Y'),
                'code' => 'nwsf_de_' . date('dmY'),
                'mail_layout_id' => 2, // empty layout
                'locked_mail_layout_id' => 2, // empty layout
                'mail_type_id' => 11, // nemecko,
                'from' => 'Deník N <info@denikn.cz>',
            ];
        } else {
            $defaults = [
                'name' => 'Newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_' . date('dmY'),
                'mail_layout_id' => 2, // empty layout
                'locked_mail_layout_id' => 2, // empty layout
                'mail_type_id' => 6, // newsfilter,
                'from' => 'Deník N <info@denikn.cz>',
            ];
        }

        $form->setDefaults($defaults);

        $withJobs = $form->addSubmit('generate_emails_jobs', 'system.save');
        $withJobs->getControlPrototype()
            ->setName('button')
            ->setHtml('Generate newsletter batch and start sending');

        $withJobsCreated = $form->addSubmit('generate_emails_jobs_created', 'system.save');
        $withJobsCreated->getControlPrototype()
            ->setName('button')
            ->setHtml('Generate newsletter batch');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    private function getUniqueTemplateCode($code)
    {
        $storedCodes = $this->templatesRepository->getTable()
            ->where('code LIKE ?', $code . '%')
            ->select('code')->fetchAll();

        if ($storedCodes) {
            $max = 0;
            foreach ($storedCodes as $c) {
                $parts = explode('-', $c->code);
                if (count($parts) > 1) {
                    $max = max($max, (int) $parts[1]);
                }
            }
            $code .= '-' . ($max + 1);
        }
        return $code;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $generate = function ($htmlBody, $textBody, $mailLayoutId, $segmentCode = null) use ($values, $form) {
            $mailTemplate = $this->templatesRepository->add(
                $values['name'],
                $this->getUniqueTemplateCode($values['code']),
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

            $mailJob = $this->jobsRepository->add($segmentCode, Crm::PROVIDER_ALIAS, $jobContext);
            $batch = $this->batchesRepository->add($mailJob->id, null, null, BatchesRepository::METHOD_RANDOM);
            $this->batchesRepository->addTemplate($batch, $mailTemplate);

            $batchStatus = BatchesRepository::STATUS_READY;
            if ($form['generate_emails_jobs_created']->isSubmittedBy()) {
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
