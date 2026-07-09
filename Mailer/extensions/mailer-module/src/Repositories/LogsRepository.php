<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Database\Table\ActiveRow as NetteActiveRow;
use Nette\Utils\DateTime;

class LogsRepository extends Repository
{
    use NewTableDataMigrationTrait;

    /**
     * Delivery events normally arrive within a few days of sending. Callers doing a
     * mail_sender_id lookup use this as the created_at lower bound so MySQL can prune
     * to recent partitions instead of scanning the whole (500M+ row) table.
     */
    public const SENDER_ID_LOOKUP_WINDOW_DAYS = 14;

    protected $tableName = 'mail_logs';

    protected array $dataTableSearchable = ['email'];

    private array $eventMap = [
        'delivered' => 'delivered_at',
        'clicked' => 'clicked_at',
        'opened' => 'opened_at',
        'complained' => 'spam_complained_at',
        'bounced' => 'dropped_at',
        'failed' => 'dropped_at',
        'dropped' => 'dropped_at',
    ];

    private array $bouncesMap = [
        'suppress-bounce' => 'dropped_at',
        'suppress-complaint' => 'dropped_at',
        'suppress-unsubscribe' => 'dropped_at',
    ];

    public function allForEmail(string $email): Selection
    {
        return $this->allForEmails([$email]);
    }

    /**
     * @param array<string> $emails
     */
    public function allForEmails(array $emails): Selection
    {
        return $this->getTable()->where('email', $emails);
    }

    public function add(
        string $email,
        string $subject,
        int $templateId,
        ?int $jobId = null,
        ?int $batchId = null,
        ?string $mailSenderId = null,
        ?int $attachmentSize = null,
        ?string $context = null,
        ?int $userId = null,
    ): ActiveRow {
        return $this->insert(
            $this->getInsertData($email, $subject, $templateId, $jobId, $batchId, $mailSenderId, $attachmentSize, $context, $userId)
        );
    }

    public function getInsertData(
        string $email,
        string $subject,
        int $templateId,
        ?int $jobId = null,
        ?int $batchId = null,
        ?string $mailSenderId = null,
        ?int $attachmentSize = null,
        ?string $context = null,
        ?int $userId = null
    ): array {
        return [
            'email' => $email,
            'user_id' => $userId,
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

    /**
     * @param array<string> $emails
     */
    public function deleteAllForEmails(array $emails): int
    {
        if (count($emails) === 0) {
            return 0;
        }

        return $this->getTable()->where([
            'email' => $emails
        ])->delete();
    }

    public function getEmailLogs(string $email): Selection
    {
        return $this->getTable()->where('email', $email)->order('created_at DESC');
    }

    public function getJobLogs(int $jobId): Selection
    {
        return $this->getTable()->where('mail_job_id', $jobId)->order('created_at DESC');
    }

    /**
     * findBySenderId finds the first log row matching $senderId.
     *
     * When $since is provided the query also adds a created_at >= $since predicate,
     * which lets the MySQL optimizer prune old partitions and touch only the recent
     * months when delivery events normally arrive (typically within a few days of
     * sending). If no row is found within the window the method retries without
     * the bound so that rare late events are still matched.
     */
    public function findBySenderId(string $senderId, ?\DateTimeInterface $since = null): ?ActiveRow
    {
        if ($since !== null) {
            /** @var ActiveRow $row */
            $row = $this->getTable()
                ->where('mail_sender_id', $senderId)
                ->where('created_at >= ?', $since)
                ->limit(1)
                ->fetch();

            if ($row !== null) {
                return $row;
            }
            // Rare late event: fall back to a full-table scan.
        }

        /** @var ActiveRow $row */
        $row = $this->getTable()->where('mail_sender_id', $senderId)->limit(1)->fetch();

        return $row;
    }

    /**
     * Returns all log rows matching $senderId.
     *
     * When $since is provided the query adds a created_at >= $since predicate for
     * partition pruning.  Unlike findBySenderId there is no automatic fallback here;
     * callers that need the fallback behaviour should handle it themselves.
     */
    public function findAllBySenderId(string $senderId, ?\DateTimeInterface $since = null): Selection
    {
        $query = $this->getTable()->where('mail_sender_id', $senderId);
        if ($since !== null) {
            $query = $query->where('created_at >= ?', $since);
        }
        return $query;
    }

    public function getBatchTemplateStats(ActiveRow $batchTemplate): ?ActiveRow
    {
        $columns = [
            'mail_job_batch_id',
            'COUNT(delivered_at) AS delivered',
            'COUNT(dropped_at) AS dropped',
            'COUNT(spam_complained_at) AS spam_complained',
            'COUNT(clicked_at) AS clicked',
            'COUNT(opened_at) AS opened'
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

    public function getNonBatchTemplateStats(array $templateIds): ?ActiveRow
    {
        $columns = [
            'COUNT(created_at) AS sent',
            'COUNT(delivered_at) AS delivered',
            'COUNT(dropped_at) AS dropped',
            'COUNT(spam_complained_at) AS spam_complained',
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

    public function tableFilter(
        string $query,
        string $order,
        string $orderDirection,
        ?int $limit = null,
        ?int $offset = null,
        ?int $templateId = null,
        ?DateTime $createdAtFrom = null,
        ?DateTime $createdAtTo = null
    ): Selection {
        $selection = $this->getTable()
            ->order($order . ' ' . strtoupper($orderDirection));

        if ($templateId !== null) {
            $selection->where('mail_template_id = ?', $templateId);
        }

        if ($createdAtFrom !== null) {
            $selection->where('created_at >= ?', $createdAtFrom);
        }

        if ($createdAtTo !== null) {
            $selection->where('created_at <= ?', $createdAtTo);
        }

        if (!empty($query)) {
            $selection->where('email = ?', $query);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }

    public function alreadySentForJob(string $email, int $jobId): bool
    {
        return $this->getTable()->where([
            'mail_logs.mail_job_id' => $jobId,
            'mail_logs.email' => $email
        ])->count('*') > 0;
    }

    public function alreadySentForEmail(string $mailTemplateCode, string $email): bool
    {
        return $this->getTable()->where([
            'mail_logs.email' => $email,
            'mail_template.code' => $mailTemplateCode
        ])->count('*') > 0;
    }

    /**
     * @deprecated Method is not performant due to unnecessary joins. Use filterAlreadySentV2 instead.
     */
    public function filterAlreadySent(array $emails, string $mailTemplateCode, int $jobId, ?string $context = null): array
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

    public function filterAlreadySentV2(
        array $emails,
        NetteActiveRow $mailTemplate,
        NetteActiveRow $job,
        ?string $context = null
    ) {
        $query = $this->getTable()->where('CONVERT(email USING UTF8)', $emails);

        $orCondition = [
            'mail_template_id' => $mailTemplate->id,
            'mail_job_id' => $job->id,
        ];
        if ($context) {
            $orCondition['context'] = $context;
        }

        $alreadySentEmails = $query
            ->select('email')
            ->whereOr($orCondition)
            ->fetchPairs(null, 'email');

        return array_diff($emails, $alreadySentEmails);
    }

    public function alreadySentContext(string $email, string $context): bool
    {
        return $this->getTable()->where([
            'mail_logs.email' => $email,
            'mail_logs.context' => $context,
        ])->count('*') > 0;
    }

    public function mappedEvents(): array
    {
        return array_keys($this->eventMap);
    }

    public function mapEvent(string $externalEvent, ?string $reason): ?string
    {
        if (!isset($this->eventMap[$externalEvent])) {
            return null;
        }
        if ($externalEvent === 'failed' && in_array($reason, $this->bouncesMap, true)) {
            return $this->bouncesMap[$reason];
        }
        return $this->eventMap[$externalEvent];
    }

    public function insert(array $data)
    {
        $result = parent::insert($data);
        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->insert($result->toArray());
        }
        return $result;
    }

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $data['updated_at'] = new \DateTime();
        $result = parent::update($row, $data);
        if ($this->newTableDataMigrationIsRunning()) {
            // Include created_at in the WHERE clause so the query prunes to the
            // correct partition on the shadow table during the migration window.
            $this->getNewTable()->where(['id' => $row->id, 'created_at' => $row->created_at])->update($data);
        }
        return $result;
    }
}
