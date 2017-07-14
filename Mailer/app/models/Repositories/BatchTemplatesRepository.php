<?php

namespace Remp\MailerModule\Repository;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Remp\MailerModule\Repository;

class BatchTemplatesRepository extends Repository
{
    protected $tableName = 'mail_job_batch_templates';

    public function add($jobId, $batchId, $templateId, $weight = 100)
    {
        $result = $this->insert([
            'mail_job_id' => $jobId,
            'mail_job_batch_id' => $batchId,
            'mail_template_id' => $templateId,
            'weight' => $weight,
            'created_at' => new \DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function findByJobId($jobId)
    {
        return $this->getTable()->where(['mail_job_id' => $jobId]);
    }
}
