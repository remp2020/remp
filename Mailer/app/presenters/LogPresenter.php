<?php

namespace Remp\MailerModule\Presenters;

use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Repository\LogsRepository;

final class LogPresenter extends BasePresenter
{
    /** @var LogsRepository */
    private $logsRepository;

    public function __construct(
        LogsRepository $logsRepository
    )
    {
        parent::__construct();
        $this->logsRepository = $logsRepository;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setColSetting('created_at')
            ->setColSetting('email')
            ->setColSetting('mail_template_id')
            ->setColSetting('events');

        return $dataTable;
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
}
