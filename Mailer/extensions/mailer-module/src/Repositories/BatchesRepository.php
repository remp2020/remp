<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\Storage;
use Nette\Database\Context;
use Nette\Utils\DateTime;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Hermes\RedisDriver;
use Tomaj\Hermes\Emitter;

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

    private Emitter $emitter;

    public function __construct(Context $database, Emitter $emitter, Storage $cacheStorage = null)
    {
        parent::__construct($database, $cacheStorage);
        $this->emitter = $emitter;
    }

    public function add(int $jobId, int $emailCount = null, string $startAt = null, string $method = 'random'): ActiveRow
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

    public function updateStatus(ActiveRow $batch, $status): bool
    {
        $result = $this->update($batch, [
            'status' => $status,
            'updated_at' => new DateTime()
        ]);
        $this->emitter->emit(new HermesMessage('batch-status-change', [
            'mail_job_batch_id' => $batch->id,
            'time' => time(),
            'status' => $status
        ]), RedisDriver::PRIORITY_LOW);

        return $result;
    }

    public function addTemplate(ActiveRow $batch, ActiveRow $template, int $weight = 100): ActiveRow
    {
        $row = $this->database->table('mail_job_batch_templates')->insert([
            'mail_job_id' => $batch->mail_job_id,
            'mail_job_batch_id' => $batch->id,
            'mail_template_id' => $template->id,
            'weight' => $weight,
            'created_at' => new DateTime(),
        ]);
        return new ActiveRow($row->toArray(), $row->getTable());
    }

    public function getBatchReady(): ?ActiveRow
    {
        return $this->getTable()->select('*')->where([
            'status' => self::STATUS_READY,
            'start_at <= ? OR start_at IS NULL' => new DateTime(),
        ])->limit(1)->fetch();
    }

    public function getBatchToSend(): ?ActiveRow
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

    public function getBatchPriority(ActiveRow $batch): int
    {
        return $batch->related('mail_job_batch_templates')->fetch()->mail_template->mail_type->priority;
    }

    public function getInProgressBatches(int $limit): Selection
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

    public function getLastDoneBatches(int $limit): Selection
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

    public function notEditableBatches(int $jobId): Selection
    {
        return $this->getTable()
            ->select('*')
            ->where(['mail_job_id' => $jobId])
            ->where(['status NOT IN' => self::EDITABLE_STATUSES]);
    }
}
