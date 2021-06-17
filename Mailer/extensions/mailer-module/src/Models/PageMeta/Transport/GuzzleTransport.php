<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

class GuzzleTransport implements TransportInterface
{
    public function getContent(string $url): ?string
    {
        $client = new Client();
        try {
            $res = $client->get($url);
            return (string) $res->getBody();
        } catch (ConnectException $e) {
            return null;
        } catch (ServerException $e) {
            return null;
        }
    }
}
