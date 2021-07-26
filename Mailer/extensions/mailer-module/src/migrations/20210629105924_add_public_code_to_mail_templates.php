<?php
declare(strict_types=1);

use Nette\Utils\Random;
use Phinx\Migration\AbstractMigration;

final class AddPublicCodeToMailTemplates extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_templates')
            ->addColumn('public_code', 'string', ['null' => true, 'after' => 'code'])
            ->update();

        $templates = $this->fetchAll('
            SELECT * FROM mail_templates
        ');

        foreach ($templates as $template) {
            $templateId = $template['id'];
            $public_code = Random::generate(8);

            $this->execute("
                UPDATE mail_templates SET public_code = '{$public_code}' WHERE id = {$templateId}
            ");
        }

        $this->table('mail_templates')
            ->changeColumn('public_code', 'string', ['null' => false])
            ->addIndex('public_code', ['unique' => true])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_templates')
            ->removeColumn('public_code')
            ->update();
    }
}
