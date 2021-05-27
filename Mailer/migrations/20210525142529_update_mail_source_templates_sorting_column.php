<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateMailSourceTemplatesSortingColumn extends AbstractMigration
{
    public function up()
    {
        $this->execute('
            SET @s = 0;
            UPDATE mail_source_template SET sorting = (@s:=@s+1) ORDER BY sorting ASC;
        ');
    }
}
