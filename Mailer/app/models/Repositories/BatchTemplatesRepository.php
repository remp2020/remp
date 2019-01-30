<?php

namespace Remp\MailerModule\Repository;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Remp\MailerModule\Repository;

class BatchTemplatesRepository extends Repository
{
    protected $tableName = 'mail_job_batch_templates';

    public function getDashboardGraphDataForTypes(\DateTime $from, \DateTime $to)
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

    public function getDashboardAllMailsGraphData(\DateTime $from, \DateTime $to)
    {
        return $this->getTable()
            ->select('
                SUM(COALESCE(mail_job_batch_templates.sent, 0)) AS sent_mails,
                mail_job_batch.first_email_sent_at
            ')
            ->where('mail_job_batch.first_email_sent_at IS NOT NULL')
            ->where('DATE(mail_job_batch.first_email_sent_at) >= DATE(?)', $from->format('Y-m-d'))
            ->where('DATE(mail_job_batch.first_email_sent_at) <= DATE(?)', $to->format('Y-m-d'))
            ->group('DATE(mail_job_batch.first_email_sent_at)');
    }

    /**
     * @param $mailTypeId
     * @param \DateTime $from
     * @param \DateTime $to
     * @return \Remp\MailerModule\Selection
     */
    public function getDashboardDetailGraphData($mailTypeId, \DateTime $from, \DateTime $to)
    {
        return $this->getTable()
            ->select('
                DISTINCT mail_template.id,
                SUM(COALESCE(mail_template.sent, 0)) AS sent_mails,
                SUM(COALESCE(mail_template.clicked, 0)) AS clicked_mails,
                SUM(COALESCE(mail_template.opened, 0)) AS opened_mails,
                DATE(mail_job_batch.first_email_sent_at) AS label_date,
                DATE(mail_template.created_at) AS created_date')
            ->where('mail_job_batch.first_email_sent_at IS NOT NULL')
            ->where('mail_template.mail_type_id = ?', $mailTypeId)
            ->where('DATE(mail_job_batch.first_email_sent_at) >= DATE(?)', $from->format('Y-m-d'))
            ->where('DATE(mail_job_batch.first_email_sent_at) <= DATE(?)', $to->format('Y-m-d'))
            ->group('
                mail_job_batch_templates.id, 
                label_date
            ')
            ->order('label_date DESC');
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
