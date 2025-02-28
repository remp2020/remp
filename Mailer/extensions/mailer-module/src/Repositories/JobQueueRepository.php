<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\Json;

class JobQueueRepository extends Repository
{
    protected $tableName = 'mail_job_queue';

    public const STATUS_NEW = 'new';
    public const STATUS_DONE = 'done';
    public const STATUS_ERROR = 'error';

    public function multiInsert(array $rows): void
    {
        $status = self::STATUS_NEW;
        $insertLogsData = [];
        foreach ($rows as $row) {
            $insertLogsData[] = [
                'mail_batch_id' => $row['batch'],
                'mail_template_id' => $row['templateId'],
                'status' => $status,
                'sorting' => $row['sorting'],
                'email' => $row['email'],
                'context' => $row['context'],
                'params' => $row['params'] ?? Json::encode([]),
            ];
        }
        $this->database->query("INSERT INTO {$this->tableName}", $insertLogsData);
    }

    public function clearBatch(ActiveRow $batch): int
    {
        return $this->getTable()->where(['mail_batch_id' => $batch->id])->delete();
    }

    public function stripEmails(ActiveRow $batch, int $leaveEmails, int $limit = 10000): void
    {
        if (!$leaveEmails) {
            return;
        }

        $total = $this->getTable()->where(['mail_batch_id' => $batch->id])->count('*');
        $remove = $total - $leaveEmails;
        if ($remove <= 0) {
            return;
        }

        $sql = <<<SQL
SELECT id FROM {$this->tableName}
  WHERE mail_batch_id = {$batch->id}
ORDER BY RAND()
LIMIT ?
SQL;
        while ($remove > $limit) {
            $ids = $this->getDatabase()->query($sql, $limit)->fetchPairs(null, 'id');
            $this->deleteAllByIds($ids);
            $remove -= $limit;
        }

        $ids = $this->getDatabase()->query($sql, $remove)->fetchPairs(null, 'id');
        $this->deleteAllByIds($ids);
    }

    public function removeAlreadySent(ActiveRow $batch, int $limit = 10000): void
    {
        $job = $batch->job;
        $sql = <<<SQL
SELECT id FROM {$this->tableName}
    WHERE mail_batch_id = {$batch->id}
      AND email IN (SELECT email FROM mail_logs WHERE mail_job_id = {$job->id})
LIMIT $limit
SQL;

        while ($ids = $this->getDatabase()->query($sql)->fetchPairs(null, 'id')) {
            $this->deleteAllByIds($ids);
        }
    }

    public function removeAlreadyQueued(ActiveRow $batch, int $limit = 10000): void
    {
        $job = $batch->job;
        $sql = <<<SQL
SELECT mjq1.id FROM mail_job_queue mjq1
    JOIN mail_job_queue mjq2 ON mjq1.email = mjq2.email
  WHERE mjq1.mail_batch_id = $batch->id
    AND mjq2.mail_batch_id IN (SELECT id FROM mail_job_batch WHERE mail_job_id = $job->id AND id <> $batch->id)
LIMIT $limit
SQL;

        while ($ids = $this->getDatabase()->query($sql)->fetchPairs(null, 'id')) {
            $this->deleteAllByIds($ids);
        }
    }

    public function removeUnsubscribed(ActiveRow $batch, int $limit = 10000): void
    {
        $q = $this->getTable()
            ->select('mail_template_id')
            ->where(['mail_batch_id' => $batch->id])
            ->group('mail_template_id');

        foreach ($q as $row) {
            $sql = <<<SQL
SELECT mail_job_queue.id
FROM mail_job_queue
WHERE mail_job_queue.mail_batch_id = {$batch->id}
    AND mail_job_queue.mail_template_id = {$row->mail_template_id}
    AND mail_job_queue.email NOT IN (
        SELECT user_email
        FROM mail_user_subscriptions
        WHERE mail_user_subscriptions.mail_type_id = {$row->mail_template->mail_type_id}
           AND mail_user_subscriptions.subscribed = 1
    )
SQL;

            $ids = $this->getDatabase()->query($sql)->fetchPairs(null, 'id');
            foreach (array_chunk($ids, $limit, true) as $idsChunk) {
                $this->deleteAllByIds($idsChunk);
            }
        }
    }

    public function removeOtherVariants(ActiveRow $batch, int $variantId, int $limit = 10000): void
    {
        $sql = <<<SQL
SELECT mail_job_queue.id
FROM mail_job_queue
WHERE mail_job_queue.mail_batch_id = {$batch->id}
    AND mail_job_queue.email NOT IN (
        SELECT mail_user_subscriptions.user_email
        FROM mail_user_subscriptions
        INNER JOIN mail_user_subscription_variants
            ON mail_user_subscription_variants.mail_user_subscription_id = mail_user_subscriptions.id
            AND mail_user_subscription_variants.mail_type_variant_id = '{$variantId}'
            AND mail_user_subscriptions.subscribed = 1
)
LIMIT $limit
SQL;

        while ($ids = $this->getDatabase()->query($sql)->fetchPairs(null, 'id')) {
            $this->deleteAllByIds($ids);
        }
    }

    public function removeAlreadySentContext(ActiveRow $batch, string $context, int $limit = 10000): void
    {
        $sql = <<<SQL
SELECT q.id
  FROM mail_job_queue q
      JOIN mail_logs ml ON q.mail_batch_id = ? AND q.email = ml.email AND ml.context = ?
LIMIT $limit
SQL;

        while ($ids = $this->getDatabase()->query($sql, $batch->id, $context)->fetchPairs(null, 'id')) {
            $this->deleteAllByIds($ids);
        }

        $sql = <<<SQL
SELECT mjq1.id 
    FROM mail_job_queue mjq1 
    INNER JOIN mail_job_queue mjq2 ON mjq1.email = mjq2.email AND 
      mjq2.context = mjq1.context AND 
      mjq2.mail_batch_id != {$batch->id}
    WHERE mjq1.mail_batch_id = {$batch->id}
LIMIT $limit
SQL;

        while ($ids = $this->getDatabase()->query($sql)->fetchPairs(null, 'id')) {
            $this->deleteAllByIds($ids);
        }
    }

    public function getBatchEmails(ActiveRow $mailBatch, int $lastId = 0, $count = null): Selection
    {
        $selection = $this->getTable()->where(['id > ?' => $lastId, 'mail_batch_id' => $mailBatch->id])->order('id ASC');
        if ($count !== null) {
            $selection->limit($count);
        }

        return $selection;
    }

    public function getBatchUsersCount(ActiveRow $mailBatch): int
    {
        return $this->getTable()->where(['mail_batch_id' => $mailBatch->id])->count('*');
    }

    public function getJob(string $email, int $batchId): ?ActiveRow
    {
        return $this->getTable()->where(['email' => $email, 'mail_batch_id' => $batchId])->limit(1)->fetch();
    }

    public function deleteJobsByBatch(int $batchId, bool $newOnly = false): int
    {
        $table = $this->getTable()->where(['mail_batch_id' => $batchId]);
        if ($newOnly) {
            $table->where(['status' => 'new']);
        }

        return $table->delete();
    }

    /**
     * @param array<string> $emails
     */
    public function deleteAllByEmails(array $emails): int
    {
        if (count($emails) === 0) {
            return 0;
        }

        return $this->getTable()->where([
            'email' => $emails
        ])->delete();
    }

    /**
     * @param array<int> $ids
     */
    public function deleteAllByIds(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        return $this->getTable()->where([
            'id' => $ids
        ])->delete();
    }
}
