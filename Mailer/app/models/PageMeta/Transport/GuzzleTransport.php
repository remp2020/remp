<?php

namespace Remp\MailerModule\PageMeta;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

class GuzzleTransport implements TransportInterface
{
    public function getContent($url)
    {
        $client = new Client();
        try {
            $res = $client->get($url);
            return $res->getBody();
        } catch (ConnectException $e) {
            return false;
        } catch (ServerException $e) {
            return false;
        }
    }
}
