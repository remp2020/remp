<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMailTemplateLinksTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_template_links')
            ->addColumn('mail_template_id', 'integer')
            ->addColumn('url', 'string')
            ->addColumn('hash', 'string')
            ->addColumn('click_count', 'integer', ['default' => 0])
            ->addColumn('created_at', 'datetime')
            ->addForeignKey('mail_template_id', 'mail_templates', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addIndex('hash', ['unique' => true])
            ->create();

        $this->table('mail_template_link_clicks')
            ->addColumn('mail_template_link_id', 'integer')
            ->addColumn('clicked_at', 'datetime')
            ->addForeignKey('mail_template_link_id', 'mail_template_links', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('mail_template_link_clicks')
            ->drop()
            ->save();

        $this->table('mail_template_links')
            ->drop()
            ->save();
    }
}
