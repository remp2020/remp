<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

class JobQueueRepository extends Repository
{
    protected $tableName = 'mail_job_queue';

    const STATUS_NEW = 'new';
    const STATUS_DONE = 'done';
    const STATUS_ERROR = 'error';

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
                'params' => $row['params'] ?? [],
            ];
        }
        $this->database->query("INSERT INTO {$this->tableName}", $insertLogsData);
    }

    public function clearBatch(ActiveRow $batch): int
    {
        return $this->getTable()->where(['mail_batch_id' => $batch->id])->delete();
    }

    public function stripEmails(ActiveRow $batch, $leaveEmails): void
    {
        if (!$leaveEmails) {
            return;
        }

        $total = $this->getTable()->where(['mail_batch_id' => $batch->id])->count('*');
        $remove = $total - $leaveEmails;
        if ($remove > 0) {
            $this->getDatabase()->query("DELETE FROM {$this->tableName} WHERE mail_batch_id={$batch->id} ORDER BY RAND() LIMIT $remove");
        }
    }

    public function removeAlreadySent(ActiveRow $batch): void
    {
        $job = $batch->job;
        $this->getDatabase()->query("DELETE FROM {$this->tableName} WHERE mail_batch_id={$batch->id} AND email IN (SELECT email FROM mail_logs WHERE mail_job_id = {$job->id})");
    }

    public function removeAlreadyQueued(ActiveRow $batch): void
    {
        $job = $batch->job;
        $this->getDatabase()->query("DELETE mjq1.* FROM mail_job_queue mjq1 JOIN mail_job_queue mjq2 ON mjq1.email = mjq2.email
  WHERE mjq1.mail_batch_id = {$batch->id} AND mjq2.mail_batch_id IN (SELECT id FROM mail_job_batch WHERE mail_job_id = {$job->id} AND id <> {$batch->id})
");
    }

    public function removeUnsubscribed(ActiveRow $batch): void
    {
        $q = $this->getTable()
            ->select('mail_template_id')
            ->where(['mail_batch_id' => $batch->id])
            ->group('mail_template_id');

        foreach ($q as $row) {
            $sql = <<<SQL
DELETE FROM mail_job_queue
WHERE mail_job_queue.mail_batch_id = ?
AND mail_job_queue.mail_template_id = ?
AND mail_job_queue.id NOT IN (

  SELECT id FROM (

    SELECT mail_job_queue.id FROM mail_job_queue
    INNER JOIN mail_templates 
      ON mail_job_queue.mail_template_id = mail_templates.id
    INNER JOIN mail_user_subscriptions
      ON mail_user_subscriptions.mail_type_id = mail_templates.mail_type_id
      AND subscribed = 1
      AND mail_job_queue.email = mail_user_subscriptions.user_email
    WHERE mail_job_queue.mail_batch_id = ?
    AND  mail_job_queue.mail_template_id = ?

  ) t1

);
SQL;
            $this->getDatabase()->query($sql, $batch->id, $row->mail_template_id, $batch->id, $row->mail_template_id);
        }
    }

    public function removeOtherVariants(ActiveRow $batch, int $variantId): void
    {
        $sql = <<<SQL

DELETE FROM mail_job_queue
WHERE mail_job_queue.id IN (

  SELECT * FROM (
    
    SELECT id FROM mail_job_queue
      WHERE mail_job_queue.mail_batch_id = {$batch->id}
        AND mail_job_queue.email NOT IN (
          SELECT mail_user_subscriptions.user_email
          FROM mail_user_subscriptions
          INNER JOIN mail_user_subscription_variants
            ON mail_user_subscription_variants.mail_user_subscription_id = mail_user_subscriptions.id
            AND mail_user_subscription_variants.mail_type_variant_id = '{$variantId}'
            AND mail_user_subscriptions.subscribed = 1
        )
  ) t1
)
SQL;

        $this->getDatabase()->query($sql);
    }

    public function removeAlreadySentContext(ActiveRow $batch, string $context): void
    {
        $sql = <<<SQL
DELETE q.*
  FROM mail_job_queue q JOIN mail_logs ml ON q.mail_batch_id = ? AND q.email = ml.email AND ml.context = ?
SQL;
        $this->getDatabase()->query($sql, $batch->id, $context);

        $query = "DELETE mjq1.* 
            FROM mail_job_queue mjq1 
            INNER JOIN mail_job_queue mjq2 ON mjq1.email = mjq2.email AND 
              mjq2.context = mjq1.context AND 
              mjq2.mail_batch_id != {$batch->id}
            WHERE mjq1.mail_batch_id = {$batch->id}";

        $this->getDatabase()->query($query);
    }

    public function getBatchEmails(ActiveRow $mailBatch, int $lastId, $count = null): Selection
    {
        $selection = $this->getTable()->where(['id > ?' => $lastId, 'mail_batch_id' => $mailBatch->id])->order('id ASC');
        if ($count !== null) {
            $selection->limit($count);
        }

        return $selection;
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
}
