<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RestoreForeignKeysAndIndices extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_templates')
            ->addIndex('mail_body_html', ['type' => 'fulltext'])
            ->update();
    }

    public function down(): void
    {
        $this->output->writeln("DOWN migration not possible due to complexity of the migration");
    }
}
