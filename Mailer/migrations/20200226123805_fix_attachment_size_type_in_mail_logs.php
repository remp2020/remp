<?php

use Phinx\Migration\AbstractMigration;

class FixAttachmentSizeTypeInMailLogs extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_logs')
            ->changeColumn('attachment_size', 'integer', ['null' => true, 'default' => null])
            ->update();
    }
}
