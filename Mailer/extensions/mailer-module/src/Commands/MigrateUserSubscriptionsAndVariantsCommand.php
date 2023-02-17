<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\EnvironmentConfig;
use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateUserSubscriptionsAndVariantsCommand extends Command
{
    use RedisClientTrait;

    public const USER_SUBSCRIPTIONS_AND_VARIANTS_MIGRATION_IS_RUNNING = 'user_subscriptions_and_variants_migration_running';

    public const COMMAND_NAME = "mail:migrate-user-subscriptions-and-variants";

    public function __construct(
        private Explorer $database,
        private UserSubscriptionsRepository $userSubscriptionsRepository,
        private UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository,
        private EnvironmentConfig $environmentConfig,
        RedisClientFactory $redisClientFactory,
    ) {
        parent::__construct();

        $this->redisClientFactory = $redisClientFactory;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Migrate user subscriptions and variants data to new table.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('STARTING `mail_user_subscriptions` AND `mail_user_subscription_variants` TABLE DATA MIGRATION');
        $output->writeln('');

        $userSubscriptionsTableName = $this->userSubscriptionsRepository->getTable()->getName();
        $userSubscriptionsV2TableName = $this->userSubscriptionsRepository->getNewTable()->getName();

        $userSubscriptionVariantsTableName = $this->userSubscriptionVariantsRepository->getTable()->getName();
        $userSubscriptionVariantsV2TableName = $this->userSubscriptionVariantsRepository->getNewTable()->getName();

        // Set migration running/start time flag in redis
        $migrationStartTime = new DateTime();
        if ($this->redis()->exists(self::USER_SUBSCRIPTIONS_AND_VARIANTS_MIGRATION_IS_RUNNING)) {
            $migrationStartTime = new DateTime($this->redis()->get(self::USER_SUBSCRIPTIONS_AND_VARIANTS_MIGRATION_IS_RUNNING));
        } else {
            $this->redis()->set(self::USER_SUBSCRIPTIONS_AND_VARIANTS_MIGRATION_IS_RUNNING, $migrationStartTime);
        }

        $this->database->query("
            SET FOREIGN_KEY_CHECKS=0;
            SET UNIQUE_CHECKS=0;
        ");

        // Paging LOOP
        $pageSize = 10000;
        while (true) {
            $lastMigratedId = $this->database
                ->query("SELECT id FROM `{$userSubscriptionsV2TableName}` WHERE created_at <= ? ORDER BY id DESC LIMIT 1", $migrationStartTime)
                ->fetch()
                ?->id ?? 0;

            $maxId = $this->database
                ->query("SELECT id FROM `{$userSubscriptionsTableName}` WHERE created_at <= ? ORDER BY id DESC LIMIT 1", $migrationStartTime)
                ->fetch()
                ?->id ?? 0;

            if ($maxId === 0 || $lastMigratedId === $maxId) {
                break;
            }

            $this->database->query("
                INSERT IGNORE INTO `{$userSubscriptionsV2TableName}` (`id`, `user_id`, `user_email`, `mail_type_id`, `created_at`, `updated_at`, `subscribed`, `rtm_source`, `rtm_medium`, `rtm_campaign`, `rtm_content`)
                SELECT `id`, `user_id`, `user_email`, `mail_type_id`, `created_at`, `updated_at`, `subscribed`, `rtm_source`, `rtm_medium`, `rtm_campaign`, `rtm_content`
                FROM `{$userSubscriptionsTableName}`
                WHERE id > {$lastMigratedId}
                ORDER BY id ASC
                LIMIT {$pageSize}
            ");

            $this->database->query("
                INSERT IGNORE INTO `{$userSubscriptionVariantsV2TableName}` (`id`, `mail_user_subscription_id`, `mail_type_variant_id`, `created_at`)
                SELECT `{$userSubscriptionVariantsTableName}`.`id`, `mail_user_subscription_id`, `mail_type_variant_id`, `created_at`
                FROM `{$userSubscriptionVariantsTableName}`
                WHERE `mail_user_subscription_id` IN (SELECT * FROM (SELECT `id` FROM `{$userSubscriptionsTableName}` WHERE id > {$lastMigratedId} ORDER BY id ASC LIMIT {$pageSize}) as t)
            ");

            $remaining = $maxId-$lastMigratedId;
            $output->write("\r\e[0KMIGRATED IDs: {$lastMigratedId} / {$maxId} (REMAINING: {$remaining})");
        }

        $output->writeln('');
        $output->writeln('DATA MIGRATED');
        $output->writeln('');
        $output->writeln('UPDATING ROWS DIFFERENCES AND INSERTING MISSING ROWS');

        $this->fixTableDifferences(
            $userSubscriptionsTableName,
            $userSubscriptionsV2TableName,
            $userSubscriptionVariantsTableName,
            $userSubscriptionVariantsV2TableName,
            $migrationStartTime
        );

        $output->writeln('');
        $output->writeln('SETUPING AUTO_INCREMENT');

        // Sat AUTO_INCREMENT for new tables to old table values
        $dbName = $this->environmentConfig->get('DB_NAME');
        $this->database->query("
            SELECT MAX(id)+10000 INTO @AutoInc FROM {$userSubscriptionsTableName};

            SET @s:=CONCAT('ALTER TABLE `{$dbName}`.`{$userSubscriptionsV2TableName}` AUTO_INCREMENT=', @AutoInc);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            SELECT MAX(id)+10000 INTO @AutoInc FROM {$userSubscriptionVariantsTableName};

            SET @s:=CONCAT('ALTER TABLE `{$dbName}`.`{$userSubscriptionVariantsV2TableName}` AUTO_INCREMENT=', @AutoInc);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $output->writeln('');
        $output->writeln('RENAMING TABLES');

        // Rename tables
        $this->database->query("
            ANALYZE TABLE {$userSubscriptionsV2TableName};
            ANALYZE TABLE {$userSubscriptionVariantsV2TableName};
            RENAME TABLE {$userSubscriptionsTableName} TO {$userSubscriptionsTableName}_old,
            {$userSubscriptionsV2TableName} TO {$userSubscriptionsTableName},
            {$userSubscriptionVariantsTableName} TO {$userSubscriptionVariantsTableName}_old,
            {$userSubscriptionVariantsV2TableName} TO {$userSubscriptionVariantsTableName};
        ");

        $output->writeln('');
        $output->writeln('UPDATING ROWS DIFFERENCES AND INSERTING MISSING ROWS');

        $this->fixTableDifferences(
            $userSubscriptionsTableName . '_old',
            $userSubscriptionsTableName,
            $userSubscriptionVariantsTableName . '_old',
            $userSubscriptionVariantsTableName,
            $migrationStartTime
        );

        $this->database->query("
            SET FOREIGN_KEY_CHECKS=1;
            SET UNIQUE_CHECKS=1;
        ");

        // Remove migration running flag in redis
        $this->redis()->del(self::USER_SUBSCRIPTIONS_AND_VARIANTS_MIGRATION_IS_RUNNING);

        $output->writeln('');
        $output->writeln('DATA MIGRATED SUCCESSFULLY');
        return Command::SUCCESS;
    }

    public function fixTableDifferences(
        string $userSubscriptionsFromTable,
        string $userSubscriptionsToTable,
        string $userSubscriptionVariantsFromTable,
        string $userSubscriptionVariantsToTable,
        DateTime $updatedAfter
    ) {
        $this->database->query("
            UPDATE {$userSubscriptionsToTable} us_to
            JOIN {$userSubscriptionsFromTable} us_from on us_to.id = us_from.id
            SET us_to.user_id = us_from.user_id,
                us_to.user_email = us_from.user_email,
                us_to.mail_type_id = us_from.mail_type_id,
                us_to.created_at = us_from.created_at,
                us_to.updated_at = us_from.updated_at,
                us_to.subscribed = us_from.subscribed,
                us_to.rtm_source = us_from.rtm_source,
                us_to.rtm_medium = us_from.rtm_medium,
                us_to.rtm_campaign = us_from.rtm_campaign,
                us_to.rtm_content = us_from.rtm_content
            WHERE us_from.updated_at > ?
                AND (us_to.updated_at IS NULL OR us_from.updated_at != us_to.updated_at)
        ", $updatedAfter);

        $missingIds = $this->database->query("
            SELECT `id` FROM `{$userSubscriptionsFromTable}`
            WHERE created_at > ?
            AND `id` NOT IN (
                SELECT `id` FROM `{$userSubscriptionsToTable}` WHERE created_at > ?
            )
        ", $updatedAfter, $updatedAfter)->fetchFields();

        if ($missingIds) {
            $this->database->query("
            INSERT IGNORE INTO `{$userSubscriptionsToTable}` (`id`, `user_id`, `user_email`, `mail_type_id`, `created_at`, `updated_at`, `subscribed`, `rtm_source`, `rtm_medium`, `rtm_campaign`, `rtm_content`)
            SELECT `id`, `user_id`, `user_email`, `mail_type_id`, `created_at`, `updated_at`, `subscribed`, `rtm_source`, `rtm_medium`, `rtm_campaign`, `rtm_content`
            FROM `{$userSubscriptionsFromTable}`
            WHERE `id` IN ?
        ", $missingIds);

            $this->database->query("
            INSERT IGNORE INTO `{$userSubscriptionVariantsToTable}` (`id`, `mail_user_subscription_id`, `mail_type_variant_id`, `created_at`)
            SELECT `id`, `mail_user_subscription_id`, `mail_type_variant_id`, `created_at`
            FROM `{$userSubscriptionVariantsFromTable}`
            WHERE `mail_log_id` IN ?
        ", $missingIds);
        }
    }
}
