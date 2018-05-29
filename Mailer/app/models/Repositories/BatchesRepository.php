<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\ActiveRow;
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

    const METHOD_RANDOM = 'random';

    const EDITABLE_STATUSES = [
        BatchesRepository::STATE_CREATED,
        BatchesRepository::STATE_UPDATED,
        BatchesRepository::STATE_READY,
    ];

    protected $tableName = 'mail_job_batch';

    public function add($jobId, $emailCount = null, $startAt = null, $method = 'random')
    {
        $result = $this->insert([
            'mail_job_id' => $jobId,
            'method' => $method,
            'max_emails' => $emailCount,
            'start_at' => new \DateTime($startAt),
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function addTemplate($batch, $template, $weight = 100)
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
            'status' => BatchesRepository::STATE_READY,
            'start_at <= ? OR start_at IS NULL' => new DateTime(),
        ])->limit(1)->fetch();
    }

    public function getBatchToSend()
    {
        return $this->getTable()
            ->select('mail_job_batch.*')
            ->where([
                'mail_job_batch.status' => [ BatchesRepository::STATE_PROCESSED, BatchesRepository::STATE_SENDING ]
            ])
            ->order(':mail_job_batch_templates.mail_template.mail_type.priority DESC')
            ->limit(1)
            ->fetch();
    }

    public function getBatchPriority(ActiveRow $batch)
    {
        return $batch->related('mail_job_batch_templates')->fetch()->mail_template->mail_type->priority;
    }

    public function getInProgressBatches($limit)
    {
        return $this->getTable()
            ->select('
                mail_job_batch.*,
                GROUP_CONCAT(:mail_job_batch_template.mail_template.name SEPARATOR \', \') AS template_name,
                :mail_job_batch_template.mail_job_id
            ')
            ->where([
                'mail_job_batch.status' => [
                    self::STATE_READY,
                    self::STATE_PROCESSING,
                    self::STATE_PROCESSED,
                    self::STATE_SENDING
                ]
            ])
            ->group('mail_job_batch.id')
            ->order('start_at ASC')
            ->limit($limit);
    }

    public function getLastDoneBatches($limit)
    {
        return $this->getTable()
            ->select('
                mail_job_batch.*,
                GROUP_CONCAT(:mail_job_batch_template.mail_template.name SEPARATOR \', \') AS template_name,
                :mail_job_batch_template.mail_job_id
            ')
            ->where([
                'mail_job_batch.status' => [
                    self::STATE_DONE
                ]
            ])
            ->group('mail_job_batch.id')
            ->order('mail_job_batch.last_email_sent_at DESC')
            ->limit($limit);
    }

    public function notEditableBatches($jobId)
    {
        return $this->getTable()
            ->select('*')
            ->where(['mail_job_id' => $jobId])
            ->where(['status NOT IN' => self::EDITABLE_STATUSES])
            ;
    }
}
