<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\SendingStats;

use Nette\Application\UI\Control;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\MailTemplateDirectStatsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;

class SendingStats extends Control
{
    private $templatesRepository;

    private $userSubscriptionsRepository;

    private $mailTemplateDirectStatsRepository;

    private $templateIds = [];

    private $jobBatchTemplates = [];

    private $showTotal = false;

    private $showConversions = false;

    public function __construct(
        TemplatesRepository $templatesRepository,
        UserSubscriptionsRepository $userSubscriptionsRepository,
        MailTemplateDirectStatsRepository $mailTemplateDirectStatsRepository
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->mailTemplateDirectStatsRepository = $mailTemplateDirectStatsRepository;
    }

    public function addTemplate(ActiveRow $mailTemplate): self
    {
        /** @var ActiveRow $jobBatchTemplate */
        foreach ($mailTemplate->related('mail_job_batch_templates') as $jobBatchTemplate) {
            $this->jobBatchTemplates[] = $jobBatchTemplate;
        }
        $this->templateIds[] = $mailTemplate->id;
        return $this;
    }

    public function addBatch(ActiveRow $batch): self
    {
        /** @var ActiveRow $jobBatchTemplate */
        foreach ($batch->related('mail_job_batch_templates') as $jobBatchTemplate) {
            $this->jobBatchTemplates[] = $jobBatchTemplate;
            $this->templateIds[] = $jobBatchTemplate->mail_template_id;
        }
        return $this;
    }

    public function addJobBatchTemplate(ActiveRow $jobBatchTemplate): void
    {
        $this->jobBatchTemplates[] = $jobBatchTemplate;
        $this->templateIds[] = $jobBatchTemplate->mail_template_id;
    }

    public function showTotal(): void
    {
        $this->showTotal = true;
    }

    public function showConversions(): void
    {
        $this->showConversions = true;
    }

    public function render(): void
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
                    'rtm_campaign' => $jobBatchTemplate->mail_template->code,
                    'rtm_content' => $jobBatchTemplate->mail_job_batch_id,
                    'subscribed' => false,
                ])
                ->count('*');
        }

        if ($this->templateIds) {
            $direct = $this->mailTemplateDirectStatsRepository->sumForTemplates($this->templateIds);

            $total += $direct['sent'];
            $stats['delivered']['value'] += $direct['delivered'];
            $stats['opened']['value'] += $direct['opened'];
            $stats['clicked']['value'] += $direct['clicked'];
            $stats['converted']['value'] += $direct['converted'];
            $stats['dropped']['value'] += $direct['dropped'];
            $stats['spam_complained']['value'] += $direct['spam_complained'];

            $templateCodes = $this->templatesRepository->getTable()
                ->where(['id' => $this->templateIds])
                ->fetchPairs('code', 'code');
            $stats['unsubscribed']['value'] += $this->userSubscriptionsRepository->getTable()
                ->where([
                    'rtm_campaign' => array_values($templateCodes),
                    'rtm_content' => null,
                    'subscribed' => false,
                ])
                ->count('*');
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
