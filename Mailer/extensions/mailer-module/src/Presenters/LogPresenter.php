<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Crm\ApplicationModule\Repository\AuditLogRepository;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\Form\Renderer\BootstrapInlineRenderer;

final class LogPresenter extends BasePresenter
{
    /** @persistent */
    public $email;

    /** @persistent */
    public $created_at_from;

    /** @persistent */
    public $created_at_to;

    /** @persistent */
    public $mail_template_code;

    private $logsRepository;

    private $dataTableFactory;

    private $templatesRepository;

    public function __construct(
        LogsRepository $logsRepository,
        DataTableFactory $dataTableFactory,
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();
        $this->logsRepository = $logsRepository;
        $this->dataTableFactory = $dataTableFactory;
        $this->templatesRepository = $templatesRepository;
    }

    public function startup(): void
    {
        parent::startup();

        if ($this->created_at_from === null) {
            $this->created_at_from = (new DateTime())->modify('-1 day')->format('m/d/Y H:i A');
        }
        if ($this->created_at_to === null) {
            $this->created_at_to = (new DateTime())->format('m/d/Y H:i A');
        }
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
                'render' => 'text',
            ])
            ->setColSetting('subject', [
                'priority' => 1,
                'render' => 'text',
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
            ->setTableSetting('order', Json::encode([[0, 'DESC']]))
            ->setTableSetting('allowSearch', false);

        return $dataTable;
    }

    private function isFilterActive()
    {
        if ($this->email) {
            return true;
        }
        if ($this->mail_template_code) {
            return true;
        }
        if ($this->created_at_from) {
            return true;
        }
        if ($this->created_at_to) {
            return true;
        }
        return false;
    }

    public function renderDefaultJsonData(): void
    {
        $request = $this->request->getParameters();

        $mailTemplateId = null;
        if ($this->mail_template_code) {
            $mailTemplate = $this->templatesRepository->getByCode($this->mail_template_code);
            if ($mailTemplate) {
                $mailTemplateId = $mailTemplate->id;
            } else {
                $this->mail_template_code = null;
            }
        }

        $logsCount = 0;
        $logs = [];
        if ($this->isFilterActive()) {
            $logsCount = $this->logsRepository
                ->tableFilter(
                    $this->email ?? '',
                    $request['columns'][$request['order'][0]['column']]['name'],
                    $request['order'][0]['dir'],
                    null,
                    null,
                    $mailTemplateId,
                    $this->created_at_from ? DateTime::createFromFormat('m/d/Y H:i A', $this->created_at_from) : null,
                    $this->created_at_to ? DateTime::createFromFormat('m/d/Y H:i A', $this->created_at_to) : null,
                )->count('*');
            $logs = $this->logsRepository
                ->tableFilter(
                    $this->email ?? '',
                    $request['columns'][$request['order'][0]['column']]['name'],
                    $request['order'][0]['dir'],
                    (int)$request['length'],
                    (int)$request['start'],
                    $mailTemplateId,
                    $this->created_at_from ? DateTime::createFromFormat('m/d/Y H:i A', $this->created_at_from) : null,
                    $this->created_at_to ? DateTime::createFromFormat('m/d/Y H:i A', $this->created_at_to) : null,
                )->fetchAll();
        }

        $result = [
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
                    ), 'text' => $log->mail_template->code ?? null
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

    public function createComponentFilterLogsForm()
    {
        $form = new Form;
        $form->setRenderer(new BootstrapInlineRenderer());
        $form->setHtmlAttribute('class', 'form-logs-filter');

        $form->addText('email', 'E-mail');

        $form->addText('mail_template_code', 'Mail template code');

        $form->addText('created_at_from', 'Sent from')->setRequired('Field `Sent from` is required');

        $form->addText('created_at_to', 'Sent to')->setRequired('Field `Sent to` is required');

        $form->addSubmit('send')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-filter"></i> Filter');

        $presenter = $this;
        $form->addSubmit('cancel', 'Cancel')->onClick[] = function () use ($presenter) {
            $presenter->redirect('Log:Default', [
                'email' => null,
                'mail_template_code' => null,
                'created_at_from' => null,
                'created_at_to' => null,
            ]);
        };

        $form->onSuccess[] = [$this, 'adminFilterSubmitted'];
        $form->setDefaults([
            'email' => $this->email,
            'created_at_from' => $this->created_at_from,
            'created_at_to' => $this->created_at_to,
            'mail_template_code' => $this->mail_template_code,
        ]);
        return $form;
    }

    public function adminFilterSubmitted($form, $values)
    {
        $this->redirect($this->action, (array) $values);
    }
}
