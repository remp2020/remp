<?php

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\User\IUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncUserSubscriptionsCommand extends Command
{
    private $userProvider;

    public function __construct(IUser $userProvider) {
        parent::__construct();
        $this->userProvider = $userProvider;
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
        $page = 1;

        while ($users = $this->userProvider->list([], $page)) {
            dump($users);die;
            // TODO: subscribe users

            $page++;
        }
    }
}