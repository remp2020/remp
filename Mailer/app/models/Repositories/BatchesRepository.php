<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;

class BatchesRepository extends Repository
{
    const STATE_CREATED = 'created';
    const STATE_UPDATED = 'updated';
    const STATE_READY = 'ready';
    const STATE_PREPARING = 'preparing';
    const STATE_PROCESSING = 'processing';
    const STATE_PROCESSED = 'processed';
    const STATE_SENDING = 'sending';
    const STATE_DONE = 'done';
    const STATE_USER_STOP = 'user_stopped';
    const STATE_WORKER_STOP = 'worker_stopped';

    protected $tableName = 'mail_job_batch';

    public function add($jobId, $email_count = null, $startAt = null, $method = 'random')
    {
        $result = $this->insert([
            'mail_job_id' => $jobId,
            'method' => $method,
            'max_emails' => $email_count,
            'start_at' => new \DateTime($startAt),
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function getBatchReady()
    {
        return $this->getTable()->select('*')->where([
            'status' => BatchesRepository::STATE_READY,
            'start_at <= ? OR start_at IS NULL' => new DateTime(),
        ])->limit(1)->fetch();
    }

    public function getBatchToSend()
    {
        return $this->getTable()->select('*')->where([
            'status' => [ BatchesRepository::STATE_PROCESSED, BatchesRepository::STATE_SENDING ]
        ])->limit(1)->fetch();
    }
}
