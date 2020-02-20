<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;
use Remp\MailerModule\Selection;

class LogsRepository extends Repository
{
    protected $tableName = 'mail_logs';

    protected $dataTableSearchable = ['email'];

    private $eventMap = [
        'delivered' => 'delivered_at',
        'clicked' => 'clicked_at',
        'opened' => 'opened_at',
        'complained' => 'spam_complained_at',
        'bounced' => 'hard_bounced_at',
        'failed' => 'dropped_at',
        'dropped' => 'dropped_at',
    ];

    private $bouncesMap = [
        'suppress-bounce' => 'hard_bounced_at',
        'suppress-complaint' => 'hard_bounced_at',
        'suppress-unsubscribe' => 'hard_bounced_at',
    ];

    public function add($email, $subject, $templateId, $jobId = null, $batchId = null, $mailSenderId = null, $attachmentSize = null, $context = null)
    {
        return $this->insert(
            $this->getInsertData($email, $subject, $templateId, $jobId, $batchId, $mailSenderId, $attachmentSize, $context)
        );
    }

    public function getInsertData($email, $subject, $templateId, $jobId = null, $batchId = null, $mailSenderId = null, $attachmentSize = null, $context = null)
    {
        return [
            'email' => $email,
            'subject' => $subject,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'mail_template_id' => $templateId,
            'mail_job_id' => $jobId,
            'mail_job_batch_id' => $batchId,
            'mail_sender_id' => $mailSenderId,
            'attachment_size' => $attachmentSize,
            'context' => $context,
        ];
    }

    public function getEmailLogs($email)
    {
        return $this->getTable()->where('email', $email)->order('created_at DESC');
    }

    public function getJobLogs($jobId)
    {
        return $this->getTable()->where('mail_job_id', $jobId)->order('created_at DESC');
    }

    public function findBySenderId($sender_id)
    {
        return $this->getTable()->where('mail_sender_id', $sender_id)->limit(1)->fetch();
    }

    public function getBatchTemplateStats($batchTemplate)
    {
        $columns = [
            'mail_job_batch_id',
            'COUNT(delivered_at) AS delivered',
            'COUNT(dropped_at) AS dropped',
            'COUNT(spam_complained_at) AS spam_complained',
            'COUNT(hard_bounced_at) AS hard_bounced',
            'COUNT(clicked_at) AS clicked',
            'COUNT(opened_at) AS opened',
            'COUNT(:mail_log_conversions.converted_at) AS converted',
        ];
        return $this->getTable()
            ->select(implode(',', $columns))
            ->where([
                'mail_job_batch_id' => $batchTemplate->mail_job_batch_id,
                'mail_template_id' => $batchTemplate->mail_template_id,
            ])
            ->limit(1)
            ->fetch();
    }

    public function getNonBatchTemplateStats($templateIds)
    {
        $columns = [
            'COUNT(created_at) AS sent',
            'COUNT(delivered_at) AS delivered',
            'COUNT(dropped_at) AS dropped',
            'COUNT(spam_complained_at) AS spam_complained',
            'COUNT(hard_bounced_at) AS hard_bounced',
            'COUNT(clicked_at) AS clicked',
            'COUNT(opened_at) AS opened',
            'COUNT(:mail_log_conversions.converted_at) AS converted',
        ];
        return $this->getTable()
            ->select(implode(',', $columns))
            ->where([
                'mail_template_id' => $templateIds,
                'mail_job_batch_id IS NULL',
            ])
            ->limit(1)
            ->fetch();
    }

    /**
     * @param $query
     * @param $order
     * @param $orderDirection
     * @param null $limit
     * @param null $offset
     * @param null $templateId
     * @return Selection
     */
    public function tableFilter($query, $order, $orderDirection, $limit = null, $offset = null, $templateId = null)
    {
        $selection = $this->getTable()
            ->order($order . ' ' . strtoupper($orderDirection));

        if ($templateId !== null) {
            $selection->where('mail_template_id = ?', $templateId);
        }

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }

            $selection->whereOr($where);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }

    public function alreadySentForJob($email, $jobId)
    {
        return $this->getTable()->where([
                'mail_logs.mail_job_id' => $jobId,
                'mail_logs.email' => $email
            ])->count('*') > 0;
    }

    public function alreadySentForEmail($mailTemplateCode, $email)
    {
        return $this->getTable()->where([
                'mail_logs.email' => $email,
                'mail_template.code' => $mailTemplateCode
            ])->count('*') > 0;
    }

    public function filterAlreadySent($emails, $mailTemplateCode, $jobId, $context = null)
    {
        $query = $this->getTable()->where([
            'mail_logs.email' => $emails,
            'mail_template.code' => $mailTemplateCode
        ])->whereOr([
            'mail_logs.email' => $emails,
            'mail_logs.mail_job_id' => $jobId,
        ]);

        if ($context) {
            $query->whereOr([
                'mail_logs.email' => $emails,
                'mail_logs.context' => $context,
            ]);
        }

        $alreadySentEmails = $query->select('email')->fetchPairs(null, 'email');

        return array_diff($emails, $alreadySentEmails);
    }

    public function alreadySentContext($context): bool
    {
        return $this->getTable()->where([
            'mail_logs.context' => $context,
        ])->count('*') > 0;
    }

    /**
     * @return string[]
     */
    public function mappedEvents(): array
    {
        return array_keys($this->eventMap);
    }

    /**
     * @param string $externalEvent
     * @param null|string $reason
     * @return string|null
     */
    public function mapEvent(string $externalEvent, ?string $reason): ?string
    {
        if (!isset($this->eventMap[$externalEvent])) {
            return null;
        }
        if ($externalEvent === 'failed' && in_array($reason, $this->bouncesMap)) {
            return $this->bouncesMap[$reason];
        }
        return $this->eventMap[$externalEvent];
    }
}
