<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\FormRenderer\MaterialRenderer;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
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

        $jobSegmentsManager = new JobSegmentsManager($job);

        $includeSegmentsDefault = array_map(static function (array $includeSegment) {
            return $includeSegment['provider'] . '::' . $includeSegment['code'];
        }, $jobSegmentsManager->getIncludeSegments());

        $form->addMultiSelect('include_segment_codes', 'Include segments', $segments)
            ->setRequired("You have to include at least one segment.")
            ->setHtmlAttribute('class', 'selectpicker')
            ->setHtmlAttribute('data-live-search', 'true')
            ->setHtmlAttribute('data-live-search-normalize', 'true')
            ->setDefaultValue($includeSegmentsDefault);

        $excludeSegmentsDefault = array_map(static function (array $excludeSegment) {
            return $excludeSegment['provider'] . '::' . $excludeSegment['code'];
        }, $jobSegmentsManager->getExcludeSegments());

        $form->addMultiSelect('exclude_segment_codes', 'Exclude segments', $segments)
            ->setHtmlAttribute('class', 'selectpicker')
            ->setHtmlAttribute('data-live-search', 'true')
            ->setHtmlAttribute('data-live-search-normalize', 'true')
            ->setDefaultValue($excludeSegmentsDefault);

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

        $jobSegmentsManager = new JobSegmentsManager();
        foreach ($values['include_segment_codes'] as $includeSegment) {
            [$provider, $code] = explode('::', $includeSegment);
            $jobSegmentsManager->includeSegment($code, $provider);
        }
        foreach ($values['exclude_segment_codes'] as $excludeSegment) {
            [$provider, $code] = explode('::', $excludeSegment);
            $jobSegmentsManager->excludeSegment($code, $provider);
        }

        $jobNewData = [
            'segments' => $jobSegmentsManager->toJson(),
            'context' => $values->context,
        ];

        try {
            $this->jobsRepository->update($job, $jobNewData);
            $job = $this->jobsRepository->find($job->id);
        } catch (\Exception $e) {
            ($this->onError)($job, $e->getMessage());
        }

        ($this->onSuccess)($job);
    }
}
