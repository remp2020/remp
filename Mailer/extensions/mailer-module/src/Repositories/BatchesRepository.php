<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Hermes\RedisDriver;
use Remp\MailerModule\Models\DataRetentionInterface;
use Remp\MailerModule\Models\DataRetentionTrait;
use Remp\MailerModule\Models\Job\MailCache;
use Tomaj\Hermes\Emitter;

class BatchesRepository extends Repository implements DataRetentionInterface
{
    use DataRetentionTrait;

    const STATUS_CREATED = 'created';
    const STATUS_READY_TO_PROCESS_AND_SEND = 'ready_to_process_and_send';
    const STATUS_READY_TO_PROCESS = 'ready_to_process';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PROCESSED = 'processed';
    const STATUS_QUEUED = 'queued';
    const STATUS_SENDING = 'sending';
    const STATUS_DONE = 'done';
    const STATUS_USER_STOP = 'user_stopped';
    const STATUS_WORKER_STOP = 'worker_stopped';

    const METHOD_RANDOM = 'random';

    const EDITABLE_STATUSES = [
        BatchesRepository::STATUS_CREATED,
        BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND,
        BatchesRepository::STATUS_READY_TO_PROCESS,
    ];
    const SENDING_STATUSES = [
        self::STATUS_QUEUED, // waiting for the next worker to pick up
        self::STATUS_SENDING, // actually sending
    ];
    const STOP_STATUSES = [
        self::STATUS_USER_STOP,
        self::STATUS_WORKER_STOP,
    ];
    protected $tableName = 'mail_job_batch';

    public function __construct(
        Explorer $database,
        protected Emitter $emitter,
        protected MailCache $mailCache,
        protected BatchTemplatesRepository $batchTemplatesRepository,
        protected JobQueueRepository $jobQueueRepository,
        Storage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
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

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function updateStatus(ActiveRow $batch, $status): bool
    {
        $result = $this->update($batch, [
            'status' => $status,
        ]);

        if (in_array($status, self::SENDING_STATUSES, true)) {
            $priority = $this->getBatchPriority($batch);
            $this->mailCache->restartQueue($batch->id, $priority);
        } elseif (in_array($status, self::STOP_STATUSES, true)) {
            $this->mailCache->pauseQueue($batch->id);
        } elseif (in_array($status, self::EDITABLE_STATUSES, true)) {
            $this->mailCache->removeQueue($batch->id);
        }

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
            'status' => [self::STATUS_READY_TO_PROCESS_AND_SEND, self::STATUS_READY_TO_PROCESS],
            'start_at <= ? OR start_at IS NULL' => new DateTime(),
        ])->limit(1)->fetch();
    }

    public function getBatchToSend(): ?ActiveRow
    {
        return $this->getTable()
            ->select('mail_job_batch.*')
            ->where([
                'mail_job_batch.status' => self::SENDING_STATUSES,
            ])
            ->order(':mail_job_batch_templates.mail_template.mail_type.priority DESC')
            ->limit(1)
            ->fetch();
    }

    public function isSendingBatch(int $batchId): bool
    {
        return $this->getTable()
            ->select('mail_job_batch.*')
            ->where([
                'mail_job_batch.id' => $batchId,
                'mail_job_batch.status' => self::SENDING_STATUSES,
            ])
            ->count('*') > 0;
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
                    self::STATUS_READY_TO_PROCESS_AND_SEND,
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

    public function removeData(): ?int
    {
        if ($this->retentionForever) {
            return null;
        }

        $threshold = (new \DateTime())->modify('-' . $this->retentionThreshold);
        $idsToDelete = $this->getTable()
            ->select('mail_job_batch.id')
            ->joinWhere(':mail_logs', 'mail_job_batch.id = :mail_logs.mail_job_batch_id')
            ->where("mail_job_batch.{$this->getRetentionRemovingField()} < ?", $threshold)
            ->where('status = ?', self::STATUS_PROCESSED)
            ->where(':mail_logs.id IS NULL')
            ->fetchPairs('id', 'id'); // delete only batches that were never sent

        if (!count($idsToDelete)) {
            return 0;
        }

        foreach ($idsToDelete as $batchId) {
            $this->batchTemplatesRepository->deleteByBatchId($batchId);
            $this->mailCache->removeQueue($batchId);
            $this->jobQueueRepository->deleteJobsByBatch($batchId);
        }

        return $this->getTable()->where('mail_job_batch.id IN (?)', $idsToDelete)->delete();
    }
}
