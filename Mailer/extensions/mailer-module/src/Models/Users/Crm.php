<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Users;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\StreamWrapper;
use JsonMachine\Items;
use Nette\Utils\Json;
use Tracy\Debugger;

class Crm implements IUser
{
    const ENDPOINT_LIST = 'api/v1/users/list';

    private $client;

    public function __construct(string $baseUrl, string $token)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
    }

    public function list(array $userIds, int $page, bool $includeDeactivated = false): array
    {
        try {
            $response = $this->client->post(self::ENDPOINT_LIST, [
                'form_params' => [
                    'user_ids' => Json::encode($userIds),
                    'page' => $page,
                    'include_deactivated' => $includeDeactivated,
                ],
            ]);

            $stream = StreamWrapper::getResource($response->getBody());
            try {
                $users = [];
                foreach (Items::fromStream($stream, ['pointer' => '/users']) as $user) {
                    $users[$user->id] = [
                        'id' => $user->id,
                        'email' => $user->email,
                    ];
                }
            } finally {
                fclose($stream);
            }
        } catch (ConnectException $e) {
            throw new UserException("could not connect CRM user base: {$e->getMessage()}");
        } catch (ClientException $e) {
            Debugger::log("unable to get list of CRM users: " . $e->getResponse()->getBody()->getContents(), Debugger::WARNING);
            return [];
        }

        return $users;
    }
}
