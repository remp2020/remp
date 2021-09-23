<?php
declare(strict_types=1);

namespace Remp\Mailer\Forms;

use Nette\Application\UI\Form;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Models\Segment\Crm;

class ArticleUrlParserTemplateFormFactory
{
    private $segmentCode;

    private $layoutCode;

    private $templatesRepository;

    private $layoutsRepository;

    private $jobsRepository;

    private $batchesRepository;

    private $listsRepository;

    private $sourceTamplatesRepository;

    public $onUpdate;

    public $onSave;

    public function __construct(
        TemplatesRepository $templatesRepository,
        LayoutsRepository $layoutsRepository,
        ListsRepository $listsRepository,
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        SourceTemplatesRepository $sourceTemplatesRepository
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->sourceTamplatesRepository = $sourceTemplatesRepository;
    }

    public function setSegmentCode(string $segmentCode): void
    {
        $this->segmentCode = $segmentCode;
    }

    public function setLayoutCode(string $layoutCode): void
    {
        $this->layoutCode = $layoutCode;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        if (!$this->segmentCode) {
            $form->addError("Default value 'segment code' is missing.");
        }

        if (!$this->layoutCode) {
            $form->addError("Default value 'layout code' is missing.");
        }

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.");

        $form->addText('code', 'Identifier')
            ->setRequired("Field 'Identifier' is required.");

        $mailTypes = $this->listsRepository->getTable()
            ->where(['is_public' => true])
            ->order('sorting ASC')
            ->fetchPairs('id', 'title');

        $form->addSelect('mail_type_id', 'Type', $mailTypes)
            ->setPrompt('Select type')
            ->setRequired("Field 'Type' is required.");

        $form->addText('from', 'Sender')
            ->setHtmlAttribute('placeholder', 'e.g. info@domain.com')
            ->setRequired("Field 'Sender' is required.");

        $form->addText('subject', 'Subject')
            ->setRequired("Field 'Subject' is required.");

        $form->addHidden('mail_layout_id');
        $form->addHidden('html_content');
        $form->addHidden('text_content');

        $sourceTemplate = $this->sourceTamplatesRepository->find($_POST['source_template_id']);

        $defaults = [
            'name' => "{$sourceTemplate->title} " . date('d. m. Y'),
            'code' => "{$sourceTemplate->code}_" . date('Y-m-d'),
        ];

        if ($this->layoutCode) {
            $defaults['mail_layout_id'] = (int)$this->layoutsRepository->findBy('code', $this->layoutCode)->id;
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

    public function formSucceeded(Form $form, $values)
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
                (int)$mailLayoutId,
                $values['mail_type_id']
            );

            $jobContext = null;

            $mailJob = $this->jobsRepository->add($segmentCode, Crm::PROVIDER_ALIAS, $jobContext);
            $batch = $this->batchesRepository->add($mailJob->id, null, null, BatchesRepository::METHOD_RANDOM);
            $this->batchesRepository->addTemplate($batch, $mailTemplate);

            $batchStatus = BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND;
            if ($form['generate_emails_jobs_created']->isSubmittedBy()) {
                $batchStatus = BatchesRepository::STATUS_CREATED;
            }

            $this->batchesRepository->updateStatus($batch, $batchStatus);
        };

        $generate(
            $values['html_content'],
            $values['text_content'],
            $values['mail_layout_id'],
            $this->segmentCode
        );

        $this->onSave->__invoke();
    }
}
