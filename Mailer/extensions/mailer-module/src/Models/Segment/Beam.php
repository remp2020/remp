<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Segment;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Nette\Utils\Json;

class Beam implements ISegment
{
    const PROVIDER_ALIAS = 'remp-segment';

    const ENDPOINT_LIST = 'segments';

    const ENDPOINT_USERS = 'segments/%s/users';

    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function provider(): string
    {
        return static::PROVIDER_ALIAS;
    }

    public function list(): array
    {
        $response = $this->request(static::ENDPOINT_LIST);
        $segments = [];

        foreach ($response as $segment) {
            $segments[] = [
                'name' => $segment['name'],
                'provider' => static::PROVIDER_ALIAS,
                'code' => $segment['code'],
                'group' => $segment['group'],
            ];
        }

        return $segments;
    }

    public function users(array $segment): array
    {
        $response = $this->request(sprintf(static::ENDPOINT_USERS, $segment['code']));
        return $response;
    }

    private function request(string $url, array $query = []): array
    {
        $client = new Client([
            'base_uri' => $this->baseUrl
        ]);

        try {
            $response = $client->get($url, [
                'query' => $query,
            ]);

            return Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        } catch (ConnectException $connectException) {
            throw new SegmentException("Could not connect to Segment:{$url} endpoint: {$connectException->getMessage()}");
        }
    }
}
