<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddParamsColumnToMailTemplatesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_templates')
            ->addColumn('params', 'json', ['null' => true, 'after' => 'extras'])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_templates')
            ->removeColumn('params')
            ->update();
    }
}
