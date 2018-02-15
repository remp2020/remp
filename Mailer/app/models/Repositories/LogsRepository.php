<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Context;
use Nette\Database\IRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;
use Remp\MailerModule\Selection;

class LogsRepository extends Repository
{
    protected $tableName = 'mail_logs';

    protected $startStatsDate;

    private $statsTotals = [];

    protected $dataTableSearchable = ['email'];

    private $eventMap = [
        'delivered' => 'delivered_at',
        'clicked' => 'clicked_at',
        'opened' => 'opened_at',
        'complained' => 'spam_complained_at',
        'bounced' => 'hard_bounced_at',
        'dropped' => 'dropped_at',
    ];

    public function __construct($startStatsDate, Context $database)
    {
        parent::__construct($database);
        $this->startStatsDate = $startStatsDate;
    }

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

    public function getStatsRate($mailTemplates, $field, $startTime = null, $endTime = null)
    {
        $ids = [];
        if (is_array($mailTemplates)) {
            foreach ($mailTemplates as $templateId) {
                $ids[] = $templateId;
            }
        }
        if (is_a($mailTemplates, IRow::class)) {
            $ids[] = $mailTemplates->id;
        }

        $statsKey = implode(',', $ids) . '-' . ($startTime ? $startTime->format('c') : '') . '-' . ($endTime ? $endTime->format('c') : '');

        $logs = $this->getTable()->where(['mail_template_id' => $ids, $field . ' > ? OR ' . $field . ' IS NULL' => $this->startStatsDate]);

        if ($startTime) {
            $logs->where(['created_at >= ?' => $startTime]);
        }
        if ($endTime) {
            $logs->where(['created_at <= ?' => $endTime]);
        }

        if (!isset($this->statsTotals[$statsKey])) {
            $this->statsTotals[$statsKey] = $logs->count('*');
        }
        $local = $logs->where([$field . ' NOT' => null])->count('*');
        $total = $this->statsTotals[$statsKey];
        return [
            'per' => $total ? 100 * $local / $total : 0,
            'value' => $local,
            'total' => $this->statsTotals[$statsKey],
        ];
    }

    public function getConversion($mailTemplates, $startTime = null, $endTime = null)
    {
        $ids = [];
        if (is_array($mailTemplates)) {
            foreach ($mailTemplates as $templateId) {
                $ids[] = $templateId;
            }
        }
        if (is_a($mailTemplates, IRow::class)) {
            $ids[] = $mailTemplates->id;
        }

        if (count($ids) == 0 || (count($ids) == 1 && !$ids[0])) {
            return [
                'per' => 0,
                'value' => 0,
                'total' => 0,
            ];
        }

        $timeWhere = '';
        if ($startTime) {
            $timeWhere .= " AND mail_logs.created_at >= '{$startTime->format('Y-m-d H:i:s')}' ";
        }
        if ($endTime) {
            $timeWhere .= " AND mail_logs.created_at <= '{$endTime->format('Y-m-d H:i:s')}' ";
        }

        $query = "SELECT count(*) AS count
            FROM payments
            INNER JOIN users ON users.id = payments.user_id
            INNER JOIN mail_logs ON mail_logs.email = users.email AND mail_logs.mail_template_id IN (" . implode(',', $ids) . ") {$timeWhere}
            LEFT JOIN recurrent_payments ON recurrent_payments.parent_payment_id = payments.id
            WHERE
                payments.status = 'paid' AND 
                payments.created_at BETWEEN mail_logs.created_at AND mail_logs.created_at + INTERVAL 2 DAY AND
                recurrent_payments.payment_id IS NULL
        ";

        $res = $this->getDatabase()->query($query);
        $local = 0;
        foreach ($res as $row) {
            $local = $row->count;
            break;
        }
        return $local;
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

    public function filterAlreadySentForContext($emails, $context)
    {
        $alreadySentEmails = $this->getTable()->where([
                'mail_logs.email' => $emails,
                'mail_logs.context' => $context,
        ])->select('email')->fetchPairs(null, 'email');

        return array_diff($emails, $alreadySentEmails);
    }

    public function filterAlreadySent($emails, $mailTemplateCode, $jobId)
    {
        $alreadySentEmails = $this->getTable()->where([
            'mail_logs.email' => $emails,
            'mail_template.code' => $mailTemplateCode
        ])->whereOr([
            'mail_logs.email' => $emails,
            'mail_logs.mail_job_id' => $jobId,
        ])->select('email')->fetchPairs(null, 'email');

        return array_diff($emails, $alreadySentEmails);
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
     * @return string|null
     */
    public function mapEvent(string $externalEvent): ?string
    {
        if (!isset($this->eventMap[$externalEvent])) {
            return null;
        }
        return $this->eventMap[$externalEvent];
    }
}
