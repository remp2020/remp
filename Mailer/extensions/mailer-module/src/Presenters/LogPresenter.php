<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Utils\Json;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Repositories\LogsRepository;

final class LogPresenter extends BasePresenter
{
    private $logsRepository;

    private $dataTableFactory;

    public function __construct(
        LogsRepository $logsRepository,
        DataTableFactory $dataTableFactory
    ) {
        parent::__construct();
        $this->logsRepository = $logsRepository;
        $this->dataTableFactory = $dataTableFactory;
    }

    public function createComponentDataTableDefault(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setColSetting('created_at', [
                'header' => 'sent at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setColSetting('email', [
                'priority' => 1,
            ])
            ->setColSetting('subject', [
                'priority' => 1,
            ])
            ->setColSetting('mail_template_id', [
                'header' => 'template code',
                'render' => 'link',
                'orderable' => false,
                'priority' => 2,
            ])
            ->setColSetting('attachment_size', [
                'header' => 'attachment',
                'render' => 'bytes',
                'class' => 'text-right',
                'priority' => 2,
            ])
            ->setColSetting('events', [
                'render' => 'badge',
                'orderable' => false,
                'priority' => 3,
            ])
            ->setTableSetting('order', Json::encode([[0, 'DESC']]));

        return $dataTable;
    }

    public function renderDefaultJsonData(): void
    {
        $request = $this->request->getParameters();

        $logsCount = $this->logsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'])
            ->count('*');

        $logs = $this->logsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], (int)$request['length'], (int)$request['start'])
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
                    'url' => $this->link(
                        'Template:Show',
                        ['id' => $log->mail_template_id]
                    ), 'text' => $log->mail_template->code
                ],
                $log->attachment_size,
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
}
