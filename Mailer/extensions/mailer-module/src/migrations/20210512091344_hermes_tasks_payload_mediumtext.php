<?php
declare(strict_types=1);

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class HermesTasksPayloadMediumtext extends AbstractMigration
{
    public function up()
    {
        $this->table('hermes_tasks')
            ->changeColumn(
                'payload',
                AdapterInterface::PHINX_TYPE_TEXT,
                [
                    'null' => false,
                    'limit' => MysqlAdapter::TEXT_MEDIUM,
                ])
            ->update();
    }

    public function down()
    {
        $this->table('hermes_tasks')
            ->changeColumn(
                'payload',
                AdapterInterface::PHINX_TYPE_TEXT,
                [
                    'null' => false,
                    'limit' => MysqlAdapter::TEXT_REGULAR,
                ])
            ->update();
    }
}
