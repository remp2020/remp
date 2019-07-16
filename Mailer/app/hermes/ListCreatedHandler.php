<?php

namespace Remp\MailerModule\Hermes;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\User\IUser;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class ListCreatedHandler implements HandlerInterface
{
    use LoggerAwareTrait;

    private $listsRepository;

    private $userProvider;

    private $userSubscriptionsRepository;

    public function __construct(
        ListsRepository $listsRepository,
        IUser $userProvider,
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        $this->listsRepository = $listsRepository;
        $this->userProvider = $userProvider;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['list_id'])) {
            throw new HermesException('unable to handle event: mail_type_id is missing');
        }

        $list = $this->listsRepository->find($payload['list_id']);
        if (!$list->auto_subscribe) {
            return true;
        }

        $page = 1;
        while ($users = $this->userProvider->list([], $page)) {
            foreach ($users as $user) {
                $this->logger->log(LogLevel::INFO, sprintf("Subscribing user: %s (%s).", $user['email'], $user['id']));
                if ($list->auto_subscribe) {
                    $this->userSubscriptionsRepository->subscribeUser($list, $user['id'], $user['email']);
                } else {
                    $this->userSubscriptionsRepository->unsubscribeUser($list, $user['id'], $user['email']);
                }
            }
            $page++;
        }
        return true;
    }
}
