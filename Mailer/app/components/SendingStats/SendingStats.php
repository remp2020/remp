<?php

namespace Remp\MailerModule\Components;

use Nette\Application\UI\Control;
use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class SendingStats extends Control
{
    private $logsRepository;

    private $templatesRepository;

    private $batchesRepository;

    private $templateIds = [];

    private $jobBatchTemplates = [];

    private $showTotal = false;

    private $showConversions = false;

    public function __construct(
        LogsRepository $mailLogsRepository,
        TemplatesRepository $templatesRepository,
        BatchesRepository $batchesRepository
    ) {
        parent::__construct();

        $this->logsRepository = $mailLogsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->batchesRepository = $batchesRepository;
    }

    public function addTemplate(ActiveRow $mailTemplate)
    {
        /** @var ActiveRow $jobBatchTemplate */
        foreach ($mailTemplate->related('mail_job_batch_templates') as $jobBatchTemplate) {
            $this->jobBatchTemplates[] = $jobBatchTemplate;
        }
        $this->templateIds[] = $mailTemplate->id;
        return $this;
    }

    public function addBatch(ActiveRow $batch)
    {
        /** @var ActiveRow $jobBatchTemplate */
        foreach ($batch->related('mail_job_batch_templates') as $jobBatchTemplate) {
            $this->jobBatchTemplates[] = $jobBatchTemplate;
            $this->templateIds[] = $jobBatchTemplate->mail_template_id;
        }
        return $this;
    }

    public function addJobBatchTemplate(ActiveRow $jobBatchTemplate)
    {
        $this->jobBatchTemplates[] = $jobBatchTemplate;
        $this->templateIds[] = $jobBatchTemplate->mail_template_id;
    }

    public function showTotal()
    {
        $this->showTotal = true;
    }

    public function showConversions()
    {
        $this->showConversions = true;
    }

    public function render()
    {
        $total = 0;
        $stats = [
            'delivered' => ['value' => 0, 'per' => 0],
            'opened' => ['value' => 0, 'per' => 0],
            'clicked' => ['value' => 0, 'per' => 0],
            'converted' => ['value' => 0, 'per' => 0],
            'dropped' => ['value' => 0, 'per' => 0],
            'spam_complained' => ['value' => 0, 'per' => 0],
            'unsubscribed' => ['value' => 0, 'per' => 0],
        ];

        foreach ($this->jobBatchTemplates as $jobBatchTemplate) {
            $total += $jobBatchTemplate->sent;
            $stats['delivered']['value'] += $jobBatchTemplate->delivered;
            $stats['opened']['value'] += $jobBatchTemplate->opened;
            $stats['clicked']['value'] += $jobBatchTemplate->clicked;
            $stats['converted']['value'] += $jobBatchTemplate->converted;
            $stats['dropped']['value'] += $jobBatchTemplate->dropped;
            $stats['spam_complained']['value'] += $jobBatchTemplate->spam_complained;
            $stats['unsubscribed']['value'] += $jobBatchTemplate->mail_template->mail_type
                ->related('mail_user_subscriptions')
                ->where([
                    'utm_campaign' => $jobBatchTemplate->mail_template->code,
                    'utm_content' => $jobBatchTemplate->mail_job_batch_id,
                ])
                ->count('*');
        }

        $nonBatchTemplateStat = $this->logsRepository->getNonBatchTemplateStats($this->templateIds);
        if ($nonBatchTemplateStat) {
            $total += $nonBatchTemplateStat->sent;
            $stats['delivered']['value'] += $nonBatchTemplateStat->delivered;
            $stats['opened']['value'] += $nonBatchTemplateStat->opened;
            $stats['clicked']['value'] += $nonBatchTemplateStat->clicked;
            $stats['converted']['value'] += $nonBatchTemplateStat->converted;
            $stats['dropped']['value'] += $nonBatchTemplateStat->dropped;
            $stats['spam_complained']['value'] += $nonBatchTemplateStat->spam_complained;
        }

        foreach ($stats as $key => $stat) {
            $stats[$key]['per'] = $total ? ($stat['value'] / $total * 100) : 0;
        }

        $this->template->delivered_stat = $stats['delivered'];
        $this->template->opened_stat = $stats['opened'];
        $this->template->clicked_stat = $stats['clicked'];
        $this->template->converted_stat = $stats['converted'];
        $this->template->dropped_stat = $stats['dropped'];
        $this->template->spam_stat = $stats['spam_complained'];
        $this->template->unsubscribed_stat = $stats['unsubscribed'];
        $this->template->total_stat = $total;

        if ($this->showConversions) {
            $this->template->conversions = ['value' => 0, 'per' => 0];
        }

        $this->template->setFile(__DIR__ . '/sending_stats.latte');
        $this->template->render();
    }
}
