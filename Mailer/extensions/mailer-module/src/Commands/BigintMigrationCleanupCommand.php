<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\Explorer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BigintMigrationCleanupCommand extends Command
{
    use DecoratedCommandTrait;

    public function __construct(
        private Explorer $database
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mail:bigint_migration_cleanup')
            ->setDescription('Deletes left-over table after migration to bigint.')
            ->addArgument(
                'table',
                InputArgument::REQUIRED,
                "Name of migrated table."
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migratedTable = $input->getArgument('table');

        if (!in_array($migratedTable, ['mail_user_subscriptions', 'mail_user_subscription_variants', 'autologin_tokens', 'mail_log_conversions', 'mail_logs'], true)) {
            $this->error("Table `{$migratedTable}` was not one of migrated tables.");
            return Command::FAILURE;
        }

        $v2Table = $migratedTable . '_v2';
        if ($this->tableExists($v2Table) && !$this->confirm("Migration table `{$v2Table}` still exists, are you sure that migration was successful?", false)) {
            $this->error("Cleanup cancelled.");
            return Command::FAILURE;
        }

        $tableToDrop = $migratedTable . '_old';
        if (!$this->tableExists($v2Table) && !$this->tableExists($tableToDrop)) {
            $this->warn("There are no migration tables in your database (`{$tableToDrop}` or `{$v2Table}`). Exiting command.");
            return Command::SUCCESS;
        }

        if (!$this->confirm("This command will permanently drop tables `{$tableToDrop}` and `{$v2Table}`. Make sure that your data was successfully migrated. Proceed?", false)) {
            $this->error("Cleanup cancelled.");
            return Command::FAILURE;
        }

        $this->database->query("DROP TABLE IF EXISTS {$v2Table};");
        $this->database->query("DROP TABLE IF EXISTS {$tableToDrop};");
        $this->info('Done.');
        return Command::SUCCESS;
    }

    private function tableExists(string $tableName): bool
    {
        return (bool) $this->database->query("SHOW TABLES LIKE '{$tableName}';")->getRowCount();
    }
}
