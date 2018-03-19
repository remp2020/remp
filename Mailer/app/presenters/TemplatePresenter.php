<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Components\ISendingStatsFactory;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Forms\TemplateFormFactory;
use Remp\MailerModule\Forms\TemplateTestFormFactory;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

final class TemplatePresenter extends BasePresenter
{
    private $templatesRepository;

    private $logsRepository;

    private $templateFormFactory;

    private $templateTestFormFactory;

    private $layoutsRepository;

    private $listsRepository;

    public function __construct(
        TemplatesRepository $templatesRepository,
        LogsRepository $logsRepository,
        TemplateFormFactory $templateFormFactory,
        TemplateTestFormFactory $templateTestFormFactory,
        LayoutsRepository $layoutsRepository,
        ListsRepository $listsRepository
    ) {
    
        parent::__construct();
        $this->templatesRepository = $templatesRepository;
        $this->logsRepository = $logsRepository;
        $this->templateFormFactory = $templateFormFactory;
        $this->templateTestFormFactory = $templateTestFormFactory;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $mailTypePairs = $this->listsRepository->all()->fetchPairs('id', 'title');

        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setColSetting('created_at', ['header' => 'created at', 'render' => 'date'])
            ->setColSetting('code')
            ->setColSetting('subject')
            ->setColSetting('type', ['orderable' => false, 'filter' => $mailTypePairs])
            ->setColSetting('opened')
            ->setColSetting('clicked')
            ->setRowAction('show', 'palette-Cyan zmdi-eye')
            ->setRowAction('edit', 'palette-Cyan zmdi-edit')
            ->setRowAction('duplicate', 'palette-Cyan zmdi-copy')
            ->setTableSetting('order', Json::encode([[0, 'DESC']]));

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $listIds = null;
        foreach ($request['columns'] as $column) {
            if ($column['name'] !== 'type') {
                continue;
            }
            if (!empty($column['search']['value'])) {
                $listIds = explode(',', $column['search']['value']);
            }
            break;
        }

        $templatesCount = $this->templatesRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $listIds)
            ->count('*');

        $templates = $this->templatesRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $listIds, $request['length'], $request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->templatesRepository->totalCount(),
            'recordsFiltered' => $templatesCount,
            'data' => []
        ];

        /** @var ActiveRow $template */
        foreach ($templates as $template) {
            $editUrl = $this->link('Edit', $template->id);
            $result['data'][] = [
                'actions' => [
                    'show' => $this->link('Show', $template->id),
                    'edit' => $this->link('Edit', $template->id),
                    'duplicate' => $this->link('Duplicate!', $template->id),
                ],
                $template->created_at,
                $template->code,
                "<a href='{$editUrl}'>{$template->subject}</a>",
                $template->type->title,
                $template->related('mail_job_batch_template')->sum('opened'),
                $template->related('mail_job_batch_template')->sum('clicked'),
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderShow($id)
    {
        $template = $this->templatesRepository->find($id);
        if (!$template) {
            throw new BadRequestException();
        }

        $this->template->mailTemplate = $template;
    }

    public function createComponentDataTableLogs(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('logJsonData'))
            ->setColSetting('created_at', ['header' => 'sent at', 'render' => 'date'])
            ->setColSetting('email', ['orderable' => false])
            ->setColSetting('subject', ['orderable' => false])
            ->setColSetting('events', ['render' => 'badge', 'orderable' => false])
            ->setTableSetting('remove-search')
            ->setTableSetting('order', Json::encode([[2, 'DESC']]))
            ->setTableSetting('add-params', Json::encode(['templateId' => $this->getParameter('id')]));

        return $dataTable;
    }

    public function renderLogJsonData()
    {
        $request = $this->request->getParameters();

        $logsCount = $this->logsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], null, null, $request['templateId'])
            ->count('*');

        $logs = $this->logsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $request['length'], $request['start'], $request['templateId'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->logsRepository->totalCount(),
            'recordsFiltered' => $logsCount,
            'data' => []
        ];

        foreach ($logs as $log) {
            $result['data'][] = [
                'RowId' => $log->id,
                $log->created_at,
                $log->email,
                $log->subject,
                [
                    isset($log->delivered_at) ? ['text' => 'Delivered', 'class' => 'palette-Cyan-700 bg'] : '',
                    isset($log->dropped_at) ? ['text' => 'Dropped', 'class' => 'palette-Cyan-700 bg'] : '',
                    isset($log->spam_complained_at) ? ['text' => 'Span', 'class' => 'palette-Cyan-700 bg'] : '',
                    isset($log->hard_bounced_at) ? ['text' => 'Hard Bounce', 'class' => 'palette-Cyan-700 bg'] : '',
                    isset($log->clicked_at) ? ['text' => 'Clicked', 'class' => 'palette-Cyan-700 bg'] : '',
                    isset($log->opened_at) ? ['text' => 'Opened', 'class' => 'palette-Cyan-700 bg'] : '',
                ],
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderNew()
    {
        $layouts = $this->layoutsRepository->getTable()->fetchPairs('id', 'layout_html');
        $this->template->layouts = $layouts;
    }

    public function renderEdit($id)
    {
        $template = $this->templatesRepository->find($id);
        if (!$template) {
            throw new BadRequestException();
        }
        $layouts = $this->layoutsRepository->getTable()->fetchPairs('id', 'layout_html');

        $this->template->mailTemplate = $template;
        $this->template->layouts = $layouts;
    }

    public function renderPreview($id, $type = 'html')
    {
        $template = $this->templatesRepository->find($id);
        if (!$template) {
            throw new BadRequestException();
        }

        $mailContentGenerator = new ContentGenerator($template, $template->mail_layout, null);
        if ($type == 'html') {
            $this->template->content = $mailContentGenerator->getHtmlBody([]);
        } else {
            $this->template->content = "<pre>{$mailContentGenerator->getTextBody([])}</pre>";
        }
    }

    public function handleDuplicate($id)
    {
        $template = $this->templatesRepository->find($id);
        $newTemplate = $this->templatesRepository->duplicate($template);
        $this->flashMessage('Email was duplicated.');
        $this->redirect('edit', $newTemplate->id);
    }

    public function createComponentTemplateForm()
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = (int)$this->params['id'];
        }

        $form = $this->templateFormFactory->create($id);

        $presenter = $this;
        $this->templateFormFactory->onCreate = function ($template, $buttonSubmitted) use ($presenter) {
            $presenter->flashMessage('Email was created');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $template->id);
        };
        $this->templateFormFactory->onUpdate = function ($template, $buttonSubmitted) use ($presenter) {
            $presenter->flashMessage('Email was updated');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $template->id);
        };

        return $form;
    }

    public function createComponentTemplateTestForm()
    {
        $form = $this->templateTestFormFactory->create($this->params['id']);

        $presenter = $this;
        $this->templateTestFormFactory->onSuccess = function ($template) use ($presenter) {
            $presenter->flashMessage('Email was sent');
            $presenter->redirect('Show', $template->id);
        };

        return $form;
    }

    protected function createComponentTemplateStats(ISendingStatsFactory $factory)
    {
        $templateStats = $factory->create();

        if (isset($this->params['id'])) {
            $template = $this->templatesRepository->find($this->params['id']);
            $templateStats->addTemplate($template);
            $templateStats->showTotal();
        }

        return $templateStats;
    }
}
