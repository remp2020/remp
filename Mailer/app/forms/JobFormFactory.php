<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Segment\Aggregator;
use Remp\MailerModule\Segment\SegmentException;

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
        try {
            $segmentList = $this->segmentAggregator->list();
            array_walk($segmentList, function ($segment) use (&$segments) {
                $segments[$segment['provider']][$segment['provider'] . '::' . $segment['code']] = $segment['name'];
            });
        } catch (SegmentException $e) {
            $form->addError('Unable to fetch list of segments, please check the application configuration.');
        }

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
        $segment = explode('::', $values['segment_code']);

        $job = $this->jobsRepository->add($segment[1], $segment[0]);

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
