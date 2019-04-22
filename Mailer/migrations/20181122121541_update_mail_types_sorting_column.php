<?php

use Phinx\Migration\AbstractMigration;

class UpdateMailTypesSortingColumn extends AbstractMigration
{
    public function up()
    {
        $this->execute('
            SET @s = 0;
            update mail_types set sorting = (@s:=@s+1) order by mail_type_category_id, sorting asc;
        ');
    }
}
