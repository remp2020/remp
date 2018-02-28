<?php

namespace Remp\MailerModule\Commands;

use Nette\Utils\Json;
use Remp\MailerModule\Broker\ConsumerFactory;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailChangedWorkerCommand extends Command
{
    const TOPICS = ['user_email_changed'];

    private $consumerFactory;

    private $userSubscriptionsRepository;

    public function __construct(
        ConsumerFactory $consumerFactory,
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        parent::__construct();
        $this->consumerFactory = $consumerFactory;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('worker:change-email')
            ->setDescription('Start worker changing users email');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** EMAIL CHANGED WORKER *****</info>');
        $output->writeln('Listening for: ' . implode(', ', self::TOPICS));
        $output->writeln('');

        $consumer = $this->consumerFactory->getInstance(self::TOPICS);

        while (true) {
            $message = $consumer->consume(120000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    continue 2;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    continue 2;
                default:
                    throw new \Exception($message->errstr(), $message->err);
            }

            $message = Json::decode($message->payload, Json::FORCE_ARRAY);
            $originalEmail = $message['fields']['original_email'];
            $newEmail = $message['fields']['new_email'];

            $output->write(sprintf("Change user email %s to %s) ... ", $originalEmail, $newEmail));

            $subscriptions = $this->userSubscriptionsRepository->findByEmail($originalEmail);

            if (empty($subscriptions)) {
                throw new \Exception('Invalid original email provided by event: ' . $originalEmail);
            }

            foreach ($subscriptions as $subscription) {
                $this->userSubscriptionsRepository->update($subscription, ['user_email' => $newEmail]);
            }

            $output->writeln('<info>OK!</info>');
        }
    }
}
