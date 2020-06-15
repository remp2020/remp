<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\Multiplier;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IBatchExperimentEvaluationFactory;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Components\ISendingStatsFactory;
use Remp\MailerModule\Forms\EditBatchFormFactory;
use Remp\MailerModule\Forms\IFormFactory;
use Remp\MailerModule\Forms\JobFormFactory;
use Remp\MailerModule\Forms\NewBatchFormFactory;
use Remp\MailerModule\Forms\NewTemplateFormFactory;
use Remp\MailerModule\Job\MailCache;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobQueueRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\Segment\Aggregator;
use Tracy\Debugger;

final class JobPresenter extends BasePresenter
{
    private $jobsRepository;

    private $jobFormFactory;

    private $batchesRepository;

    private $batchTemplatesRepository;

    private $templatesRepository;

    private $logsRepository;

    private $newBatchFormFactory;

    private $editBatchFormFactory;

    private $newTemplateFormFactory;

    private $userSubscriptionsRepository;

    private $linkGenerator;

    private $segmentAggregator;

    private $jobQueueRepository;

    private $mailCache;

    private $latteFactory;

    private $listsRepository;

    public function __construct(
        JobsRepository $jobsRepository,
        JobFormFactory $jobFormFactory,
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        TemplatesRepository $templatesRepository,
        LogsRepository $logsRepository,
        NewBatchFormFactory $newBatchFormFactory,
        EditBatchFormFactory $editBatchFormFactory,
        NewTemplateFormFactory $newTemplateFormFactory,
        UserSubscriptionsRepository $userSubscriptionsRepository,
        Aggregator $segmentAggregator,
        MailCache $mailCache,
        JobQueueRepository $jobQueueRepository,
        ILatteFactory $latteFactory,
        LinkGenerator $linkGenerator,
        ListsRepository $listsRepository
    ) {
        parent::__construct();
        $this->jobsRepository = $jobsRepository;
        $this->jobFormFactory = $jobFormFactory;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->templatesRepository = $templatesRepository;
        $this->logsRepository = $logsRepository;
        $this->newBatchFormFactory = $newBatchFormFactory;
        $this->editBatchFormFactory = $editBatchFormFactory;
        $this->newTemplateFormFactory = $newTemplateFormFactory;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->segmentAggregator = $segmentAggregator;
        $this->mailCache = $mailCache;
        $this->jobQueueRepository = $jobQueueRepository;
        $this->latteFactory = $latteFactory;
        $this->linkGenerator = $linkGenerator;
        $this->listsRepository = $listsRepository;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $mailTypePairs = $this->listsRepository->all()->fetchPairs('id', 'title');

        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('defaultJsonData'))
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setColSetting('segment', [
                'orderable' => false,
                'priority' => 1,
            ])
            ->setColSetting('batches', [
                'orderable' => false,
                'filter' => $mailTypePairs,
                'priority' => 2,
            ])
            ->setColSetting('sent_count', [
                'header' => 'sent',
                'orderable' => false,
                'priority' => 1,
                'class' => 'text-right',
            ])
            ->setColSetting('opened_count', [
                'header' => 'opened',
                'orderable' => false,
                'priority' => 3,
                'class' => 'text-right',
            ])
            ->setColSetting('clicked_count', [
                'header' => 'clicked',
                'orderable' => false,
                'priority' => 3,
                'class' => 'text-right',
            ])
            ->setColSetting('unsubscribed_count', [
                'header' => 'unsubscribed',
                'orderable' => false,
                'priority' => 3,
                'class' => 'text-right',
            ])
            ->setRowAction('show', 'palette-Cyan zmdi-eye', 'Show job')
            ->setTableSetting('order', Json::encode([[0, 'DESC']]))
            ->setTableSetting('exportColumns', [0,1,2,3,4,5,6]);

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $listIds = null;
        foreach ($request['columns'] as $column) {
            if ($column['name'] !== 'batches') {
                continue;
            }
            if (!empty($column['search']['value'])) {
                $listIds = explode(',', $column['search']['value']);
            }
            break;
        }

        $jobsCount = $this->jobsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $listIds, null, null)
            ->count('*');

        $jobs = $this->jobsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $listIds, $request['length'], $request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->jobsRepository->totalCount(),
            'recordsFiltered' => $jobsCount,
            'data' => []
        ];

        $segments = [];
        $segmentList = $this->segmentAggregator->list();
        array_walk($segmentList, function ($segment) use (&$segments) {
            $segments[$segment['code']] = $segment['name'];
        });
        if ($this->segmentAggregator->hasErrors()) {
            $result['error'] = 'Unable to fetch list of segments, please check the application configuration.';
            Debugger::log($this->segmentAggregator->getErrors()[0], Debugger::WARNING);
        }

        $latte = $this->latteFactory->create();
        \Latte\Macros\CoreMacros::install($latte->getCompiler());
        \Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());
        $latte->addProvider('uiControl', $this->linkGenerator);

        /** @var ActiveRow $job */
        foreach ($jobs as $job) {
            $status = $latte->renderToString(__DIR__  . '/templates/Job/_job_status.latte', ['job' => $job]);
            $sentCount = $latte->renderToString(__DIR__  . '/templates/Job/_sent_count.latte', ['job' => $job]);
            $openedCount = $latte->renderToString(__DIR__  . '/templates/Job/_opened_count.latte', ['job' => $job]);
            $clickedCount = $latte->renderToString(__DIR__  . '/templates/Job/_clicked_count.latte', ['job' => $job]);

            $unsubscribedCount = $latte->renderToString(__DIR__  . '/templates/Job/_unsubscribed_count.latte', ['job' => $job]);

            $result['data'][] = [
                'actions' => [
                    'show' => $this->link('Show', $job->id),
                ],
                $job->created_at,
                (isset($segments[$job->segment_code]) ? $segments[$job->segment_code] : 'Missing segment'),
                $status,
                $sentCount,
                $openedCount,
                $clickedCount,
                $unsubscribedCount,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderShow($id)
    {
        $job = $this->jobsRepository->find($id);

        $segmentList = $this->segmentAggregator->list();
        array_walk($segmentList, function ($segment) use (&$job) {
            if ($segment['code'] == $job->segment_code) {
                $this->template->segment = $segment;
            }
        });
        if ($this->segmentAggregator->hasErrors()) {
            $this->flashMessage('Unable to fetch list of segments, please check the application configuration.', 'danger');
            Debugger::log($this->segmentAggregator->getErrors(), Debugger::WARNING);
        }

        $batchUnsubscribeStats = [];
        foreach ($job->related('mail_job_batch') as $batch) {
            $batchUnsubscribeStats[$batch->id] = $this->userSubscriptionsRepository->getTable()
                ->where([
                    'utm_content' => (string) $batch->id,
                    'subscribed' => false,
                ])
                ->count('*');
        }

        $this->template->job = $job;
        $this->template->total_sent = $this->logsRepository->getJobLogs($job->id)->count('*');
        $this->template->jobIsEditable = $this->jobsRepository->isEditable($job->id);
        $this->template->batchUnsubscribeStats = $batchUnsubscribeStats;
    }

    public function renderEditJob($id)
    {
        $this->template->job = $this->jobsRepository->find($id);
    }

    public function createComponentEditJobForm()
    {
        $jobId = $this->params['id'] ?? null;
        $form = $this->jobFormFactory->create($jobId);
        $this->jobFormFactory->onSuccess = function ($job) {
            $this->flashMessage('Job was updated');
            $this->redirect('Show', $job->id);
        };
        $this->jobFormFactory->onError = function ($job, $message) {
            $this->flashMessage($message, 'danger');
            $this->redirect('Show', $job->id);
        };
        return $form;
    }

    public function renderEditBatch($id)
    {
        $batch = $this->batchesRepository->find($id);
        $this->template->batch = $batch;
    }

    public function handleRemoveTemplate($id)
    {
        $batchTemplate = $this->batchTemplatesRepository->find($id);
        $this->batchTemplatesRepository->delete($batchTemplate);

        $this->flashMessage('Email was removed');
        $this->redirect('Show', $batchTemplate->job_id);
    }

    public function handleSetBatchReady($id)
    {
        $batch = $this->batchesRepository->find($id);
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATUS_READY]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleSetBatchSend($id)
    {
        $batch = $this->batchesRepository->find($id);
        $priority = $this->batchesRepository->getBatchPriority($batch);
        $this->mailCache->restartQueue($batch->id, $priority);
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATUS_SENDING]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleSetBatchUserStop($id)
    {
        $batch = $this->batchesRepository->find($id);
        $this->mailCache->pauseQueue($batch->id);
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATUS_USER_STOP]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleSetBatchCreated($id)
    {
        $batch = $this->batchesRepository->find($id);
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATUS_CREATED]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleRemoveBatch($id)
    {
        $batch = $this->batchesRepository->find($id);

        $sentCount = $this->logsRepository->getTable()->where([
            'mail_job_batch_id' => $batch->id,
        ])->count('*');
        if ($sentCount > 0) {
            $this->flashMessage('Batch was not removed, some emails were already sent');
            $this->redirect('Show', $batch->job_id);
        }

        $this->batchTemplatesRepository->deleteByBatchId($batch->id);
        $this->mailCache->removeQueue($batch->id);
        $this->jobQueueRepository->deleteJobsByBatch($batch->id, true);
        $this->batchesRepository->delete($batch);

        $this->flashMessage('Batch was removed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleTemplatesByListId($listId, $sourceForm, $sourceField, $targetField, $snippet = null)
    {
        $emptyValue = empty($listId);
        if (!empty($listId)) {
            $this[$sourceForm][$sourceField]
                ->setDefaultValue($listId);
            $this[$sourceForm][$targetField]
                ->setItems($this->templatesRepository->pairs($listId));
        } else {
            $this[$sourceForm][$sourceField]
                ->setDefaultValue(null);
            $this[$sourceForm][$targetField]
                ->setItems([]);
        }

        if ($snippet) {
            $this->redrawControl($snippet);
        }
        $this->redrawControl('wrapper');
        $this->redrawControl('batchesWrapper');
        $this->redrawControl('batches');
    }

    public function createComponentNewBatchForm()
    {
        $form = $this->newBatchFormFactory->create($this->params['id']);

        $this->newBatchFormFactory->onSuccess = function ($job) {
            $this->flashMessage('Batch was added');
            $this->redirect('Show', $job->id);
        };

        return $form;
    }

    public function createComponentEditBatchForm()
    {
        $batch = $this->batchesRepository->find($this->getParameter('id'));
        $form = $this->editBatchFormFactory->create($batch);

        $this->editBatchFormFactory->onSuccess = function ($batch, $buttonSubmitted) {
            $this->flashMessage(sprintf('Batch #%d was updated', $batch->id));

            // redirect based on button clicked by user
            if ($buttonSubmitted === IFormFactory::FORM_ACTION_SAVE_CLOSE) {
                $this->redirect('Show', $batch->job->id);
            } else {
                $this->redirect('EditBatch', $batch->id);
            }
        };

        return $form;
    }

    public function createComponentNewTemplateForm()
    {
        return new Multiplier(function ($batchId) {
            $form = $this->newTemplateFormFactory->create($batchId);

            $this->newTemplateFormFactory->onSuccess = function ($job) {
                $this->flashMessage('Email was added');
                $this->redirect('Show', $job->id);
            };

            return $form;
        });
    }

    protected function createComponentTemplateStats(ISendingStatsFactory $factory)
    {
        return new Multiplier(function ($templateId) use ($factory) {
            $templateStats = $factory->create();

            $template = $this->templatesRepository->find($templateId);
            $templateStats->addTemplate($template);
            $templateStats->showConversions();

            return $templateStats;
        });
    }

    protected function createComponentJobBatchTemplateStats(ISendingStatsFactory $factory)
    {
        return new Multiplier(function ($jobBatchTemplateId) use ($factory) {
            $stats = $factory->create();

            $jobBatchTemplate = $this->batchTemplatesRepository->find($jobBatchTemplateId);
            $stats->addJobBatchTemplate($jobBatchTemplate);
            $stats->showConversions();

            return $stats;
        });
    }

    protected function createComponentJobStats(ISendingStatsFactory $factory)
    {
        $templateStats = $factory->create();

        $batches = $this->batchesRepository
            ->getTable()
            ->where([
                'mail_job_id' => $this->params['id'],
            ]);

        foreach ($batches as $batch) {
            $templateStats->addBatch($batch);
        }
        $templateStats->showConversions();

        return $templateStats;
    }

    public function createComponentBatchExperimentEvaluation(IBatchExperimentEvaluationFactory $factory)
    {
        return $factory->create();
    }
}
