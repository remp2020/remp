<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFromIntoMailTemplateTranslations extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_template_translations')
            ->addColumn('from', 'string', ['after' => 'locale', 'null' => true])
            ->update();

        $query = "SELECT mail_templates.* FROM mail_templates JOIN mail_template_translations mtt ON mtt.mail_template_id = mail_templates.id";
        foreach ($this->fetchAll($query) as $row) {
            $this->execute("UPDATE mail_template_translations SET `from` = '{$row['from']}' WHERE mail_template_id = {$row['id']}");
        }

        $this->table('mail_template_translations')
            ->changeColumn('from', 'string', ['null' => false])
            ->update();
    }
}
