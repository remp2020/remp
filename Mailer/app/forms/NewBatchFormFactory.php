<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Nette\Utils\Json;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\SegmentsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class NewBatchFormFactory extends Object
{
    /** @var JobsRepository */
    private $jobsRepository;

    /** @var BatchesRepository */
    private $batchesRepository;

    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var BatchTemplatesRepository */
    private $batchTemplatesRepository;

    private $listsRepository;

    public $onSuccess;

    public function __construct(
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        TemplatesRepository $templatesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        ListsRepository $listsRepository
    ) {
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->templatesRepository = $templatesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->listsRepository = $listsRepository;
    }

    public function create($jobId = null, $segmentCode = null)
    {
        $form = new Form;
        $form->addProtection();

        $methods = [
            'random' => 'Random',
            'sequential' => 'Sequential',
        ];
        $form->addSelect('method', 'Method', $methods);

        $listPairs = $this->listsRepository->all()->fetchPairs('id', 'title');
        $templatePairs = $this->templatesRepository->all()->fetchPairs('id', 'name');

        $form->addSelect('mail_type_id', 'Email A alternative', $listPairs)
            ->setPrompt('Select newsletter list');

        $form->addSelect('template_id', null, $templatePairs)
            ->setPrompt('Select email')
            ->setRequired('Email for A alternative is required');

        $form->addSelect('b_mail_type_id', 'Email B alternative (optional, can be added later)', $listPairs)
            ->setPrompt('Select newsletter list');

        $form->addSelect('b_template_id', null, $templatePairs)
            ->setPrompt('Select alternative email');

        $form->addText('email_count', 'Number of emails');

        $form->addText('start_at', 'Start date');

        $form->addHidden('job_id', $jobId);

        $form->addHidden('segment_code', $segmentCode);

        $templatePairs = [];
        foreach ($this->templatesRepository->all() as $template) {
            $templatePairs[$template->mail_type_id][] = [
                'value' => $template->id,
                'label' => $template->name,
            ];
        }
        $form->addHidden('template_pairs', Json::encode($templatePairs))->setHtmlId('template_pairs');

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        if (!$values['job_id']) {
            $segment = explode('::', $values['segment_code']);
            $values['job_id'] = $this->jobsRepository->add($segment[1], $segment[0]);
        }

        $batch = $this->batchesRepository->add(
            $values['job_id'],
            !empty($values['email_count']) ? (int)$values['email_count'] : null,
            $values['start_at'],
            $values['method']
        );

        $this->batchTemplatesRepository->add(
            $values['job_id'],
            $batch->id,
            $values['template_id']
        );

        if ($values['b_template_id'] !== null) {
            $this->batchTemplatesRepository->add(
                $values['job_id'],
                $batch->id,
                $values['b_template_id']
            );
        }

        ($this->onSuccess)($batch->job);
    }
}
