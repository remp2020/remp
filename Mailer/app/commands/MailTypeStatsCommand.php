<?php

namespace Remp\MailerModule\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Remp\MailerModule\Repository\MailTypesRepository;
use Symfony\Component\Console\Output\OutputInterface;
use Remp\MailerModule\Repository\MailTypeStatsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;

class MailTypeStatsCommand extends Command
{
    /**
     * @var MailTypesRepository
     */
    private $mailTypesRepository;

    /**
     * @var MailTypeStatsRepository
     */
    private $mailTypeStatsRepository;
    /**
     * @var UserSubscriptionsRepository
     */
    private $userSubscriptionsRepository;

    public function __construct(
        MailTypesRepository $mailTypesRepository,
        MailTypeStatsRepository $mailTypeStatsRepository,
        UserSubscriptionsRepository  $userSubscriptionsRepository
    ) {
        parent::__construct();
        $this->mailTypesRepository = $mailTypesRepository;
        $this->mailTypeStatsRepository = $mailTypeStatsRepository;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    protected function configure()
    {
        $this->setName('stats:mail_type')
            ->setDescription('Run mail type stats update');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** UPDATING MAIL TYPE STATS *****</info>');
        $output->writeln('');

        $allMailTypeIds = $this->mailTypesRepository->getAllIds();

        $subscribersData = $this->userSubscriptionsRepository
            ->getAllSubscribersDataForMailTypes($allMailTypeIds);


        foreach ($subscribersData as $row) {
            if ($row->subscribed) {
                $this->mailTypeStatsRepository->add(
                    $row->mail_type_id,
                    $row->count
                );
            }
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }
}
