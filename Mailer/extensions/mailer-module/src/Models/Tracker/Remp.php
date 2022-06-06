<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Tracker;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Ramsey\Uuid\Uuid;
use Tracy\Debugger;
use Tracy\Logger;

class Remp implements ITracker
{
    const TRACK_EVENT = '/track/event';

    const TRACK_COMMERCE = '/track/commerce';

    private $client;

    private $token;

    private $trackerHost;

    public function __construct(string $trackerHost, string $token)
    {
        $this->token = $token;
        $this->trackerHost = $trackerHost;
        $this->client = new Client([
            'base_uri' => $trackerHost,
        ]);
    }

    public function trackEvent(DateTime $dateTime, string $category, string $action, EventOptions $options)
    {
        $payload = array_filter([
            'system' => [
                'property_token' => $this->token,
                'time' => $dateTime->format(DATE_RFC3339),
            ],
            'user' => $options->getUser()->toArray(),
            'category' => $category,
            'action' => $action,
            'fields' => $options->getFields(),
            'remp_event_id' => Uuid::uuid4(),
        ]);

        try {
            $this->client->post(self::TRACK_EVENT, [
                'json' => $payload,
            ]);
        } catch (ClientException $e) {
            $jsonPayload = Json::encode($payload);
            Debugger::log("Host: [{$this->trackerHost}], Payload: [{$jsonPayload}], response: [{$e->getResponse()->getBody()}]", Logger::ERROR);
        }
    }
}
