<?php

namespace Remp\MailerModule\Repository;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Remp\MailerModule\Repository;

class BatchTemplatesRepository extends Repository
{
    protected $tableName = 'mail_job_batch_templates';

    public function countMailsSent($numOfDays = null)
    {
        $selection = $this->getTable()->select('
            SUM(mail_job_batch_templates.sent) AS count
        ');

        if (!is_null($numOfDays)) {
            $selection->where('
                DATE(mail_job:mail_job_batch.first_email_sent_at) > DATE(NOW() - INTERVAL ? DAY)
            ', $numOfDays);
        }

        return $selection;
    }

    public function getDashboardGraphDataForTypes($numOfDays)
    {
        return $this->getTable()
            ->select('
                SUM(mail_job_batch_templates.sent) AS sent_mails,
                mail_template.mail_type_id,
                mail_template.mail_type.title AS mail_type_title,
                mail_job:mail_job_batch.first_email_sent_at')
            ->where('mail_job:mail_job_batch.first_email_sent_at IS NOT NULL')
            ->where('mail_template.mail_type_id IS NOT NULL')
            ->where('DATE(mail_job:mail_job_batch.first_email_sent_at) > DATE(NOW() - INTERVAL ? DAY)', $numOfDays)
            ->group('mail_template.mail_type_id, DATE(mail_job:mail_job_batch.first_email_sent_at)')
            ->order('mail_template.mail_type_id')
            ->order('mail_job:mail_job_batch.first_email_sent_at DESC');
    }

    public function getDashboardGraphData($numOfDays)
    {
        return $this->getTable()
            ->select('
                SUM(mail_job_batch_templates.sent) AS sent_mails,
                mail_job:mail_job_batch.first_email_sent_at
            ')
            ->where('mail_job:mail_job_batch.first_email_sent_at IS NOT NULL')
            ->where('DATE(mail_job:mail_job_batch.first_email_sent_at) > DATE(NOW() - INTERVAL ? DAY)', $numOfDays)
            ->group('DATE(mail_job:mail_job_batch.first_email_sent_at)');
    }

    public function getDashboardDetailGraphData($mailTypeId, $numOfDays)
    {
        return $this->getTable()
            ->select('
                SUM(mail_job_batch_templates.sent) AS sent_mails,
                mail_template.mail_type_id,
                mail_template.mail_type.title AS mail_type_title,
                mail_job:mail_job_batch.first_email_sent_at')
            ->where('mail_job:mail_job_batch.first_email_sent_at IS NOT NULL')
            ->where('mail_template.mail_type_id = ?', $mailTypeId)
            ->where('DATE(mail_job:mail_job_batch.first_email_sent_at) > DATE(NOW() - INTERVAL ? DAY)', $numOfDays)
            ->group('mail_template.mail_type_id, DATE(mail_job:mail_job_batch.first_email_sent_at)')
            ->order('mail_template.mail_type_id')
            ->order('mail_job:mail_job_batch.first_email_sent_at DESC');
    }

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

    public function findByBatchId($batchId)
    {
        return $this->getTable()->where(['mail_job_batch_id' => $batchId]);
    }

    public function deleteByBatchId($batchId)
    {
        $this->findByBatchId($batchId)->delete();
    }
}
