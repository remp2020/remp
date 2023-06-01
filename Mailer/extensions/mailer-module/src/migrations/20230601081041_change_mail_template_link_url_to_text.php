<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeMailTemplateLinkUrlToText extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_template_links')
            ->changeColumn('url', 'text')
            ->update();
    }

    public function down()
    {
        $this->table('mail_template_links')
            ->changeColumn('url', 'string')
            ->update();
    }
}
