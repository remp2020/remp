<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCodeColumnToMailLayoutsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_layouts')
            ->addColumn('code', 'string', ['null' => true, 'after' => 'id'])
            ->update();

        $layouts = $this->fetchAll('
            SELECT * FROM mail_layouts
        ');

        foreach ($layouts as $layout) {
            $layoutId = $layout['id'];
            $code = $layoutId . '_' . \Nette\Utils\Strings::webalize($layout['name']);

            $this->execute("
                UPDATE mail_layouts SET code = '{$code}' WHERE id = {$layoutId}
            ");
        }

        $this->table('mail_layouts')
            ->changeColumn('code', 'string', ['null' => false])
            ->addIndex('code', ['unique' => true])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_layouts')
            ->removeColumn('code')
            ->update();
    }
}
