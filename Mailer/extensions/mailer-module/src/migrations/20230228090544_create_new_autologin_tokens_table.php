<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNewAutologinTokensTable extends AbstractMigration
{
    public function up(): void
    {
        $autologinTokensRowCount = $this->query('SELECT 1 FROM autologin_tokens LIMIT 1;')->fetch();
        if ($autologinTokensRowCount === false) {
            $this->table('autologin_tokens')
                ->changeColumn('id', 'biginteger', ['identity' => true])
                ->save();
        } else {
            $this->query("
                CREATE TABLE autologin_tokens_v2 LIKE autologin_tokens;
            ");

            $this->table('autologin_tokens_v2')
                ->changeColumn('id', 'biginteger', ['identity' => true])
                ->save();
        }
    }

    public function down()
    {
        $this->output->writeln('Down migration is not available.');
    }
}
