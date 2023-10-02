<?php

namespace Remp\MailerModule\Commands;

use DateInterval;
use DateTime;
use Exception;
use Remp\MailerModule\Models\Crm\Client;
use Remp\MailerModule\Models\Crm\UserNotFoundException;
use Remp\MailerModule\Repositories\LogsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateCrmEmailsCommand extends Command
{
    public const COMMAND_NAME = "crm:validate-emails";
    
    public function __construct(
        private LogsRepository $mailLogRepository,
        private Client $client
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Validates mail sent in the last (by default) 10 minutes.')
        ->addArgument(
            "interval",
            InputArgument::OPTIONAL,
            "How far back the validation interval should extend. By default 10 minutes.",
            'PT10M',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $this->client;
        if (is_null($client)) {
            $output->writeln("<error>ERROR</error>: CRM client was not initialized, check your config.local.neon.");
            return Command::FAILURE;
        }

        $interval = $input->getArgument('interval');
        try {
            $interval = new DateInterval($interval);
        } catch (Exception $e) {
            $output->writeln("Failed to parse interval <comment>{$interval}</comment>:\n" . $e->getMessage());
            return Command::FAILURE;
        }

        $deliveredAtFrom = (new DateTime())->sub($interval);

        $emails = $this->mailLogRepository->getTable()
            ->select('DISTINCT email')
            ->where('delivered_at >= ?', $deliveredAtFrom)
            ->fetchPairs(value: 'email');

        $now = new DateTime();

        $before = (clone $now)->sub($interval);
        $output->writeln(sprintf(
            "Validating %d emails, from <info>%s</info> to <info>%s</info>.",
            count($emails),
            $before->format(\DateTimeInterface::RFC3339),
            $now->format(\DateTimeInterface::RFC3339),
        ));

        $client->validateMultipleEmails($emails);
        return Command::SUCCESS;
    }
}
