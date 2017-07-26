<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\UI\Multiplier;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Components\ITemplateStatsFactory;
use Remp\MailerModule\Forms\JobFormFactory;
use Remp\MailerModule\Forms\NewBatchFormFactory;
use Remp\MailerModule\Forms\NewTemplateFormFactory;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

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


    public function __construct(
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        TemplatesRepository $templatesRepository,
        LogsRepository $logsRepository,
        JobFormFactory $jobFormFactory,
        NewBatchFormFactory $newBatchFormFactory,
        NewTemplateFormFactory $newTemplateFormFactory
    )
    {
        parent::__construct();
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->templatesRepository = $templatesRepository;
        $this->logsRepository = $logsRepository;
        $this->jobFormFactory = $jobFormFactory;
        $this->newBatchFormFactory = $newBatchFormFactory;
        $this->newTemplateFormFactory = $newTemplateFormFactory;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('defaultJsonData'))
            ->setColSetting('created_at', ['header' => 'created at', 'render' => 'date'])
            ->setColSetting('segment', ['orderable' => false])
            ->setColSetting('status', [])
            ->setColSetting('emails_sent_count', ['header' => 'sent emails', 'orderable' => false])
            ->setRowLink($this->link('Show', 'RowId'))
            ->setTableSetting('add-params', Json::encode(['templateId' => $this->getParameter('id')]))
            ->setTableSetting('order', Json::encode([[0, 'DESC']]));

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $jobsCount = $this->jobsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], null, null, $request['templateId'])
            ->count('*');

        $jobs = $this->jobsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $request['length'], $request['start'], $request['templateId'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->jobsRepository->totalCount(),
            'recordsFiltered' => $jobsCount,
            'data' => []
        ];

        foreach ($jobs as $job) {
            $result['data'][] = [
                'RowId' => $job->id,
                $job->created_at,
                $job->segment->name,
                $job->status,
                $job->emails_sent_count,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderShow($id)
    {
        $job = $this->jobsRepository->find($id);

        $this->template->job = $job;
        $this->template->total_sent = $this->logsRepository->getJobLogs($job->id)->count('*');
    }

    public function renderEdit($id)
    {
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
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATE_SENDING]);

        $this->flashMessage('Status of batch was changed.');
        $this->redirect('Show', $batch->job_id);
    }

    public function handleSetBatchUserStop($id)
    {
        $batch = $this->batchesRepository->find($id);
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
