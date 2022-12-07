<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeMailJobQueueIdColumnToBigint extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_job_queue')
            ->changeColumn('id', 'biginteger', ['identity' => true])
            ->save();
    }
}
