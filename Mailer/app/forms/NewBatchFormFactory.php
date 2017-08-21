<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\SegmentsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class NewBatchFormFactory extends Object
{
    /** @var JobsRepository */
    private $jobsRepository;

    /** @var BatchesRepository */
    private $batchesRepository;

    /** @var TemplatesRepository */
    private  $templatesRepository;

    /** @var BatchTemplatesRepository */
    private $batchTemplatesRepository;

    public $onSuccess;

    public function __construct(
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        TemplatesRepository $templatesRepository,
        BatchTemplatesRepository $batchTemplatesRepository
    ) {
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->templatesRepository = $templatesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
    }

    public function create($jobId)
    {
        $form = new Form;
        $form->addProtection();

        $methods = [
            'random' => 'Random',
            'sequential' => 'Sequential',
        ];
        $form->addSelect('method', 'Method', $methods);

        $form->addSelect('template_id', 'Email', $this->templatesRepository->all()->fetchPairs('id', 'name'))
            ->setPrompt('Select email')
            ->setRequired();

        $form->addSelect('b_template_id', 'Email B Alternative', $this->templatesRepository->all()->fetchPairs('id', 'name'))
            ->setPrompt('Select email');

        $form->addText('email_count', 'Number of emails');

        $form->addText('start_at', 'Start date');

        $form->addHidden('job_id', $jobId);

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
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
