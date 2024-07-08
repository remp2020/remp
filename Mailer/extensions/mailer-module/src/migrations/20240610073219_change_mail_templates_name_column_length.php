<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeMailTemplatesNameColumnLength extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_templates')
            ->changeColumn('name', 'string', [
                'length' => 768,
                'null' => false,
            ])
            ->update();
    }

    public function down(): void
    {
        $this->output->writeln('Down migration is not available. Reverting the column length may cause data truncation.');
    }
}
