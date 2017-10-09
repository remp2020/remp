<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Multiplier;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Components\ITemplateStatsFactory;
use Remp\MailerModule\Forms\JobFormFactory;
use Remp\MailerModule\Forms\NewBatchFormFactory;
use Remp\MailerModule\Forms\NewTemplateFormFactory;
use Remp\MailerModule\Job\MailCache;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\LogEventsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\Segment\Aggregator;
use Remp\MailerModule\Segment\SegmentException;

final class JobPresenter extends BasePresenter
{
    /** @var JobsRepository */
    private $jobsRepository;

    /** @var BatchesRepository */
    private $batchesRepository;

    /** @var BatchTemplatesRepository */
    private $batchTemplatesRepository;

    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var LogsRepository */
    private $logsRepository;

    /** @var JobFormFactory */
    private $jobFormFactory;

    /** @var NewBatchFormFactory */
    private $newBatchFormFactory;

    /** @var NewTemplateFormFactory */
    private $newTemplateFormFactory;

    private $userSubscriptionsRepository;

    private $logEventsRepository;

    /** @var  Aggregator */
    private $segmentAggregator;

    /** @var MailCache */
    private $mailCache;

    /** @var ILatteFactory  */
    private $latteFactory;

    public function __construct(
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        TemplatesRepository $templatesRepository,
        LogsRepository $logsRepository,
        JobFormFactory $jobFormFactory,
        NewBatchFormFactory $newBatchFormFactory,
        NewTemplateFormFactory $newTemplateFormFactory,
        UserSubscriptionsRepository $userSubscriptionsRepository,
        LogEventsRepository $logEventsRepository,
        Aggregator $segmentAggregator,
        MailCache $mailCache,
        ILatteFactory $latteFactory
    ) {
        parent::__construct();
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->templatesRepository = $templatesRepository;
        $this->logsRepository = $logsRepository;
        $this->jobFormFactory = $jobFormFactory;
        $this->newBatchFormFactory = $newBatchFormFactory;
        $this->newTemplateFormFactory = $newTemplateFormFactory;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->logEventsRepository = $logEventsRepository;
        $this->segmentAggregator = $segmentAggregator;
        $this->mailCache = $mailCache;
        $this->latteFactory = $latteFactory;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('defaultJsonData'))
            ->setColSetting('created_at', ['header' => 'created at', 'render' => 'date'])
            ->setColSetting('segment', ['orderable' => false])
            ->setColSetting('batches', ['orderable' => false])
            ->setColSetting('sent_count', ['header' => 'sent', 'orderable' => false])
            ->setColSetting('opened_count', ['header' => 'opened', 'orderable' => false])
            ->setColSetting('clicked_count', ['header' => 'clicked', 'orderable' => false])
//            ->setColSetting('unsubscribed_count', ['header' => 'unsubscribed', 'orderable' => false])
            ->setRowLinkAction('show')
            ->setRowAction('show', 'palette-Cyan zmdi-eye')
            ->setTableSetting('add-params', Json::encode(['templateId' => $this->getParameter('id')]))
            ->setTableSetting('order', Json::encode([[0, 'DESC']]));

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $jobsCount = $this->jobsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], null, null)
            ->count('*');

        $jobs = $this->jobsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $request['length'], $request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->jobsRepository->totalCount(),
            'recordsFiltered' => $jobsCount,
            'data' => []
        ];

        $segments = [];
        try {
            $segmentList = $this->segmentAggregator->list();
            array_walk($segmentList, function ($segment) use (&$segments) {
                $segments[$segment['code']] = $segment['name'];
            });
        } catch (SegmentException $e) {
            $result['error'] = 'Unable to fetch list of segments, please check the application configuration.';
        }

        /** @var ActiveRow $job */
        foreach ($jobs as $job) {
            $latte = $this->latteFactory->create();
            $status = $latte->renderToString(__DIR__  . '/templates/Job/_job_status.latte', ['job' => $job]);
            $sentCount = $latte->renderToString(__DIR__  . '/templates/Job/_sent_count.latte', ['job' => $job]);
            $openedCount = $latte->renderToString(__DIR__  . '/templates/Job/_opened_count.latte', ['job' => $job]);
            $clickedCount = $latte->renderToString(__DIR__  . '/templates/Job/_clicked_count.latte', ['job' => $job]);

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
//                $unsubscribedCount,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderShow($id)
    {
        $job = $this->jobsRepository->find($id);

        try {
            $segmentList = $this->segmentAggregator->list();
            array_walk($segmentList, function ($segment) use (&$job) {
                if ($segment['code'] == $job->segment_code) {
                    $this->template->segment = $segment;
                }
            });
        } catch (SegmentException $e) {
            $this->flashMessage('Unable to fetch list of segments, please check the application configuration.', 'danger');
        }

        $this->template->job = $job;
        $this->template->total_sent = $this->logsRepository->getJobLogs($job->id)->count('*');
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
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATE_READY]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleSetBatchSend($id)
    {
        $batch = $this->batchesRepository->find($id);
        $priority = $this->batchesRepository->getBatchPriority($batch);
        $this->mailCache->restartQueue($batch->id, $priority);
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATE_SENDING]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleSetBatchUserStop($id)
    {
        $batch = $this->batchesRepository->find($id);
        $this->mailCache->pauseQueue($batch->id);
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATE_USER_STOP]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleSetBatchCreated($id)
    {
        $batch = $this->batchesRepository->find($id);
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATE_CREATED]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleRemoveBatch($id)
    {
        $batch = $this->batchesRepository->find($id);

        $this->batchTemplatesRepository->deleteByBatchId($batch->id);
        $this->batchesRepository->delete($batch);

        $this->flashMessage('Batch was removed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function createComponentJobForm()
    {
        $form = $this->jobFormFactory->create();

        $presenter = $this;
        $this->jobFormFactory->onSuccess = function ($job) use ($presenter) {
            $presenter->flashMessage('Job was created');
            $presenter->redirect('Show', $job->id);
        };

        return $form;
    }

    public function createComponentNewBatchForm()
    {
        $form = $this->newBatchFormFactory->create($this->params['id']);

        $presenter = $this;
        $this->newBatchFormFactory->onSuccess = function ($job) use ($presenter) {
            $presenter->flashMessage('Batch was added');
            $presenter->redirect('Show', $job->id);
        };

        return $form;
    }

    public function createComponentNewTemplateForm()
    {
        return new Multiplier(function ($batchId) {
            $form = $this->newTemplateFormFactory->create($batchId);

            $presenter = $this;
            $this->newTemplateFormFactory->onSuccess = function ($job) use ($presenter) {
                $presenter->flashMessage('Email was added');
                $presenter->redirect('Show', $job->id);
            };

            return $form;
        });
    }

    protected function createComponentTemplateStats(ITemplateStatsFactory $factory)
    {

        return new Multiplier(function ($templateId) use ($factory) {
            $templateStats = $factory->create();

            $template = $this->templatesRepository->find($templateId);
            $templateStats->setTemplate($template);
            $templateStats->showConversions();

            return $templateStats;
        });
    }

    protected function createComponentJobStats(ITemplateStatsFactory $factory)
    {
        $templateStats = $factory->create();

        $batches = $this->batchTemplatesRepository->findByJobId($this->params['id']);
        foreach ($batches as $batch) {
            $template = $this->templatesRepository->find($batch->mail_template_id);
            $templateStats->setTemplate($template);
        }
        $templateStats->showConversions();

        return $templateStats;
    }
}
