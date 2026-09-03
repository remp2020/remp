<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Auth;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Models\Crm\Client;

class CrmSsoClient extends Client
{
    /**
     * Resolves a single-use SSO code into the CRM user it was issued for.
     *
     * @return array{id: int, email: string, first_name: ?string, last_name: ?string, roles?: string[]}
     * @throws CrmSsoException
     */
    public function resolve(string $code): array
    {
        try {
            $response = $this->client->post('api/v1/sso/resolve', [
                RequestOptions::FORM_PARAMS => [
                    'code' => $code,
                ],
            ]);

            $payload = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        } catch (ConnectException $connectException) {
            throw new CrmSsoException("could not connect to CRM: {$connectException->getMessage()}");
        } catch (ClientException | ServerException $exception) {
            throw new CrmSsoException("unable to resolve SSO code: {$exception->getMessage()}");
        } catch (JsonException $jsonException) {
            throw new CrmSsoException("invalid response from CRM: {$jsonException->getMessage()}");
        }

        if (!isset($payload['user']['id'], $payload['user']['email'])) {
            throw new CrmSsoException('CRM response is missing user data');
        }

        return $payload['user'];
    }
}
