<?php

namespace Remp\MailerModule\Commands;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Broker\ConsumerFactory;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\User\IUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriberCommand extends Command
{
    const TOPICS = ["user_register"];

    private $consumerFactory;

    private $listsRepository;

    private $userSubscriptionsRepository;

    private $userProvider;

    public function __construct(
        ConsumerFactory $consumerFactory,
        ListsRepository $listsRepository,
        UserSubscriptionsRepository $userSubscriptionsRepository,
        IUser $userProvider
    ) {
        parent::__construct();
        $this->consumerFactory = $consumerFactory;
        $this->listsRepository = $listsRepository;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->userProvider = $userProvider;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('mail:subscribe-worker')
            ->setDescription('Worker subscribing new users to emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** SUBSCRIBER WORKER *****</info>');
        $output->writeln('Listening for: ' . implode(', ', self::TOPICS));
        $output->writeln('');

        $consumer = $this->consumerFactory->getInstance(self::TOPICS);

        while (true) {
            $message = $consumer->consume(120000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
//                    echo "No more messages; will wait for more\n";
                    continue 2;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
//                    echo "Timed out\n";
                    continue 2;
                default:
                    throw new \Exception($message->errstr(), $message->err);
            }

            $message = Json::decode($message->payload, Json::FORCE_ARRAY);
            $userId = $message['user']['id'];
            $output->write("Subscribing user: " . $userId . " ... ");

            $userList = $this->userProvider->list([$userId], 1);
            if (count($userList) == 0) {
                throw new \Exception('invalid user_id provided by event: ' . $userId);
            }
            $userInfo = $userList[$userId];

            $lists = $this->listsRepository->all();

            /** @var ActiveRow $list */
            foreach ($lists as $list) {
                $this->userSubscriptionsRepository->autoSubscribe($list, $userId, $userInfo['email']);
            }
            $output->writeln('<info>OK!</info>');
        }
    }
}
