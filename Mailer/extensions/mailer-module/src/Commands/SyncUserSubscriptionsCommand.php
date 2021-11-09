<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\Models\Users\IUser;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncUserSubscriptionsCommand extends Command
{
    private $userProvider;

    private $listsRepository;

    private $userSubscriptionsRepository;

    public function __construct(
        IUser $userProvider,
        ListsRepository $listsRepository,
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        parent::__construct();
        $this->userProvider = $userProvider;
        $this->listsRepository = $listsRepository;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    protected function configure(): void
    {
        $this->setName('mail:sync-user-subscriptions')
            ->setDescription('Gets all users from user base and subscribes them to emails based on the auto_subscribe flags');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<info>***** AUTO-SUBSCRIBING ALL USERS *****</info>');
        $output->writeln('(already existing records will not be touched)');
        $output->writeln('');

        $page = 1;
        $lists = $this->listsRepository->all();

        while ($users = $this->userProvider->list([], $page)) {
            $emails = [];
            foreach ($users as $user) {
                $emails[] = $user['email'];
            }

            $mailSubscriptions = $this->userSubscriptionsRepository->getTable()->where(['user_email' => $emails])->fetchAll();
            $processed = [];
            foreach ($mailSubscriptions as $mailSubscription) {
                $processed[$mailSubscription->user_email][$mailSubscription->mail_type_id] = true;
            }

            foreach ($users as $user) {
                $output->write(sprintf("Processing user: %s (%s) ... ", $user['email'], $user['id']));

                /** @var ActiveRow $list */
                foreach ($lists as $list) {
                    if (isset($processed[$user['email']][$list->id])) {
                        continue;
                    }

                    if ($list->auto_subscribe) {
                        $this->userSubscriptionsRepository->subscribeUser($list, $user['id'], $user['email']);
                    } else {
                        $this->userSubscriptionsRepository->unsubscribeUser($list, $user['id'], $user['email']);
                    }
                }

                $output->writeln('<info>OK!</info>');
            }
            $page++;
        }

        $output->writeln('');
        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
