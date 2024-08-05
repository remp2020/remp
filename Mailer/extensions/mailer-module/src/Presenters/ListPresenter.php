<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use DateInterval;
use DateTimeZone;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Forms\DuplicateListFormFactory;
use Remp\MailerModule\Forms\ListFormFactory;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Hermes\RedisDriver;
use Remp\MailerModule\Models\ChartTrait;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\MailTemplateStatsRepository;
use Remp\MailerModule\Repositories\MailTypeStatsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\Hermes\Emitter;

final class ListPresenter extends BasePresenter
{
    use ChartTrait;

    public function __construct(
        private readonly ListsRepository $listsRepository,
        private readonly TemplatesRepository $templatesRepository,
        private readonly MailTypeStatsRepository $mailTypeStatsRepository,
        private readonly MailTemplateStatsRepository $mailTemplateStatsRepository,
        private readonly UserSubscriptionsRepository $userSubscriptionsRepository,
        private readonly ListFormFactory $listFormFactory,
        private readonly DuplicateListFormFactory $duplicateListFormFactory,
        private readonly ListVariantsRepository $listVariantsRepository,
        private readonly Emitter $emitter,
        private readonly DataTableFactory $dataTableFactory,
    ) {
        parent::__construct();
    }

    public function createComponentDataTableDefault(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setColSetting('category', [
                'visible' => false,
                'priority' => 1,
            ])
            ->setColSetting('title', [
                'priority' => 1,
                'render' => 'link'
            ])
            ->setColSetting('code', [
                'priority' => 2,
                'render' => 'text',
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
            ->setColSetting('public_listing', [
                'header' => 'publicly listed',
                'render' => 'boolean',
                'priority' => 3,
            ])
            ->setAllColSetting('orderable', false)
            ->setRowAction('show', 'palette-Cyan zmdi-eye', 'Show list')
            ->setRowAction('edit', 'palette-Cyan zmdi-edit', 'Edit list')
            ->setRowAction('duplicate', 'palette-Cyan zmdi-copy', 'Duplicate list')
            ->setRowAction('delete', 'palette-Red zmdi-delete', 'Delete list', [
                'onclick' => 'return confirm(\'Are you sure you want to delete this item?\');'
            ])
            ->setRowAction('sentEmailsDetail', 'palette-Cyan zmdi-chart', 'List stats')
            ->setTableSetting('displayNavigation', false)
            ->setTableSetting('rowGroup', 0)
            ->setTableSetting('rowGroupActions', 'groupActions');

        return $dataTable;
    }

    public function renderDefaultJsonData(): void
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
            $duplicateUrl = $this->link('Duplicate', $list->id);
            $deleteUrl = $this->link('Delete!', $list->id);
            $sentEmailsDetail = $this->link('sentEmailsDetail', $list->id);
            $editCategory = $this->link('ListCategory:edit', $list->mail_type_category_id);
            $result['data'][] = [
                'actions' => [
                    'show' => $showUrl,
                    'edit' => $editUrl,
                    'duplicate' => $duplicateUrl,
                    'delete' => $deleteUrl,
                    'sentEmailsDetail' => $sentEmailsDetail,
                ],
                $list->type_category->title ?? null,
                [
                    'url' => $showUrl,
                    'text' => $list->title,
                ],
                $list->code,
                $list->related('mail_user_subscriptions')->where(['subscribed' => true])->count('*'),
                $list->auto_subscribe,
                $list->locked,
                $list->public_listing,
                'groupActions' => [
                    [
                        'url' => $editCategory,
                        'title' => 'Edit category',
                        'icon' => 'palette-Cyan zmdi-edit',
                    ]
                ]
            ];
        }

        $this->presenter->sendJson($result);
    }

    public function renderShow($id): void
    {
        $list = $this->listsRepository->find($id);
        if (!$list) {
            throw new BadRequestException();
        }

        $week = new DateTime('-7 days');
        $month = new DateTime('-30 days');

        $this->template->stats = [
            'subscribed' => $list->related('mail_user_subscriptions')
                ->where(['subscribed' => true])
                ->count('*'),
            'un-subscribed' => $list->related('mail_user_subscriptions')
                ->where(['subscribed' => false])
                ->count('*'),
            'opened' => [
                '7-days' => $this->mailTemplateStatsRepository->byMailTypeId($list->id)
                    ->where('date > DATE(?)', $week)
                    ->select('SUM(mail_template_stats.opened) AS opened')
                    ->fetch()->opened ?? 0,
                '30-days' => $this->mailTemplateStatsRepository->byMailTypeId($list->id)
                    ->where('date > DATE(?)', $month)
                    ->select('SUM(mail_template_stats.opened) AS opened')
                    ->fetch()->opened ?? 0,
            ],
            'clicked' => [
                '7-days' => $this->mailTemplateStatsRepository->byMailTypeId($list->id)
                    ->where('date > DATE(?)', $week)
                    ->select('SUM(mail_template_stats.clicked) AS opened')
                    ->fetch()->opened ?? 0,
                '30-days' => $this->mailTemplateStatsRepository->byMailTypeId($list->id)
                    ->where('date > DATE(?)', $month)
                    ->select('SUM(mail_template_stats.clicked) AS opened')
                    ->fetch()->opened ?? 0,
            ]
        ];

        $this->template->list = $list;

        $this->prepareDetailSubscribersGraphData($id);
    }

    public function prepareDetailSubscribersGraphData($id): void
    {
        $labels = [];
        $numOfDays = 30;
        $now = new DateTime();
        $from = (clone $now)->sub(new DateInterval('P' . $numOfDays . 'D'));

        // fill graph columns
        for ($i = $numOfDays; $i >= 0; $i--) {
            $labels[] = DateTime::from('-' . $i . ' days')->format('Y-m-d');
        }

        $mailType = $this->listsRepository->find($id);

        $dataSet = [
            'label' => $mailType->title,
            'data' => array_fill(0, $numOfDays, 0),
            'backgroundColor' => '#008494',
        ];

        $data = $this->mailTypeStatsRepository->getDashboardDetailData($id, $from, $now);
        $minRow = reset($data);
        foreach ($data as $row) {
            $minRow = $row->count < $minRow->count ? $row : $minRow;
        }

        // parse sent mails by type data to chart.js format
        foreach ($data as $row) {
            $foundAt = array_search(
                DateTime::from($row->created_date)->format('Y-m-d'),
                $labels
            );

            if ($foundAt !== false) {
                $dataSet['data'][$foundAt] = $row->count;
            }
        }

        $this->template->suggestedMin = $this->getChartSuggestedMin([$dataSet['data']]);
        $this->template->dataSet = $dataSet;
        $this->template->labels = $labels;
    }

    public function renderEdit($id): void
    {
        $list = $this->listsRepository->find($id);
        if (!$list) {
            throw new BadRequestException();
        }

        $this->template->list = $list;
    }

    public function renderDuplicate($id): void
    {
        $list = $this->listsRepository->find($id);
        if (!$list) {
            throw new BadRequestException();
        }

        $variantsCount = $this->listVariantsRepository->getVariantsForType($list)->count('*');
        if ($variantsCount !== 0) {
            $this->flashMessage('Source list has variants. Duplication is not implemented.', 'danger');
            $this->redirect('default');
        }

        $this->template->list = $list;
    }

    public function createComponentDataTableTemplates(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('templateJsonData'))
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setColSetting('subject', [
                'priority' => 1,
                'render' => 'text'
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

    public function renderTemplateJsonData(): void
    {
        $request = $this->request->getParameters();

        $templatesCount = $this->templatesRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], [$request['listId']])
            ->count('*');

        $templates = $this->templatesRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], [$request['listId']], null, (int)$request['length'], (int)$request['start'])
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

    public function createComponentDataTableVariants(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('variantsJsonData'))
            ->setColSetting('title', [
                'header' => 'Title',
                'priority' => 1,
                'render' => 'text',
            ])
            ->setColSetting('code', [
                'header' => 'Code',
                'priority' => 1,
                'render' => 'code',
            ])
            ->setColSetting('count', [
                'priority' => 1,
            ])
            ->setTableSetting('add-params', Json::encode(['listId' => $this->getParameter('id')]))
            ->setTableSetting('order', Json::encode([[2, 'DESC']]));

        return $dataTable;
    }

    public function renderVariantsJsonData(): void
    {
        $request = $this->request->getParameters();

        $listId = $request['listId'] ? (int)$request['listId'] : null;
        $length = $request['length'] ? (int)$request['length'] : null;
        $start = $request['start'] ? (int)$request['start'] : null;

        $variantsCount = $this->listVariantsRepository
            ->tableFilter(
                $request['search']['value'],
                $request['columns'][$request['order'][0]['column']]['name'],
                $request['order'][0]['dir'],
                [$listId]
            )
            ->count('*');

        $variants = $this->listVariantsRepository
            ->tableFilter(
                $request['search']['value'],
                $request['columns'][$request['order'][0]['column']]['name'],
                $request['order'][0]['dir'],
                [$listId],
                $length,
                $start
            );

        $result = [
            'recordsTotal' => $this->listVariantsRepository->totalCount(),
            'recordsFiltered' => $variantsCount,
            'data' => []
        ];

        /** @var ActiveRow $variant */
        foreach ($variants as $variant) {
            $result['data'][] = [
                $variant->title,
                $variant->code,
                $variant->count,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function createComponentListForm(): Form
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = (int)$this->params['id'];
        }

        $form = $this->listFormFactory->create($id);

        $presenter = $this;
        $this->listFormFactory->onCreate = function ($list) use ($presenter) {
            $this->emitter->emit(new HermesMessage('list-created', [
                'list_id' => $list->id,
            ]), RedisDriver::PRIORITY_HIGH);

            $presenter->flashMessage('Newsletter list was created');
            $presenter->redirect('Show', $list->id);
        };

        $this->listFormFactory->onUpdate = function ($list) use ($presenter) {
            $this->emitter->emit(new HermesMessage('list-updated', [
                'list_id' => $list->id,
            ]), RedisDriver::PRIORITY_HIGH);

            $presenter->flashMessage('Newsletter list was updated');
            $presenter->redirect('Edit', $list->id);
        };

        return $form;
    }

    public function createComponentDuplicateListForm(): Form
    {
        if (!isset($this->params['id'])) {
            throw new BadRequestException();
        }

        $sourceListId = (int)$this->params['id'];
        $form = $this->duplicateListFormFactory->create($sourceListId);

        $presenter = $this;
        $this->duplicateListFormFactory->onCreate = function ($newList, $sourceList, $copySubscribers) use ($presenter) {
            $this->emitter->emit(new HermesMessage('list-created', [
                'list_id' => $newList->id,
                'source_list_id' => $sourceList->id,
                'duplicate' => true,
                'copy_subscribers' => $copySubscribers
            ]), RedisDriver::PRIORITY_HIGH);

            $presenter->flashMessage('Newsletter list was duplicated');
            $presenter->redirect('Edit', $newList->id);
        };

        return $form;
    }

    public function handleRenderSorting($categoryId, $sorting): void
    {
        $factory = $this->listFormFactory;

        // set sorting value
        $factory->getSortingControl($this['listForm'])->setValue($sorting);

        // handle newsletter list category change
        if ($factory->getMailTypeCategoryIdControl($this['listForm'])->getValue() !== $categoryId) {
            $lists = $this->listsRepository->findByCategory((int)$categoryId);
            if ($listId = $factory->getListIdControl($this['listForm'])->getValue()) {
                $lists = $lists->where('id != ?', $listId);
            }

            $lists = $lists->order('sorting ASC')->fetchPairs('sorting', 'title');
            $factory->getSortingAfterControl($this['listForm'])->setItems($lists);
        }

        $this->redrawControl('wrapper');
        $this->redrawControl('sortingAfterSnippet');
    }


    public function renderSentEmailsDetail($id): void
    {
        $mailType = $this->listsRepository->find($id);
        $groupBy = $this->getParameter('group_by', 'day');

        $this->template->mailTypeId = $mailType->id;
        $this->template->mailTypeTitle = $mailType->title;
        $this->template->groupBy = $groupBy;

        if (!$this->isAjax()) {
            $from = $this->getParameter('published_from', 'today - 30 days');
            $to = $this->getParameter('published_to', 'now');
            $tz = $this->getParameter('tz');

            $this->template->from = $from;
            $this->template->to = $to;

            $data = $this->emailsDetailData($id, $from, $to, $groupBy, $tz);

            $this->template->labels = $data['labels'];
            $this->template->parser = $data['parser'];
            $this->template->tooltipFormat = $data['tooltipFormat'];
            $this->template->sentDataSet = $data['sentDataSet'];
            $this->template->openedDataSet = $data['openedDataSet'];
            $this->template->clickedDataSet = $data['clickedDataSet'];
            $this->template->openRateDataSet = $data['openRateDataSet'];
            $this->template->clickRateDataSet = $data['clickRateDataSet'];
            $this->template->unsubscibedDataSet = $data['unsubscibedDataSet'];
        }
    }

    public function emailsDetailData($id, $from, $to, $groupBy, $tz): array
    {
        $labels = [];
        if ($tz !== null) {
            $tz = new DateTimeZone($tz);
        }

        $from = new DateTime($from, $tz);
        $to = new DateTime($to, $tz);

        $dateFormat = 'Y-m-d';
        $dateInterval = '+1 day';
        $parser = 'YYYY-MM-DD';
        $tooltipFormat = 'LL';
        if ($groupBy === 'week') {
            $dateFormat = 'o-W';
            $dateInterval = '+1 week';
            $parser = 'YYYY-WW';
            $tooltipFormat = 'w/YYYY';
        } elseif ($groupBy === 'month') {
            $dateFormat = 'Y-m';
            $dateInterval = '+1 month';
            $parser = 'YYYY-MM';
            $tooltipFormat = 'MMMM YY';
        }

        $fromLimit = clone $from;
        while ($fromLimit < $to) {
            $labels[] = $fromLimit->format($dateFormat);
            $fromLimit = $fromLimit->modify($dateInterval);
        }
        $numOfGroups = count($labels);

        $sentDataSet = [
            'label' => 'Sent',
            'data' => array_fill(0, $numOfGroups, 0),
            'fill' => false,
            'borderColor' => 'rgb(0,150,136)',
            'lineTension' => 0.2,
        ];

        $openedDataSet = [
            'label' => 'Opened',
            'data' => array_fill(0, $numOfGroups, 0),
            'fill' => false,
            'borderColor' => 'rgb(33,150,243)',
            'lineTension' => 0.2,
        ];

        $clickedDataSet = [
            'label' => 'Clicked',
            'data' => array_fill(0, $numOfGroups, 0),
            'fill' => false,
            'borderColor' => 'rgb(230,57,82)',
            'lineTension' => 0.2,
        ];

        $data = $this->mailTemplateStatsRepository->getMailTypeGraphData((int) $id, $from, $to, $groupBy)->fetchAll();

        // parse sent mails by type data to chart.js format
        foreach ($data as $row) {
            $foundAt = array_search(
                $row->label_date,
                $labels
            );

            if ($foundAt !== false) {
                $sentDataSet['data'][$foundAt] += $row->sent_mails;
                $openedDataSet['data'][$foundAt] += $row->opened_mails;
                $clickedDataSet['data'][$foundAt] += $row->clicked_mails;
            }
        }

        $openRateDataSet = [
            'label' => 'Open rate',
            'data' => array_fill(0, $numOfGroups, 0),
            'fill' => false,
            'borderColor' => 'rgb(33,150,243)',
            'lineTension' => 0.5
        ];

        $clickRateDataSet = [
            'label' => 'Click rate',
            'data' => array_fill(0, $numOfGroups, 0),
            'fill' => false,
            'borderColor' => 'rgb(230,57,82)',
            'lineTension' => 0.5
        ];

        foreach ($sentDataSet['data'] as $key => $sent) {
            $open = $openedDataSet['data'][$key];
            $click = $clickedDataSet['data'][$key];

            if ($open > 0) {
                $openRateDataSet['data'][$key] = round(($open / $sent) * 100, 2);
            } else {
                $openRateDataSet['data'][$key] = 0;
            }

            if ($click > 0) {
                $clickRateDataSet['data'][$key] = round(($click / $sent) * 100, 2);
            } else {
                $clickRateDataSet['data'][$key] = 0;
            }
        }

        $unsubscibedDataSet = [
            'label' => 'Unsubscribed users',
            'data' => array_fill(0, $numOfGroups, 0),
            'fill' => false,
            'borderColor' => 'rgb(230,57,82)',
            'lineTension' => 0.5
        ];

        $unsubscribedData = $this->userSubscriptionsRepository->getMailTypeGraphData((int) $id, $from, $to, $groupBy)
            ->fetchAll();
        foreach ($unsubscribedData as $unsubscibedDataRow) {
            $foundAt = array_search(
                $unsubscibedDataRow->label_date,
                $labels,
                true
            );

            if ($foundAt !== false) {
                $unsubscibedDataSet['data'][$foundAt] += $unsubscibedDataRow->unsubscribed_users;
            }
        }

        return [
            'labels' => $labels,
            'parser' => $parser,
            'tooltipFormat' => $tooltipFormat,
            'sentDataSet' => $sentDataSet,
            'openedDataSet' => $openedDataSet,
            'clickedDataSet' => $clickedDataSet,

            'openRateDataSet' => $openRateDataSet,
            'clickRateDataSet' => $clickRateDataSet,

            'unsubscibedDataSet' => $unsubscibedDataSet
        ];
    }

    public function handleFilterChanged($id, $from, $to, $group_by, $tz)
    {
        $data = $this->emailsDetailData($id, $from, $to, $group_by, $tz);

        $this->template->groupBy = $group_by;
        $this->template->labels = $data['labels'];
        $this->template->parser = $data['parser'];
        $this->template->tooltipFormat = $data['tooltipFormat'];
        $this->template->sentDataSet = $data['sentDataSet'];
        $this->template->openedDataSet = $data['openedDataSet'];
        $this->template->clickedDataSet = $data['clickedDataSet'];
        $this->template->openRateDataSet = $data['openRateDataSet'];
        $this->template->clickRateDataSet = $data['clickRateDataSet'];
        $this->template->unsubscibedDataSet = $data['unsubscibedDataSet'];

        $this->redrawControl('graph');
        $this->redrawControl('relativeGraph');
        $this->redrawControl('exportData');
    }

    public function createComponentDataTableSubscriberEmails(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('subscriberEmailsJsonData'))
            ->setColSetting('user_email', [
                'header' => 'user email',
                'priority' => 1,
                'render' => 'text',
            ])
            ->setColSetting('updated_at', [
                'header' => 'subscribed at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setTableSetting('add-params', Json::encode(['listId' => $this->getParameter('id')]))
            ->setTableSetting('order', Json::encode([[1, 'DESC']]));

        return $dataTable;
    }

    public function renderSubscriberEmailsJsonData(): void
    {
        $request = $this->request->getParameters();

        $subscriberEmailsCount = $this->userSubscriptionsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], (int)$request['listId'])
            ->count('*');

        $subscriberEmails = $this->userSubscriptionsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], (int)$request['listId'], (int)$request['length'], (int)$request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $subscriberEmailsCount,
            'recordsFiltered' => $subscriberEmailsCount,
            'data' => []
        ];

        /** @var ActiveRow $subscriberEmail */
        foreach ($subscriberEmails as $subscriberEmail) {
            $result['data'][] = [
                $subscriberEmail->user_email,
                $subscriberEmail->updated_at,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function handleDelete($id): void
    {
        $list = $this->listsRepository->find($id);

        if ($this->listsRepository->canBeDeleted($list)) {
            $this->listsRepository->softDelete($list);
            $this->flashMessage("List {$list->code} was deleted.");
            $this->redirect('default');
        } else {
            $this->flashMessage("There are emails using list {$list->code}.", 'danger');
            $this->redirect('Template:default', ['type' => $list->id]);
        }
    }
}
