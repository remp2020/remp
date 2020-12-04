<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Remp\MailerModule\Repositories\ActiveRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories;

class BatchesRepository extends Repository
{
    const STATUS_CREATED = 'created';
    const STATUS_UPDATED = 'updated';
    const STATUS_READY = 'ready';
    const STATUS_PREPARING = 'preparing';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PROCESSED = 'processed';
    const STATUS_SENDING = 'sending';
    const STATUS_DONE = 'done';
    const STATUS_USER_STOP = 'user_stopped';
    const STATUS_WORKER_STOP = 'worker_stopped';

    const METHOD_RANDOM = 'random';

    const EDITABLE_STATUSES = [
        BatchesRepository::STATUS_CREATED,
        BatchesRepository::STATUS_UPDATED,
        BatchesRepository::STATUS_READY,
    ];

    protected $tableName = 'mail_job_batch';

    public function add(int $jobId, int $emailCount = null, string $startAt = null, string $method = 'random')
    {
        $result = $this->insert([
            'mail_job_id' => $jobId,
            'method' => $method,
            'max_emails' => $emailCount,
            'start_at' => $startAt ? new DateTime($startAt) : new DateTime(),
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function addTemplate(ActiveRow $batch, ActiveRow $template, int $weight = 100)
    {
        $this->database->table('mail_job_batch_templates')->insert([
            'mail_job_id' => $batch->mail_job_id,
            'mail_job_batch_id' => $batch->id,
            'mail_template_id' => $template->id,
            'weight' => $weight,
            'created_at' => new DateTime(),
        ]);
    }


    public function getBatchReady()
    {
        return $this->getTable()->select('*')->where([
            'status' => self::STATUS_READY,
            'start_at <= ? OR start_at IS NULL' => new DateTime(),
        ])->limit(1)->fetch();
    }

    public function getBatchToSend()
    {
        return $this->getTable()
            ->select('mail_job_batch.*')
            ->where([
                'mail_job_batch.status' => [ self::STATUS_PROCESSED, self::STATUS_SENDING ]
            ])
            ->order(':mail_job_batch_templates.mail_template.mail_type.priority DESC')
            ->limit(1)
            ->fetch();
    }

    public function getBatchPriority(ActiveRow $batch)
    {
        return $batch->related('mail_job_batch_templates')->fetch()->mail_template->mail_type->priority;
    }

    public function getInProgressBatches(int $limit)
    {
        return $this->getTable()
            ->where([
                'mail_job_batch.status' => [
                    self::STATUS_READY,
                    self::STATUS_PROCESSING,
                    self::STATUS_PROCESSED,
                    self::STATUS_SENDING
                ]
            ])
            ->order('start_at ASC')
            ->limit($limit);
    }

    public function getLastDoneBatches(int $limit)
    {
        return $this->getTable()
            ->where([
                'mail_job_batch.status' => [
                    self::STATUS_DONE
                ]
            ])
            ->order('mail_job_batch.last_email_sent_at DESC')
            ->limit($limit);
    }

    public function notEditableBatches(int $jobId)
    {
        return $this->getTable()
            ->select('*')
            ->where(['mail_job_id' => $jobId])
            ->where(['status NOT IN' => self::EDITABLE_STATUSES])
            ;
    }
}
