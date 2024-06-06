<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Models\Users\IUser;
use Tomaj\Hermes\Emitter;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class ListCreatedHandler implements HandlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ListsRepository $listsRepository,
        private readonly IUser $userProvider,
        private readonly UserSubscriptionsRepository $userSubscriptionsRepository,
        private readonly Explorer $database,
        private Emitter $emitter,
    ) {
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['list_id'])) {
            throw new HermesException('unable to handle event: mail_type_id is missing');
        }

        $list = $this->listsRepository->find($payload['list_id']);

        if ($payload['copy_subscribers'] ?? false) {
            $sql = <<<SQL
                INSERT INTO mail_user_subscriptions (user_id, user_email, mail_type_id, subscribed, created_at, updated_at, rtm_source, rtm_medium, rtm_campaign, rtm_content)
                SELECT user_id, user_email, ?, 1, ?, ?, rtm_source, rtm_medium, rtm_campaign, rtm_content
                FROM mail_user_subscriptions
                WHERE mail_type_id = ? AND subscribed = 1
            SQL;
            $this->database->query(
                $sql,
                $payload['list_id'],
                new DateTime(),
                new DateTime(),
                $payload['source_list_id']
            );

            $subscribers = $this->userSubscriptionsRepository
                ->getTable()
                ->where('mail_type_id', $payload['list_id']);
            foreach ($subscribers as $subscriber) {
                $this->emitter->emit(new HermesMessage('user-subscribed', [
                    'user_id' => $subscriber->user_id,
                    'user_email' => $subscriber->user_email,
                    'mail_type_id' => $payload['list_id'],
                    'send_welcome_email' => false,
                    'time' => new DateTime(),
                    'rtm_source' => $subscriber->rtm_source,
                    'rtm_medium' => $subscriber->rtm_medium,
                    'rtm_campaign' => $subscriber->rtm_campaign,
                    'rtm_content' => $subscriber->rtm_content,
                ]));
            }
        } else {
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
        }

        return true;
    }
}
