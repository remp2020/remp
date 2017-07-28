<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\UsersRepository;

final class ListPresenter extends BasePresenter
{
    /** @var ListsRepository */
    private $listsRepository;

    /** @var UsersRepository */
    private $usersRepository;

    public function __construct(
        ListsRepository $listsRepository,
        UsersRepository $usersRepository
    ) {
    
        parent::__construct();
        $this->listsRepository = $listsRepository;
        $this->usersRepository = $usersRepository;
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
            ->setRowLink($this->link('Show', 'RowId'))
            ->setRowAction('show', $this->link('Show', 'RowId'), 'palette-Cyan zmdi-eye')
            ->setTableSetting('displayNavigation', false)
            ->setTableSetting('rowGroup', 0)
            ->setTableSetting('length', -1);

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $listsCount = $this->listsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'])
            ->count('*');

        $lists = $this->listsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $request['length'], $request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->listsRepository->totalCount(),
            'recordsFiltered' => $listsCount,
            'data' => []
        ];

        foreach ($lists as $list) {
            $result['data'][] = [
                'RowId' => $list->id,
                $list->type_category->title,
                $list->title,
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

    public function createComponentListForm()
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = (int)$this->params['id'];
        }

        $form = $this->listFormFactory->create($id);

        $presenter = $this;
        $this->listFormFactory->onCreate = function ($list) use ($presenter) {
            $presenter->flashMessage('Newsletter list was created');
            $presenter->redirect('Edit', $list->id);
        };
        $this->listFormFactory->onUpdate = function ($list) use ($presenter) {
            $presenter->flashMessage('Newsletter list was updated');
            $presenter->redirect('Edit', $list->id);
        };

        return $form;
    }
}
