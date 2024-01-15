<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Latte\Essential\CoreExtension;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Multiplier;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\UIExtension;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Remp\MailerModule\Components\BatchExperimentEvaluation\IBatchExperimentEvaluationFactory;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Components\SendingStats\ISendingStatsFactory;
use Remp\MailerModule\Forms\EditBatchFormFactory;
use Remp\MailerModule\Forms\IFormFactory;
use Remp\MailerModule\Forms\JobFormFactory;
use Remp\MailerModule\Forms\NewBatchFormFactory;
use Remp\MailerModule\Forms\NewTemplateFormFactory;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Job\MailCache;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tracy\Debugger;

final class JobPresenter extends BasePresenter
{
    public function __construct(
        private JobsRepository $jobsRepository,
        private JobFormFactory $jobFormFactory,
        private BatchesRepository $batchesRepository,
        private BatchTemplatesRepository $batchTemplatesRepository,
        private TemplatesRepository $templatesRepository,
        private LogsRepository $logsRepository,
        private NewBatchFormFactory $newBatchFormFactory,
        private EditBatchFormFactory $editBatchFormFactory,
        private NewTemplateFormFactory $newTemplateFormFactory,
        private Aggregator $segmentAggregator,
        private MailCache $mailCache,
        private JobQueueRepository $jobQueueRepository,
        private LatteFactory $latteFactory,
        private LinkGenerator $linkGenerator,
        private ListsRepository $listsRepository,
        private DataTableFactory $dataTableFactory,
        private ISendingStatsFactory $sendingStatsFactory,
        private IBatchExperimentEvaluationFactory $batchExperimentEvaluationFactory,
        private ListVariantsRepository $listVariantsRepository,
    ) {
        parent::__construct();
    }

    public function createComponentDataTableDefault()
    {
        $mailTypePairs = $this->listsRepository->all()->fetchPairs('id', 'title');

        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('defaultJsonData'))
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setColSetting('segments', [
                'orderable' => false,
                'priority' => 1,
                'render' => 'raw',
            ])
            ->setColSetting('batches', [
                'orderable' => false,
                'filter' => $mailTypePairs,
                'priority' => 2,
                'render' => 'raw',
            ])
            ->setColSetting('sent_count', [
                'header' => 'sent',
                'orderable' => false,
                'priority' => 1,
                'class' => 'text-right',
                'render' => 'raw',
            ])
            ->setColSetting('opened_count', [
                'header' => 'opened',
                'orderable' => false,
                'priority' => 3,
                'class' => 'text-right',
                'render' => 'raw',
            ])
            ->setColSetting('clicked_count', [
                'header' => 'clicked',
                'orderable' => false,
                'priority' => 3,
                'class' => 'text-right',
                'render' => 'raw',
            ])
            ->setColSetting('unsubscribed_count', [
                'header' => 'unsubscribed',
                'orderable' => false,
                'priority' => 3,
                'class' => 'text-right',
                'render' => 'raw',
            ])
            ->setRowAction('show', 'palette-Cyan zmdi-eye', 'Show job')
            ->setTableSetting('order', Json::encode([[0, 'DESC']]))
            ->setTableSetting('exportColumns', [0,1,2,3,4,5,6]);

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $listIds = [];
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
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $listIds)
            ->count('*');

        $jobs = $this->jobsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $listIds, (int)$request['length'], (int)$request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->jobsRepository->totalCount(),
            'recordsFiltered' => $jobsCount,
            'data' => []
        ];

        $segments = [];
        $segmentList = $this->segmentAggregator->list();
        array_walk($segmentList, function ($segment) use (&$segments) {
            $segments[$segment['provider']][$segment['code']] = $segment['name'];
        });
        if ($this->segmentAggregator->hasErrors()) {
            $result['error'] = 'Unable to fetch list of segments, please check the application configuration.';
            Debugger::log($this->segmentAggregator->getErrors()[0], Debugger::WARNING);
        }

        $latte = $this->latteFactory->create();
        $latte->addExtension(new CoreExtension());
        $latte->addExtension(new UIExtension($this));
        $latte->addProvider('uiControl', $this->linkGenerator);

        /** @var ActiveRow $job */
        foreach ($jobs as $job) {
            $status = $latte->renderToString(__DIR__  . '/templates/Job/_job_status.latte', ['job' => $job]);
            $sentCount = $latte->renderToString(__DIR__  . '/templates/Job/_sent_count.latte', ['job' => $job]);
            $openedCount = $latte->renderToString(__DIR__  . '/templates/Job/_opened_count.latte', ['job' => $job]);
            $clickedCount = $latte->renderToString(__DIR__  . '/templates/Job/_clicked_count.latte', ['job' => $job]);

            $unsubscribedCount = $latte->renderToString(__DIR__  . '/templates/Job/_unsubscribed_count.latte', ['job' => $job]);

            $jobSegmentsManager = new JobSegmentsManager($job);

            $includeSegments = [];
            foreach ($jobSegmentsManager->getIncludeSegments() as $segment) {
                $includeSegments[] = $segments[$segment['provider']][$segment['code']] ?? 'Missing segment';
            }

            $excludeSegments = [];
            foreach ($jobSegmentsManager->getExcludeSegments() as $segment) {
                $excludeSegments[] = $segments[$segment['provider']][$segment['code']] ?? 'Missing segment';
            }

            $segmentsRaw = $latte->renderToString(
                __DIR__  . '/templates/Job/_job_segments.latte',
                ['includeSegments' => $includeSegments, 'excludeSegments' => $excludeSegments]
            );

            $result['data'][] = [
                'actions' => [
                    'show' => $this->link('Show', $job->id),
                ],
                $job->created_at,
                $segmentsRaw,
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
        $jobSegmentsManager = new JobSegmentsManager($job);

        $this->template->includeSegments = $this->pairSegments($jobSegmentsManager->getIncludeSegments(), $segmentList);
        $this->template->excludeSegments = $this->pairSegments($jobSegmentsManager->getExcludeSegments(), $segmentList);

        if ($this->segmentAggregator->hasErrors()) {
            $this->flashMessage('Unable to fetch list of segments, please check the application configuration.', 'danger');
            Debugger::log($this->segmentAggregator->getErrors(), Debugger::WARNING);
        }

        $batchUnsubscribeStats = [];
        $batchPreparedEmailsStats = [];
        foreach ($job->related('mail_job_batch') as $batch) {
            $batchUnsubscribeStats[$batch->id] = 0;
            foreach ($batch->related('mail_job_batch_templates') as $mjbt) {
                $batchUnsubscribeStats[$batch->id] += $mjbt->mail_template->mail_type
                    ->related('mail_user_subscriptions')
                    ->where([
                        'rtm_campaign' => $mjbt->mail_template->code,
                        'rtm_content' => (string) $mjbt->mail_job_batch_id,
                        'subscribed' => false,
                    ])
                    ->count('*');
            }

            $batchPreparedEmailsStats[$batch->id] = $this->mailCache->countJobs($batch->id);
        }

        $this->template->job = $job;
        $this->template->total_sent = $this->logsRepository->getJobLogs($job->id)->count('*');
        $this->template->jobIsEditable = $this->jobsRepository->isEditable($job->id);
        $this->template->batchUnsubscribeStats = $batchUnsubscribeStats;
        $this->template->batchPreparedEmailsStats = $batchPreparedEmailsStats;
    }

    private function pairSegments($jobSegments, $aggSegments): array
    {
        $result = [];
        foreach ($jobSegments as $jobSegment) {
            foreach ($aggSegments as $aggSegment) {
                if ($aggSegment['provider'] === $jobSegment['provider']
                    && $aggSegment['code'] === $jobSegment['code']) {
                    $result[] =  $aggSegment['name'];
                    continue 2;
                }
            }
            $result[] = $jobSegment['provider'] . ':' . $jobSegment['code'] . ' - Missing segment';
        }

        return $result;
    }

    public function renderEditJob($id)
    {
        $this->template->job = $this->jobsRepository->find($id);
    }

    public function createComponentEditJobForm()
    {
        $form = $this->jobFormFactory->create((int) $this->params['id']);
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
        $this->redirect('Show', $batchTemplate->mail_job_id);
    }

    public function handleSetBatchReadyToSend($id)
    {
        if (!$this->permissionManager->isAllowed($this->getUser(), 'batch', 'start')) {
            throw new ForbiddenRequestException(
                "You don't have permission to run this action. (batch/start)",
                IResponse::S403_Forbidden
            );
        }
        $batch = $this->batchesRepository->find($id);
        $priority = $this->batchesRepository->getBatchPriority($batch);
        if (!$priority) {
            $this->flashMessage("You can't send batch which mail type have priority set to 0.");
            $this->redirect('Show', $batch->mail_job_id);
        }
        if ($batch->status === BatchesRepository::STATUS_PROCESSED) {
            $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_QUEUED);
        } else {
            $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND);
        }

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->mail_job_id);
    }

    public function handleSetBatchSend($id)
    {
        if (!$this->permissionManager->isAllowed($this->getUser(), 'batch', 'start')) {
            throw new ForbiddenRequestException(
                "You don't have permission to run this action. (batch/start)",
                IResponse::S403_Forbidden
            );
        }
        $batch = $this->batchesRepository->find($id);
        $priority = $this->batchesRepository->getBatchPriority($batch);
        if (!$priority) {
            $this->flashMessage("You can't send batch which mail type have priority set to 0.");
            $this->redirect('Show', $batch->mail_job_id);
        }
        $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_SENDING);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->mail_job_id);
    }

    public function handleSetBatchUserStop($id)
    {
        if (!$this->permissionManager->isAllowed($this->getUser(), 'batch', 'stop')) {
            throw new ForbiddenRequestException(
                "You don't have permission to run this action. (batch/stop)",
                IResponse::S403_Forbidden
            );
        }
        $batch = $this->batchesRepository->find($id);
        $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_USER_STOP);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->mail_job_id);
    }

    public function handleSetBatchCreated($id)
    {
        $batch = $this->batchesRepository->find($id);
        $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_CREATED);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->mail_job_id);
    }

    public function handleRemoveBatch($id)
    {
        $batch = $this->batchesRepository->find($id);

        $sentCount = $this->logsRepository->getTable()->where([
            'mail_job_batch_id' => $batch->id,
        ])->count('*');
        if ($sentCount > 0) {
            $this->flashMessage('Batch was not removed, some emails were already sent');
            $this->redirect('Show', $batch->mail_job_id);
        }

        $this->batchTemplatesRepository->deleteByBatchId($batch->id);
        $this->mailCache->removeQueue($batch->id);
        $this->jobQueueRepository->deleteJobsByBatch($batch->id, true);
        $this->batchesRepository->delete($batch);

        $this->flashMessage('Batch was removed.');
        $this->redirect('Show', $batch->mail_job_id);
    }

    public function handleSetBatchReadyToProcess($id)
    {
        if (!$this->permissionManager->isAllowed($this->getUser(), 'batch', 'process')) {
            throw new ForbiddenRequestException(
                "You don't have permission to run this action. (batch/process)",
                IResponse::S403_Forbidden
            );
        }
        $batch = $this->batchesRepository->find($id);
        $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_READY_TO_PROCESS);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->mail_job_id);
    }

    public function actionMailTypeTemplates($id): void
    {
        $templates = $this->templatesRepository->pairs((int) $id);
        $this->sendJson($templates);
    }

    public function actionMailTypeVariants($id): void
    {
        $mailType = $this->listsRepository->find($id);
        $variants = [];
        if ($mailType) {
            $variants = $this->listVariantsRepository->getVariantsForType($mailType)
                ->order('sorting')
                ->fetchPairs('id', 'title');
        }
        $this->sendJson($variants);
    }

    public function actionMailTypeCodeVariants($id): void
    {
        $mailType = $this->listsRepository->findByCode($id)->fetch();
        $variants = [];
        if ($mailType) {
            $variants = $this->listVariantsRepository->getVariantsForType($mailType)
                ->order('sorting')
                ->fetchPairs('id', 'title');
        }
        $this->sendJson($variants);
    }

    public function createComponentNewBatchForm()
    {
        $form = $this->newBatchFormFactory->create(isset($this->params['id']) ? (int)$this->params['id'] : null);

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

    protected function createComponentTemplateStats()
    {
        return new Multiplier(function ($templateId) {
            $templateStats = $this->sendingStatsFactory->create();

            $template = $this->templatesRepository->find($templateId);
            $templateStats->addTemplate($template);
            $templateStats->showConversions();

            return $templateStats;
        });
    }

    protected function createComponentJobBatchTemplateStats()
    {
        return new Multiplier(function ($jobBatchTemplateId) {
            $stats = $this->sendingStatsFactory->create();

            $jobBatchTemplate = $this->batchTemplatesRepository->find($jobBatchTemplateId);
            $stats->addJobBatchTemplate($jobBatchTemplate);
            $stats->showConversions();

            return $stats;
        });
    }

    protected function createComponentJobStats()
    {
        $templateStats = $this->sendingStatsFactory->create();

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

    public function createComponentBatchExperimentEvaluation()
    {
        return $this->batchExperimentEvaluationFactory->create();
    }
}
