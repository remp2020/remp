<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

final class RemoveEmptyTranslations extends AbstractMigration
{
    public function up(): void
    {
        $sql = <<<SQL
            DELETE FROM mail_snippet_translations where text = '' and html = '';
            DELETE FROM mail_layout_translations where layout_text = '' and layout_html = '';
SQL;
        $this->execute($sql);

    }

    public function down(): void
    {
        $this->output->writeln('Down migration is not possible.');
        throw new IrreversibleMigrationException();
    }
}
