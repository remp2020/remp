<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Tracker\EventOptions;
use Remp\MailerModule\Models\Tracker\ITracker;
use Remp\MailerModule\Models\Tracker\User;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\Handler\RetryTrait;
use Tomaj\Hermes\MessageInterface;

class TrackSubscribeUnsubscribeHandler implements HandlerInterface
{
    use RetryTrait;

    public function __construct(
        private ITracker $tracker,
        private MailTypesRepository $mailTypesRepository,
        private ListVariantsRepository $listVariantsRepository,
    ) {
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();

        if (!in_array($message->getType(), ['user-subscribed', 'user-unsubscribed', 'user-subscribed-variant'], true)) {
            throw new HermesException(
                "unable to handle event: wrong type '{$message->getType()}', only 'user-subscribed', 'user-unsubscribed' and 'user-subscribed-variant' types are allowed"
            );
        }

        if (!isset($payload['user_id'])) {
            throw new HermesException('unable to handle event: user_id is missing');
        }

        if (!isset($payload['mail_type_id'])) {
            throw new HermesException('unable to handle event: mail_type_id is missing');
        }

        if (!isset($payload['time'])) {
            throw new HermesException('unable to handle event: time is missing');
        }

        $options = new EventOptions();
        $options->setUser(new User([
            'id' => $payload['user_id']
        ]));

        $rtmParams = [
            'rtm_source' => $payload['rtm_source'] ?? null,
            'rtm_medium' => $payload['rtm_medium'] ?? null,
            'rtm_campaign' => $payload['rtm_campaign'] ?? null,
            'rtm_content' => $payload['rtm_content'] ?? null,
            'rtm_variant' => $payload['rtm_variant'] ?? null,
        ];

        $mailType = $this->mailTypesRepository->find($payload['mail_type_id']);
        $fields = [
            'mail_type' => $mailType->code
        ] + array_filter($rtmParams);

        if (isset($payload['mail_type_variant_id'])) {
            $variant = $this->listVariantsRepository->find($payload['mail_type_variant_id']);
            if ($variant) {
                $fields['mail_type_variant'] = $variant->code;
            }
        }

        $options->setFields($fields);

        $action = match ($message->getType()) {
            'user-subscribed' => 'subscribe',
            'user-unsubscribed' => 'unsubscribe',
            'user-subscribed-variant' => 'subscribe-variant'
        };

        $this->tracker->trackEvent(
            DateTime::from($payload['time']),
            'mail-type',
            $action,
            $options
        );

        return true;
    }
}
