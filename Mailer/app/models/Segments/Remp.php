<?php
namespace Remp\MailerModule\Segment;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class Remp implements ISegment
{
    const PROVIDER_ALIAS = 'remp-segment';

    const ENDPOINT_LIST = 'segments';

    const ENDPOINT_USERS = 'segments/%s/users';

    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
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
        $response = $this->request(sprintf(static::ENDPOINT_USERS, $segment['code']));
        return $response['users'];
    }

    private function request($url, $query = [])
    {
        $client = new Client([
            'base_uri' => $this->baseUrl
        ]);

        try {
            $response = $client->get($url, [
                'query' => $query,
            ]);

            return Json::decode($response->getBody(), Json::FORCE_ARRAY);
        } catch (ClientException $clientException) {
            $data = json_decode($clientException->getResponse()->getBody());
            return ['status' => 'error', 'error' => $data->error, 'message' => $data->message];
        } catch (ConnectException $connectException) {
            return ['status' => 'error', 'error' => 'unavailable server', 'message' => 'Cannot connect to auth server'];
        } catch (JsonException $jsonException) {
            return ['status' => 'error', 'error' => 'wrong response', 'message' => $jsonException->getMessage()];
        }
    }
}
