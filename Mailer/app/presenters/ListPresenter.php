<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Forms\ListFormFactory;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\UsersRepository;

final class ListPresenter extends BasePresenter
{
    /** @var ListsRepository */
    private $listsRepository;

    /** @var UsersRepository */
    private $usersRepository;

    /** @var ListFormFactory */
    private $listFormFactory;

    public function __construct(
        ListsRepository $listsRepository,
        UsersRepository $usersRepository,
        ListFormFactory $listFormFactory
    ) {
    
        parent::__construct();
        $this->listsRepository = $listsRepository;
        $this->usersRepository = $usersRepository;
        $this->listFormFactory = $listFormFactory;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setColSetting('sorting', ['visible' => false])
            ->setColSetting('title')
            ->setColSetting('code')
            ->setColSetting('is_consent_required', ['header' => 'consent required', 'render' => 'boolean'])
            ->setColSetting('is_locked', ['header' => 'locked', 'render' => 'boolean'])
            ->setColSetting('is_public', ['header' => 'public', 'render' => 'boolean'])
            ->setColSetting('subscribers', ['header' => 'number of subscribers', 'render' => 'number', 'orderable' => false])
            ->setColSetting('created_at', ['header' => 'created at', 'render' => 'date'])
            ->setRowLink($this->link('Edit', 'RowId'))
            ->setRowAction('edit', $this->link('Edit', 'RowId'), 'palette-Cyan zmdi-edit');

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

        $totalUsers = $this->usersRepository->totalCount();

        foreach ($lists as $list) {
            $result['data'][] = [
                'RowId' => $list->id,
                $list->sorting,
                $list->title,
                $list->code,
                $list->auto_subscribe,
                $list->locked,
                $list->is_public,
                $list->is_public == 1 ? $list->consents : $totalUsers - $list->consents,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderEdit($id)
    {
        $list = $this->listsRepository->find($id);
        if (!$list) {
            throw new BadRequestException();
        }

        $this->template->list = $list;
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
