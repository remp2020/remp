<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveHermesTasksOld extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('hermes_tasks_old')) {
            $this->table("hermes_tasks_old")->drop()->save();
        }
    }

    public function down()
    {
        $this->output->writeln('Down migration is not available. Migration dropped backup table not needed since 2019-07.');
    }
}
