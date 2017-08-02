<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Segment\Aggregator;

class JobFormFactory extends Object
{
    /** @var JobsRepository */
    private $jobsRepository;

    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var BatchesRepository */
    private $batchesRepository;

    /** @var BatchTemplatesRepository */
    private $batchTemplatesRepository;

    /** @var Aggregator */
    private $segmentAggregator;

    public $onSuccess;

    public function __construct(
        JobsRepository $jobsRepository,
        TemplatesRepository $templatesRepository,
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        Aggregator $segmentAggregator
    ) {
        $this->jobsRepository = $jobsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->segmentAggregator = $segmentAggregator;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        $segments = [];
        $segmentList = $this->segmentAggregator->list();
        array_walk($segmentList, function ($segment) use (&$segments) {
            $segments[$segment['provider']][$segment['code']] = $segment['name'];
        });

        $form->addSelect('segment_code', 'Segment', $segments);

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
        $segments = [];
        $segmentList = $this->segmentAggregator->list();
        array_walk($segmentList, function ($segment) use (&$segments) {
            $segments[$segment['code']] = $segment['provider'];
        });

        $job = $this->jobsRepository->add($values['segment_code'], $segments[$values['segment_code']]);

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
