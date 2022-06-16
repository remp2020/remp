<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixChangeZeroMailTypePriorities extends AbstractMigration
{
    public function up()
    {
        $sql = <<<SQL
UPDATE mail_types
SET priority = DEFAULT(priority)
WHERE priority = 0;
SQL;
        $this->execute($sql);
    }

    public function down()
    {
        $this->output->writeln('Down migration is not available.');
    }
}
