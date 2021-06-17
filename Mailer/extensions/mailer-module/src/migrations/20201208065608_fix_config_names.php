<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixConfigNames extends AbstractMigration
{
    public function up(): void
    {
        $sql = <<<SQL
UPDATE configs SET name = REPLACE(name, 'remp-', 'remp_');
UPDATE configs SET value = REPLACE(value, 'remp-', 'remp_') WHERE name = 'default_mailer';
SQL;
        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = <<<SQL
UPDATE configs SET value = REPLACE(value, 'remp_', 'remp-') WHERE name = 'default_mailer';
UPDATE configs SET name = REPLACE(name, 'remp_', 'remp-');
SQL;
        $this->execute($sql);
    }
}
