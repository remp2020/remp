<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Http\Discovery\Exception\NotFoundException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Repositories\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Components\SendingStats\ISendingStatsFactory;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Forms\TemplateFormFactory;
use Remp\MailerModule\Forms\TemplateTestFormFactory;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

final class TemplatePresenter extends BasePresenter
{
    private $templatesRepository;

    private $logsRepository;

    private $templateFormFactory;

    private $templateTestFormFactory;

    private $layoutsRepository;

    private $snippetsRepository;

    private $listsRepository;

    private $contentGenerator;

    private $dataTableFactory;

    private $sendingStatsFactory;

    private $generatorInputFactory;

    public function __construct(
        TemplatesRepository $templatesRepository,
        LogsRepository $logsRepository,
        TemplateFormFactory $templateFormFactory,
        TemplateTestFormFactory $templateTestFormFactory,
        LayoutsRepository $layoutsRepository,
        SnippetsRepository $snippetsRepository,
        ListsRepository $listsRepository,
        ContentGenerator $contentGenerator,
        DataTableFactory $dataTableFactory,
        ISendingStatsFactory $sendingStatsFactory,
        GeneratorInputFactory $generatorInputFactory
    ) {
        parent::__construct();
        $this->templatesRepository = $templatesRepository;
        $this->logsRepository = $logsRepository;
        $this->templateFormFactory = $templateFormFactory;
        $this->templateTestFormFactory = $templateTestFormFactory;
        $this->layoutsRepository = $layoutsRepository;
        $this->snippetsRepository = $snippetsRepository;
        $this->listsRepository = $listsRepository;
        $this->contentGenerator = $contentGenerator;
        $this->dataTableFactory = $dataTableFactory;
        $this->sendingStatsFactory = $sendingStatsFactory;
        $this->generatorInputFactory = $generatorInputFactory;
    }

    public function createComponentDataTableDefault(): DataTable
    {
        $mailTypePairs = $this->listsRepository->all()->fetchPairs('id', 'title');

        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setColSetting('code', [
                'priority' => 2,
            ])
            ->setColSetting('subject', [
                'priority' => 1,
            ])
            ->setColSetting('type', [
                'orderable' => false,
                'filter' => $mailTypePairs,
                'priority' => 1,
            ])
            ->setColSetting('opened', [
                'priority' => 3,
                'render' => 'number',
                'class' => 'text-right',
            ])
            ->setColSetting('clicked', [
                'priority' => 3,
                'render' => 'number',
                'class' => 'text-right',
            ])
            ->setRowAction('show', 'palette-Cyan zmdi-eye', 'Show template')
            ->setRowAction('edit', 'palette-Cyan zmdi-edit', 'Edit template')
            ->setRowAction('duplicate', 'palette-Cyan zmdi-copy', 'Duplicate template')
            ->setTableSetting('order', Json::encode([[0, 'DESC']]))
            ->setTableSetting('exportColumns', [0,1,2,3,4,5]);

        return $dataTable;
    }

    public function renderDefaultJsonData(): void
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

        $query = $request['search']['value'];
        $order = $request['columns'][$request['order'][0]['column']]['name'];
        $orderDir = $request['order'][0]['dir'];
        $templatesCount = $this->templatesRepository
            ->tableFilter($query, $order, $orderDir, $listIds)
            ->count('*');

        $templates = $this->templatesRepository
            ->tableFilter($query, $order, $orderDir, $listIds, (int)$request['length'], (int)$request['start'])
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
                $template->related('mail_job_batch_template')->sum('opened') + $template->related('mail_logs', 'mail_template_id')->where('mail_job_id IS NULL')->count('opened_at'),
                $template->related('mail_job_batch_template')->sum('clicked') + $template->related('mail_logs', 'mail_template_id')->where('mail_job_id IS NULL')->count('clicked_at'),
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderShow($id): void
    {
        $template = $this->templatesRepository->find($id);
        if (!$template) {
            throw new BadRequestException();
        }

        $this->template->mailTemplate = $template;
    }

    public function actionShowByCode($id): void
    {
        $template = $this->templatesRepository->getByCode($id);
        if (!$template) {
            throw new NotFoundException('', 404);
        }
        $this->redirect('show', ['id' => $template->id]);
    }

    public function createComponentDataTableLogs(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('logJsonData'))
            ->setColSetting('created_at', [
                'header' => 'sent at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setColSetting('email', [
                'orderable' => false,
                'priority' => 1,
            ])
            ->setColSetting('subject', [
                'orderable' => false,
                'priority' => 1,
            ])
            ->setColSetting('events', [
                'render' => 'badge',
                'orderable' => false,
                'priority' => 2,
            ])
            ->setTableSetting('remove-search')
            ->setTableSetting('order', Json::encode([[2, 'DESC']]))
            ->setTableSetting('add-params', Json::encode(['templateId' => $this->getParameter('id')]));

        return $dataTable;
    }

    public function renderLogJsonData(): void
    {
        $request = $this->request->getParameters();

        $logsCount = $this->logsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], null, null, (int)$request['templateId'])
            ->count('*');

        $logs = $this->logsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], (int)$request['length'], (int)$request['start'], (int)$request['templateId'])
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

    public function renderNew(): void
    {
        $layouts = $this->layoutsRepository->getTable()->fetchPairs('id', 'layout_html');
        $snippets = $this->snippetsRepository->getTable()->select('code')->group('code')->fetchAssoc('code');
        $lists = $this->listsRepository->all()->fetchAssoc('id');

        $this->template->layouts = $layouts;
        $this->template->snippets = $snippets;
        $this->template->lists = $lists;
        $this->template->templateEditor = $this->environmentConfig->getParam('template_editor', 'codemirror');
    }

    public function renderEdit($id): void
    {
        $template = $this->templatesRepository->find($id);
        if (!$template) {
            throw new BadRequestException();
        }
        $layouts = $this->layoutsRepository->getTable()->fetchAssoc('id');
        $snippets = $this->snippetsRepository->getTable()->select('code')->group('code')->fetchAssoc('code');
        $lists = $this->listsRepository->all()->fetchAssoc('id');

        $this->template->mailTemplate = $template;
        $this->template->layouts = $layouts;
        $this->template->snippets = $snippets;
        $this->template->lists = $lists;
        $this->template->templateEditor = $this->environmentConfig->getParam('template_editor', 'codemirror');
    }

    public function renderPreview($id, $type = 'html'): void
    {
        $template = $this->templatesRepository->find($id);
        if (!$template) {
            throw new BadRequestException();
        }

        $mailContent = $this->contentGenerator->render($this->generatorInputFactory->create($template));
        $this->template->content = ($type === 'html')
            ? $mailContent->html()
            : "<pre>{$mailContent->text()}</pre>";
    }

    public function handleDuplicate($id): void
    {
        $template = $this->templatesRepository->find($id);
        $newTemplate = $this->templatesRepository->duplicate($template);
        $this->flashMessage('Email was duplicated.');
        $this->redirect('edit', $newTemplate->id);
    }

    public function createComponentTemplateForm(): Form
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

    public function createComponentTemplateTestForm(): Form
    {
        $form = $this->templateTestFormFactory->create((int)$this->params['id']);

        $presenter = $this;
        $this->templateTestFormFactory->onSuccess = function ($template) use ($presenter) {
            $presenter->flashMessage('Email was sent');
            $presenter->redirect('Show', $template->id);
        };

        return $form;
    }

    protected function createComponentTemplateStats(): Control
    {
        $templateStats = $this->sendingStatsFactory->create();

        if (isset($this->params['id'])) {
            $template = $this->templatesRepository->find($this->params['id']);
            $templateStats->addTemplate($template);
            $templateStats->showTotal();
        }

        return $templateStats;
    }
}
