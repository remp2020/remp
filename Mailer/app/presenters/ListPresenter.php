<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
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
    )
    {
        parent::__construct();
        $this->listsRepository = $listsRepository;
        $this->usersRepository = $usersRepository;
        $this->listFormFactory = $listFormFactory;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $lists = $this->listsRepository->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir']);
        $result = [
            'recordsTotal' => $this->listsRepository->totalCount(),
            'recordsFiltered' => count($lists),
            'data' => []
        ];

        $lists = array_slice($lists, $request['start'], $request['length']);
        $totalUsers = $this->usersRepository->totalCount();

        foreach ($lists as $list) {
            $result['data'][] = [
                'RowId' => $list->id,
                $list->name,
                $list->code,
                $list->is_consent_required,
                $list->is_locked,
                $list->is_public,
                $list->is_consent_required == 1 ? $list->consents : $totalUsers - $list->consents,
                $list->created_at,
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
