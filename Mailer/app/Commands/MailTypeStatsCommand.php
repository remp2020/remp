<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Symfony\Component\Console\Output\OutputInterface;
use Remp\MailerModule\Repositories\MailTypeStatsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;

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

    protected function configure(): void
    {
        $this->setName('mail:subscriber-stats')
            ->setDescription('Run mail type stats update');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<info>***** UPDATING MAIL TYPE STATS *****</info>');
        $output->writeln('');

        $allMailTypes = $this->mailTypesRepository->all()->fetchPairs('id', 'title');
        $allMailTypeIds = array_keys($allMailTypes);

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

        return Command::SUCCESS;
    }
}
