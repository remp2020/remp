<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Nette\Utils\Json;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Meta;
use Tracy\Debugger;
use Tracy\ILogger;

class NytContent implements ContentInterface
{
    public function __construct(
        private readonly string $apiKey,
    ) {
    }

    public function fetchUrlMeta(string $url): ?Meta
    {
        $client = new Client();

        try {
            $responseRaw = $client->request('GET', 'https://api.nytimes.com/svc/search/v2/articlesearch.json', [
                'query' => [
                    'fq' => 'url:"' . $url . '"',
                    'api-key' => $this->apiKey,
                ],
            ]);
            $responseJson = $responseRaw->getBody()->getContents();
            $response = Json::decode($responseJson, forceArrays: true);

            if (count($response['response']['docs']) !== 1) {
                throw new \RuntimeException('Unable to fetch NYT article: ' . $responseJson);
            }
        } catch (ClientException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", $e->getCode(), $e);
        } catch (ServerException|ConnectException|RequestException $e) {
            Debugger::log('Unable to request NYT API: ' . $e->getMessage(), ILogger::EXCEPTION);
            return null;
        }

        return $this->parseMeta($response['response']['docs'][0]);
    }

    public function parseMeta(array $article): ?Meta
    {
        return new Meta(
            title: $article['headline']['main'],
            description: $article['abstract'],
            image: $article['multimedia']['default']['url'],
            authors: [$article['byline']['original']], // e.g. "By Devlin Barrett"
        );
    }
}
