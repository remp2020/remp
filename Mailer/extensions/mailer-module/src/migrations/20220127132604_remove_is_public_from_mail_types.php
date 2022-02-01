<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveIsPublicFromMailTypes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_types')
            ->removeColumn('is_public')
            ->update();
    }

    public function down()
    {
        $this->output->writeln('Down migration is not available, up migration was destructive.');
    }
}
