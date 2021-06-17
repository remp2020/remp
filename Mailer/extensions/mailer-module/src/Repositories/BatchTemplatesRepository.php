<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;

class BatchTemplatesRepository extends Repository
{
    protected $tableName = 'mail_job_batch_templates';

    private $eventMap = [
        'delivered_at' => 'delivered',
        'clicked_at' => 'clicked',
        'opened_at' => 'opened',
        'spam_complained_at' => 'spam_complained',
        'hard_bounced_at' => 'hard_bounced',
        'dropped_at' => 'dropped',
    ];

    public function getDashboardGraphDataForTypes(DateTime $from, DateTime $to): Selection
    {
        return $this->getTable()
            ->select('
                SUM(COALESCE(mail_job_batch_templates.sent, 0)) AS sent_mails,
                mail_template.mail_type_id,
                mail_template.mail_type.title AS mail_type_title,
                mail_job_batch.first_email_sent_at')
            ->where('mail_job_batch.first_email_sent_at IS NOT NULL')
            ->where('mail_template.mail_type_id IS NOT NULL')
            ->where('DATE(mail_job_batch.first_email_sent_at) >= DATE(?)', $from->format('Y-m-d'))
            ->where('DATE(mail_job_batch.first_email_sent_at) <= DATE(?)', $to->format('Y-m-d'))
            ->group('
                DATE(mail_job_batch.first_email_sent_at),
                mail_template.mail_type_id,
                mail_template.mail_type.title
            ')
            ->order('mail_template.mail_type_id')
            ->order('mail_job_batch.first_email_sent_at DESC');
    }

    public function add(int $jobId, int $batchId, int $templateId, int $weight = 100): ActiveRow
    {
        $result = $this->insert([
            'mail_job_id' => $jobId,
            'mail_job_batch_id' => $batchId,
            'mail_template_id' => $templateId,
            'weight' => $weight,
            'created_at' => new DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function findByBatchId(int $batchId): Selection
    {
        return $this->getTable()->where(['mail_job_batch_id' => $batchId]);
    }

    public function deleteByBatchId(int $batchId): int
    {
        return $this->findByBatchId($batchId)->delete();
    }

    public function mapEvent($event): ?string
    {
        return $this->eventMap[$event] ?? null;
    }

    public function updateAllConverted()
    {
        return $this->database->query('
            update mail_job_batch_templates
                left join (
                    select count(*) as count, mail_job_batch_id, mail_template_id
                    from mail_log_conversions
                             left join mail_logs on mail_log_id = mail_logs.id
                    group by mail_job_batch_id, mail_template_id
                ) t1 on mail_job_batch_templates.mail_template_id = t1.mail_template_id and
                        mail_job_batch_templates.mail_job_batch_id = t1.mail_job_batch_id
            set converted = coalesce(t1.count, 0)
        ');
    }

    public function incrementColumn($column, $mailTemplateId, $mailJobBatchId)
    {
        return $this->getTable()->where([
            'mail_template_id' => $mailTemplateId,
            'mail_job_batch_id' => $mailJobBatchId,
        ])->update([
            "{$column}+=" => 1,
        ]);
    }
}
