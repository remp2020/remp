<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Forms\ListFormFactory;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\MailTypeStatsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Tomaj\Hermes\Emitter;
use DateTime;
use DateInterval;
use IntlDateFormatter;
use Remp\MailerModule\Formatters\DateFormatterFactory;

final class ListPresenter extends BasePresenter
{
    /** @var ListsRepository */
    private $listsRepository;

    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var MailTypeStatsRepository */
    private $mailTypeStatsRepository;

    /** @var ListFormFactory */
    private $listFormFactory;

    /** @var IntlDateFormatter */
    private $dateFormatter;

    /** @var Emitter */
    private $emitter;

    public function __construct(
        ListsRepository $listsRepository,
        TemplatesRepository $templatesRepository,
        MailTypeStatsRepository $mailTypeStatsRepository,
        DateFormatterFactory $dateFormatterFactory,
        ListFormFactory $listFormFactory,
        Emitter $emitter
    ) {
        parent::__construct();

        $this->dateFormatter = $dateFormatterFactory
            ->getInstance(IntlDateFormatter::SHORT, IntlDateFormatter::NONE);

        $this->listsRepository = $listsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->mailTypeStatsRepository = $mailTypeStatsRepository;
        $this->listFormFactory = $listFormFactory;
        $this->emitter = $emitter;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setColSetting('category', [
                'visible' => false,
                'priority' => 1,
            ])
            ->setColSetting('title', [
                'priority' => 1,
            ])
            ->setColSetting('code', [
                'priority' => 2,
            ])
            ->setColSetting('subscribed', [
                'render' => 'number',
                'priority' => 2,
            ])
            ->setColSetting('auto_subscribe', [
                'header' => 'auto subscribe',
                'render' => 'boolean',
                'priority' => 3,
            ])
            ->setColSetting('locked', [
                'render' => 'boolean',
                'priority' => 2,
            ])
            ->setColSetting('is_public', [
                'header' => 'public',
                'render' => 'boolean',
                'priority' => 3,
            ])
            ->setAllColSetting('orderable', false)
            ->setRowAction('show', 'palette-Cyan zmdi-eye', 'Show list')
            ->setRowAction('edit', 'palette-Cyan zmdi-edit', 'Edit layout')
            ->setTableSetting('displayNavigation', false)
            ->setTableSetting('rowGroup', 0);

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $lists = $this->listsRepository->tableFilter();
        $listsCount = $lists->count('*');

        $result = [
            'recordsTotal' => $listsCount,
            'recordsFiltered' => $listsCount,
            'data' => []
        ];

        /** @var ActiveRow $list */
        foreach ($lists as $list) {
            $showUrl = $this->link('Show', $list->id);
            $editUrl = $this->link('Edit', $list->id);
            $result['data'][] = [
                'actions' => [
                    'show' => $showUrl,
                    'edit' => $editUrl,
                ],
                $list->type_category ? $list->type_category->title : null,
                "<a href='{$showUrl}'>{$list->title}</a>",
                $list->code,
                $list->related('mail_user_subscriptions')->where(['subscribed' => true])->count('*'),
                $list->auto_subscribe,
                $list->locked,
                $list->is_public,
            ];
        }

        $this->presenter->sendJson($result);
    }

    public function renderShow($id)
    {
        $list = $this->listsRepository->find($id);
        if (!$list) {
            throw new BadRequestException();
        }

        $this->template->list = $list;
        $this->template->variants = $list->related('mail_type_variants')->order('sorting');
        $this->template->stats = [
            'subscribed' => $list->related('mail_user_subscriptions')->where(['subscribed' => true])->count('*'),
            'un-subscribed' => $list->related('mail_user_subscriptions')->where(['subscribed' => false])->count('*'),
        ];

        $this->prepareDetailSubscribersGraphData($id);
    }

    public function prepareDetailSubscribersGraphData($id)
    {
        $labels = [];
        $numOfDays = 30;
        $now = new DateTime();
        $from = (clone $now)->sub(new DateInterval('P' . $numOfDays . 'D'));

        // fill graph columns
        for ($i = $numOfDays; $i >= 0; $i--) {
            $labels[] = $this->dateFormatter->format(strtotime('-' . $i . ' days'));
        }

        $mailType = $this->listsRepository->find($id);

        $dataSet = [
            'label' => $mailType->title,
            'data' => array_fill(0, $numOfDays, 0),
            'fill' => false,
            'borderColor' => 'rgb(75, 192, 192)',
            'lineTension' => 0.5
        ];

        $data = $this->mailTypeStatsRepository->getDashboardDetailData($id, $from, $now);

        // parse sent mails by type data to chart.js format
        foreach ($data as $row) {
            $foundAt = array_search(
                $this->dateFormatter->format($row->created_date),
                $labels
            );

            if ($foundAt !== false) {
                $dataSet['data'][$foundAt] = $row->count;
            }
        }

        $this->template->dataSet = $dataSet;
        $this->template->labels = $labels;
    }

    public function renderEdit($id)
    {
        $list = $this->listsRepository->find($id);
        if (!$list) {
            throw new BadRequestException();
        }

        $this->template->list = $list;
    }

    public function createComponentDataTableTemplates(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('templateJsonData'))
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setColSetting('subject', [
                'priority' => 1,
            ])
            ->setColSetting('opened', [
                'priority' => 2,
            ])
            ->setColSetting('clicked', [
                'priority' => 2,
            ])
            ->setRowAction('show', 'palette-Cyan zmdi-eye', 'Show template')
            ->setTableSetting('add-params', Json::encode(['listId' => $this->getParameter('id')]))
            ->setTableSetting('order', Json::encode([[0, 'DESC']]));

        return $dataTable;
    }

    public function renderTemplateJsonData()
    {
        $request = $this->request->getParameters();

        $templatesCount = $this->templatesRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $request['listId'], null, null)
            ->count('*');

        $templates = $this->templatesRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $request['listId'], $request['length'], $request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->templatesRepository->totalCount(),
            'recordsFiltered' => $templatesCount,
            'data' => []
        ];

        /** @var ActiveRow $template */
        foreach ($templates as $template) {
            $opened = 0;
            $clicked = 0;
            /** @var ActiveRow $jobBatchTemplate */
            foreach ($template->related('mail_job_batch_template') as $jobBatchTemplate) {
                $opened += $jobBatchTemplate->opened;
                $clicked += $jobBatchTemplate->clicked;
            }
            $result['data'][] = [
                'actions' => [
                    'show' => $this->link('Template:Show', $template->id),
                ],
                $template->created_at,
                $template->subject,
                $opened,
                $clicked,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function createComponentListForm()
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = intval($this->params['id']);
        }

        $form = $this->listFormFactory->create($id);

        $presenter = $this;
        $this->listFormFactory->onCreate = function ($list) use ($presenter) {
            $this->emitter->emit(new HermesMessage('list-created', [
                'list_id' => $list->id,
            ]));

            $presenter->flashMessage('Newsletter list was created');
            $presenter->redirect('Show', $list->id);
        };

        $this->listFormFactory->onUpdate = function ($list) use ($presenter) {
            $this->emitter->emit(new HermesMessage('list-updated', [
                'list_id' => $list->id,
            ]));

            $presenter->flashMessage('Newsletter list was updated');
            $presenter->redirect('Edit', $list->id);
        };

        return $form;
    }

    public function handleRenderSorting($categoryId, $sorting)
    {
        // set sorting value
        $this['listForm']['sorting']->setValue($sorting);

        // handle newsletter list category change
        if ($this['listForm']['mail_type_category_id']->getValue() !== $categoryId) {
            $lists = $this->listsRepository->findByCategory($categoryId);
            if ($listId = $this['listForm']['id']->getValue()) {
                $lists = $lists->where('id != ?', $listId);
            }

            $lists = $lists->order('sorting ASC')->fetchPairs('sorting', 'title');
            $this['listForm']['sorting_after']->setItems($lists);
        }

        $this->redrawControl('wrapper');
        $this->redrawControl('sortingAfterSnippet');
    }
}
