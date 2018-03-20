<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Forms\ListFormFactory;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Tomaj\Hermes\Emitter;

final class ListPresenter extends BasePresenter
{
    /** @var ListsRepository */
    private $listsRepository;

    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var ListFormFactory */
    private $listFormFactory;

    /** @var Emitter */
    private $emitter;

    public function __construct(
        ListsRepository $listsRepository,
        TemplatesRepository $templatesRepository,
        ListFormFactory $listFormFactory,
        Emitter $emitter
    ) {

        parent::__construct();
        $this->listsRepository = $listsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->listFormFactory = $listFormFactory;
        $this->emitter = $emitter;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setColSetting('category', ['visible' => false])
            ->setColSetting('title')
            ->setColSetting('code')
            ->setColSetting('subscribed', ['render' => 'number'])
            ->setColSetting('auto_subscribe', ['header' => 'auto subscribe', 'render' => 'boolean'])
            ->setColSetting('locked', ['render' => 'boolean'])
            ->setColSetting('is_public', ['header' => 'public', 'render' => 'boolean'])
            ->setAllColSetting('orderable', false)
            ->setRowAction('show', 'palette-Cyan zmdi-eye')
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
            $result['data'][] = [
                'actions' => [
                    'show' => $showUrl,
                ],
                $list->type_category->title,
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
    }

    public function createComponentDataTableTemplates(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('templateJsonData'))
            ->setColSetting('created_at', ['header' => 'created at', 'render' => 'date'])
            ->setColSetting('subject')
            ->setColSetting('opened')
            ->setColSetting('clicked')
            ->setRowAction('show', 'palette-Cyan zmdi-eye')
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
        $form = $this->listFormFactory->create();

        $presenter = $this;
        $this->listFormFactory->onSuccess = function ($list) use ($presenter) {
            $this->emitter->emit(new HermesMessage('list-created', [
                'list_id' => $list->id,
            ]));

            $presenter->flashMessage('Newsletter list was created');
            $presenter->redirect('Show', $list->id);
        };

        return $form;
    }

    public function handleListCategoryChange($categoryId)
    {
        $lists = $this->listsRepository->findByCategory($categoryId)->fetchPairs('sorting', 'title');
        $this['listForm']['sorting_after']->setItems($lists);

        $this->redrawControl('wrapper');
        $this->redrawControl('sortingAfterSnippet');
    }
}
