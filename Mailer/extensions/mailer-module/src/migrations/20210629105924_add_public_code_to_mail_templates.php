<?php
declare(strict_types=1);

use Nette\Utils\Random;
use Phinx\Migration\AbstractMigration;

final class AddPublicCodeToMailTemplates extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET foreign_key_checks = 0');

        // so we can use inplace algorithm
        $this->table('mail_templates')->removeIndex('mail_body_html')->update();

        $this->execute("
ALTER TABLE mail_templates ADD COLUMN public_code VARCHAR(255) NULL AFTER code, ALGORITHM=INPLACE, LOCK=NONE;
CREATE UNIQUE INDEX idx_public_code ON mail_templates(public_code);
UPDATE mail_templates SET public_code = (LEFT(MD5(ROUND(RAND(id)*4294967296)), 8));
ALTER TABLE mail_templates MODIFY COLUMN public_code VARCHAR(255) NOT NULL AFTER code, ALGORITHM=INPLACE, LOCK=NONE;
        ");

        $this->execute('SET foreign_key_checks = 1');
    }

    public function down(): void
    {
        $this->output->writeln("DOWN migration not possible due to complexity of the migration");
    }

}
