<?php

use Phinx\Migration\AbstractMigration;

class MailSourceTemplate extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->table('mail_source_template')
            ->addColumn('title', 'string', ['null' => false])
            ->addColumn('content_html', 'text', ['null' => false, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
            ->addColumn('content_text', 'text', ['null' => false, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
            ->addColumn('sorting', 'integer', ['null' => false, 'default' => 100])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addColumn('generator', 'string', ['null' => false])
            ->addIndex('sorting')
            ->create();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('mail_source_template')->drop();
    }
}
