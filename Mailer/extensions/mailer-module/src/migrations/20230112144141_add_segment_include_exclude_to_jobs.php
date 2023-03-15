<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSegmentIncludeExcludeToJobs extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_jobs')
            ->addColumn('segments', 'json')
            ->save();

        $mailJobsSegments = $this->query("SELECT id, segment_code, segment_provider FROM mail_jobs");
        foreach ($mailJobsSegments as $mailJobsSegment) {
            $sql = <<<SQL
UPDATE mail_jobs SET segments = '{
"include": [{
    "provider": "{$mailJobsSegment['segment_provider']}",
    "code": "{$mailJobsSegment['segment_code']}"
}],
"exclude": []
}'
WHERE id = {$mailJobsSegment['id']}
SQL;
            $this->query($sql);
        }

        $this->table('mail_jobs')
            ->removeColumn('segment_code')
            ->removeColumn('segment_provider')
            ->save();
    }

    public function down(): void
    {
        $this->table('mail_jobs')
            ->addColumn('segment_code', 'string')
            ->addColumn('segment_provider', 'string')
            ->save();

        $sql = <<<SQL
UPDATE mail_jobs 
SET segment_code = JSON_UNQUOTE(JSON_EXTRACT(mail_jobs.segments, '$.include[0].code')),
    segment_provider = JSON_UNQUOTE(JSON_EXTRACT(mail_jobs.segments, '$.include[0].provider'))
SQL;
        $this->query($sql);

        $this->table('mail_jobs')
            ->removeColumn('segments')
            ->save();
    }
}
