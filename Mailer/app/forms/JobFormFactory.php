<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\SegmentsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class JobFormFactory extends Object
{
    /** @var JobsRepository */
    private $jobsRepository;

    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var SegmentsRepository */
    private $segmentRepository;

    /** @var BatchesRepository */
    private $batchesRepository;

    /** @var BatchTemplatesRepository */
    private $batchTemplatesRepository;

    public $onSuccess;

    public function __construct(
        JobsRepository $jobsRepository,
        TemplatesRepository $templatesRepository,
        SegmentsRepository $segmentRepository,
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository
    ) {
        $this->jobsRepository = $jobsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->segmentRepository = $segmentRepository;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        $form->addSelect('segment_id', 'Segment', $this->segmentRepository->all()->fetchPairs('id', 'name'));

        $form->addSelect('template_id', 'Email', $this->templatesRepository->all()->fetchPairs('id', 'name'));

        $form->addText('email_count', 'Max number of sent emails');

        $form->addText('start_at', 'Start date');

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $job = $this->jobsRepository->add($values['segment_id']);

        $batch = $this->batchesRepository->add(
            $job->id,
            !empty($values['email_count']) ? $values['email_count'] : null,
            !empty($values['start_at']) ? $values['start_at'] : null
        );

        $batchTemplate = $this->batchTemplatesRepository->add(
            $job->id,
            $batch->id,
            $values['template_id']
        );

        ($this->onSuccess)($job);
    }
}
