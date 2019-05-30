<?php

use Phinx\Migration\AbstractMigration;

class AddParamsToJobQueue extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_job_queue')
            ->addColumn('params', 'json', ['null' => true])
            ->update();
    }
}
