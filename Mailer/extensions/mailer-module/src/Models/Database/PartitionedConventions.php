<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Database;

use Nette\Database\Conventions;
use Nette\Database\Conventions\DiscoveredConventions;

/**
 * InnoDB forbids foreign keys on partitioned tables, so mail_logs carries none, and every FK that
 * used to reference it was dropped. Nette's default DiscoveredConventions resolves table joins by
 * reading FK metadata, which means it can no longer resolve any join to the mail_logs.
 */
class PartitionedConventions implements Conventions
{
    // belongsTo: "<referencing table>.<key>" => [<referenced table>, <local FK column>]
    private const BELONGS_TO = [
        'mail_log_conversions.mail_log' => ['mail_logs', 'mail_log_id'],
        'mail_logs.mail_template' => ['mail_templates', 'mail_template_id'],
        'mail_logs.mail_job' => ['mail_jobs', 'mail_job_id'],
        'mail_logs.mail_job_batch' => ['mail_job_batch', 'mail_job_batch_id'],
    ];

    // hasMany: "<parent table>.<key>" => [<child table>, <child FK column>] (inverse of the above)
    private const HAS_MANY = [
        'mail_logs.mail_log_conversions' => ['mail_log_conversions', 'mail_log_id'],
        'mail_templates.mail_logs' => ['mail_logs', 'mail_template_id'],
        'mail_jobs.mail_logs' => ['mail_logs', 'mail_job_id'],
        'mail_job_batch.mail_logs' => ['mail_logs', 'mail_job_batch_id'],
    ];

    public function __construct(
        private readonly DiscoveredConventions $discoveredConventions,
    ) {
    }

    public function getPrimary(string $table): string|array|null
    {
        $primary = $this->discoveredConventions->getPrimary($table);
        if (is_array($primary)) {
            // Nette's SQL builder would fail on Array-to-string conversion if we returned composite index here. This
            // is a tradeoff in favor of NetteDB queries instead of writing raw queries.
            return reset($primary);
        }
        return $primary;
    }

    public function getBelongsToReference(string $table, string $key): ?array
    {
        return self::BELONGS_TO["$table.$key"] ?? $this->discoveredConventions->getBelongsToReference($table, $key);
    }

    public function getHasManyReference(string $table, string $key): ?array
    {
        return self::HAS_MANY["$table.$key"] ?? $this->discoveredConventions->getHasManyReference($table, $key);
    }
}
