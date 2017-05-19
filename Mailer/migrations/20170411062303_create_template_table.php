<?php

use Phinx\Migration\AbstractMigration;

class CreateTemplateTable extends AbstractMigration
{
    public function change()
    {
        $this->table('templates')
            ->addColumn('code', 'string')
            ->addColumn('name', 'string')
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('from', 'string')
            ->addColumn('subject', 'string', ['null' => true])
            ->addColumn('template_text', 'text')
            ->addColumn('template_html', 'text')
            ->addColumn('source_template_id', 'integer', ['null' => true])
            ->addColumn('layout_id', 'integer')
            ->addColumn('list_id', 'integer')
            ->addTimestamps()
            ->addIndex('source_template_id')
            ->addIndex('layout_id')
            ->addIndex('list_id')
            ->addForeignKey('source_template_id', 'templates', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->addForeignKey('layout_id', 'layouts', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->addForeignKey('list_id', 'lists', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->save();

        $this->table('template_stats')
            ->addColumn('template_id', 'integer')
            ->addColumn('sent', 'integer', ['default' => 0])
            ->addColumn('delivered', 'integer', ['default' => 0])
            ->addColumn('dropped', 'integer', ['default' => 0])
            ->addColumn('spam_complained', 'integer', ['default' => 0])
            ->addColumn('hard_bounced', 'integer', ['default' => 0])
            ->addColumn('clicked', 'integer', ['default' => 0])
            ->addColumn('opened', 'integer', ['default' => 0])
            ->addIndex('template_id', ['unique' => true])
            ->addForeignKey('template_id', 'templates', 'id', array('delete' => 'RESTRICT', 'update' => 'CASCADE'))
            ->save();
    }
}
