<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\FormRenderer\MaterialRenderer;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Tracy\Debugger;

class JobFormFactory
{
    use SmartObject;

    private $jobsRepository;

    private $batchesRepository;

    private $segmentAggregator;

    public $onSuccess;
    public $onError;

    public function __construct(
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        Aggregator $segmentAggregator
    ) {
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->segmentAggregator = $segmentAggregator;
    }

    public function create(int $jobId): Form
    {
        $form = new Form();
        $form->addProtection();
        $form->setRenderer(new MaterialRenderer());
        $form->addHidden('job_id', $jobId);

        $job = $this->jobsRepository->find($jobId);
        if (!$job) {
            $form->addError('Unable to load Mail Job.');
        }

        if ($this->batchesRepository->notEditableBatches($jobId)->count() > 0) {
            $form->addError("Job can't be updated. One or more Mail Job Batches were already started.");
        }

        $segments = [];
        $segmentList = $this->segmentAggregator->list();
        array_walk($segmentList, function ($segment) use (&$segments) {
            $segments[$segment['provider']][$segment['provider'] . '::' . $segment['code']] = $segment['name'];
        });
        if ($this->segmentAggregator->hasErrors()) {
            $form->addError('Unable to fetch list of segments, please check the application configuration.');
            Debugger::log($this->segmentAggregator->getErrors()[0], Debugger::WARNING);
        }

        $form->addSelect('segment_code', 'Segment', $segments)
            ->setPrompt('Select segment')
            ->setRequired("Field 'Segment' is required.")
            ->setHtmlAttribute('class', 'selectpicker')
            ->setHtmlAttribute('data-live-search', 'true')
            ->setHtmlAttribute('data-live-search-normalize', 'true')
            ->setDefaultValue($job->segment_provider . '::' . $job->segment_code);

        $form->addText('context', 'Context')
            ->setNullable()
            ->setDefaultValue($job->context);

        $form->addSubmit('save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $job = $this->jobsRepository->find($values['job_id']);

        $segment = explode('::', $values['segment_code']);

        $jobNewData = [
            'segment_provider' => $segment[0],
            'segment_code' => $segment[1],
            'context' => $values->context,
        ];

        try {
            $this->jobsRepository->update($job, $jobNewData);
        } catch (\Exception $e) {
            ($this->onError)($job, $e->getMessage());
        }

        ($this->onSuccess)($job);
    }
}
