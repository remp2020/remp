<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;

class BatchesRepository extends Repository
{
    protected $tableName = 'mail_job_batch';

    public function add($jobId, $email_count, $startAt = null, $method = 'random')
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
}
