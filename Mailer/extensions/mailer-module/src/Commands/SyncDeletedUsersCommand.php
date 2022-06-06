<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\Models\Users\IUser;
use Remp\MailerModule\Models\Users\UserManager;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncDeletedUsersCommand extends Command
{
    private const LIMIT = 1000;

    private $userProvider;

    private $userManager;

    private $userSubscriptionsRepository;

    public function __construct(
        IUser $userProvider,
        UserManager $userManager,
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        parent::__construct();
        $this->userProvider = $userProvider;
        $this->userManager = $userManager;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    protected function configure(): void
    {
        $this->setName('mail:sync-deleted-users')
            ->setDescription("Sync deleted users. Compares Mailer's users with CRM users and deletes user data for anonymized users.")
            ->addOption(
                'delete-deactivated',
                null,
                InputOption::VALUE_NONE,
                "Deletes also deactivated accounts.\nDoesn't affect anonymized accounts - user data for these are always removed."
            )
            ->addOption(
                'from-user-id',
                null,
                InputOption::VALUE_REQUIRED,
                "Start sync of deleted users from provided user_id.\nHelpful if you want to run command in batches."
            )
            ->addUsage('--delete-deactivated')
            ->addUsage('--from-user-id=42')
            ->addUsage('--from-user-id=42 --delete-deactivated');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<info>***** DELETE USERS ANONYMIZED IN CRM *****</info>');

        // include deactivated in UserProvider::list() in comparison with Mailer users
        $includeDeactivated = true;
        if ($input->getOption('delete-deactivated')) {
            $output->writeln("<info>***** <comment>--delete-deactivated</comment> used. Sync will remove also <comment>deactivated, non-anonymized</comment> users.</info>");
            $includeDeactivated = false;
        }

        $lastUserId = 0;
        $fromUserId = $input->getOption('from-user-id');
        if ($fromUserId) {
            $output->writeln("<info>***** <comment>--from-user-id</comment> used. Sync will start from user ID [<comment>{$fromUserId}</comment>].</info>");
            $lastUserId = $fromUserId;
        }

        $output->writeln('');

        // fetch & compare & remove user subscriptions in batches
        while ($userSubscriptions = $this->userSubscriptionsRepository->getTable()
            ->where('user_id >= ', $lastUserId)
            ->limit(self::LIMIT)
            ->fetchAssoc('user_id=user_email')) {
            // get CRM users
            $mailerUserIds = array_keys($userSubscriptions);
            $maxUserId = max($mailerUserIds);
            $output->writeln(' * Users [<info>' . $lastUserId . '-' . $maxUserId . '</info>]:');
            $lastUserId = $maxUserId;

            /** @var array<int, array> $crmUsers */
            $crmUsers = $this->userProvider->list($mailerUserIds, 1, $includeDeactivated);

            // compare mailer & crm users; get missing emails
            /** @var array<int, string> $missingUsers */
            $missingUsers = array_diff_key($userSubscriptions, $crmUsers);

            if (count($missingUsers) === 0) {
                $output->writeln('   * No users to delete.');
                continue;
            }

            $output->writeln('   * Deleting user data for emails:');
            foreach ($missingUsers as $missingUserId => $missingEmail) {
                $output->writeln("     - {$missingEmail} ({$missingUserId})");
            }

            try {
                $this->userManager->deleteUsers($missingUsers);
            } catch (\Exception $e) {
                $output->writeln('   * <error>Users were not deleted.</error>');
                throw $e;
            }

            $output->writeln('   * <info>Users were deleted.</info>');
            $output->writeln('');
        }

        $output->writeln('');
        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
