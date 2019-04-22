<?php

use Nette\Utils\Strings;
use Phinx\Migration\AbstractMigration;

class AddCodeToMailSourceTemplate extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_source_template')
            ->addColumn('code', 'string', ['null' => true, 'after' => 'title'])
            ->addIndex(['code'], ['unique' => true])
            ->update();

        foreach ($this->fetchAll('select * from mail_source_template') as $row) {
            $code = Strings::webalize($row['title']);
            $this->execute("UPDATE mail_source_template SET code='$code' WHERE id={$row['id']}");
        }

        $this->table('mail_source_template')
            ->changeColumn('code', 'string', ['null' => false])
            ->update();
    }
}
