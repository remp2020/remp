<?php

namespace Remp\MailerModule\Commands;

use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\User\IUser;
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

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('mail:sync-user-subscriptions')
            ->setDescription('Gets all users from user base and subscribes them to emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** AUTO-SUBSCRIBING ALL USERS *****</info>');
        $output->writeln('');

        $page = 1;

        while ($users = $this->userProvider->list([], $page)) {
            foreach ($users as $user) {
                $output->write(sprintf("Subscribing user: %s (%s) ... ", $user['email'], $user['id']));
                $lists = $this->listsRepository->all();

                /** @var ActiveRow $list */
                foreach ($lists as $list) {
                    $this->userSubscriptionsRepository->autoSubscribe($list, $user['id'], $user['email']);
                }

                $output->writeln('<info>OK!</info>');
            }
            $page++;
        }
    }
}
