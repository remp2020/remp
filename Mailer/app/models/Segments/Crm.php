<?php
namespace Remp\MailerModule\Segment;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class Crm implements ISegment
{
    const PROVIDER_ALIAS = 'crm-segment';

    const ENDPOINT_LIST = 'user-segments/list';

    const ENDPOINT_USERS = 'user-segments/users';

    private $baseUrl;

    private $token;

    public function __construct($baseUrl, $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }

    public function provider()
    {
        return [static::PROVIDER_ALIAS => $this];
    }

    public function list()
    {
        $response = $this->request(static::ENDPOINT_LIST);
        $segments = [];

        foreach ($response['segments'] as $segment) {
            $segments[] = [
                'name' => $segment['name'],
                'provider' => static::PROVIDER_ALIAS,
                'code' => $segment['code'],
                'group' => $segment['group'],
            ];
        }

        return $segments;
    }

    public function users($segment)
    {
        $response = $this->request(static::ENDPOINT_USERS, ['code' => $segment['code']]);
        $userIds = [];
        foreach ($response['users'] as $user) {
            $userIds[] = $user['id'];
        }
        return $userIds;
    }

    private function request($url, $query = [])
    {
        $client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ]
        ]);

        try {
            $response = $client->get($url, [
                'query' => $query,
            ]);

            return Json::decode($response->getBody(), Json::FORCE_ARRAY);
        } catch (ConnectException $connectException) {
            throw new SegmentException("Could not connect to Segment:{$url} endpoint: {$connectException->getMessage()}");
        }
    }
}
