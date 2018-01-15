<?php

namespace Remp\MailerModule\Commands;

use Mailgun\Model\Event\EventResponse;
use Nette\Utils\DateTime;
use Remp\MailerModule\Mailer\MailgunMailer;
use Remp\MailerModule\Repository\LogEventsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Sender\MailerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailgunEventsCommand extends Command
{
    /** @var MailgunMailer  */
    private $mailgun;

    private $logsRepository;

    private $logEventsRepository;

    public function __construct(
        MailerFactory $mailerFactory,
        LogsRepository $logsRepository,
        LogEventsRepository $logEventsRepository
    ) {
        parent::__construct();
        $this->mailgun = $mailerFactory->getMailer('remp-mailgun');
        $this->logsRepository = $logsRepository;
        $this->logEventsRepository = $logEventsRepository;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('mailgun:events')
            ->setDescription('Syncs latest mailgun events with local log')
            ->addArgument('timespan', InputArgument::OPTIONAL, 'Timespan ending with latest processed event for processing', '30s');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timespan = $input->getArgument('timespan');

        $output->writeln('');
        $output->writeln('<info>***** SYNCING MAILGUN EVENTS *****</info>');
        $output->writeln('');

        $latestEventTime = $this->logEventsRepository->latestEventTime();

        /** @var EventResponse $eventResponse */
        $eventResponse = $this->mailgun->mailer()->events()->get($this->mailgun->option('domain'), [
            'ascending' => true,
            'begin' => $latestEventTime->sub(\DateInterval::createFromDateString($timespan))->getTimestamp(),
            'event' => implode(' OR ', $this->logsRepository->mappedEvents()),
        ]);

        do {
            /** @var \Mailgun\Model\Event\Event $event */
            foreach ($eventResponse->getItems() as $event) {
                $userVariables = $event->getUserVariables();
                $date = $event->getEventDate()->format(DATE_RFC3339);

                $mappedEvent = $this->logsRepository->mapEvent($event->getEvent());
                if (!$mappedEvent) {
                    // unsupported event type
                    $output->writeln(sprintf("%s: ignoring event: %s (unsupported)", $date, $event->getEvent()));
                    continue;
                }

                $log = $this->logsRepository->findBySenderId($userVariables['mail_sender_id']);
                if (!$log) {
                    // missing mail_log record
                    $output->writeln(sprintf("%s: missing mail log record for mail_sender_id: %s", $date, $userVariables['mail_sender_id']));
                    continue;
                }

                $logEvent = $this->logEventsRepository->findByLogType($log->id, $event->getEvent());
                if ($logEvent) {
                    // already processed event logged
                    $output->writeln(sprintf("%s: event already logged, ignoring: %s (%s)", $date, $event->getRecipient(), $event->getEvent()));
                    continue;
                }

                $eventTimestamp = explode('.', $event->getTimestamp())[0];
                $date = DateTime::from($eventTimestamp);

                $this->logsRepository->update($log, [
                    $mappedEvent => $date,
                    'updated_at' => new DateTime(),
                ]);

                $this->logEventsRepository->addLog($log, $date, $event->getEvent());
                $output->writeln(sprintf("%s: event processed: %s (%s)", $date, $event->getRecipient(), $event->getEvent()));
            }

            $eventResponse = $this->mailgun->mailer()->events()->nextPage($eventResponse);
        } while (!empty($eventResponse->getItems()));
    }
}
