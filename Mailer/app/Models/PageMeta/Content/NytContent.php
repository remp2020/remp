<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Nette\Utils\Json;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Meta;

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
            $responseRaw = $client->get('http://api.nytimes.com/svc/news/v3/content.json', [
                'query' => [
                    'url' => $url,
                    'api-key' => $this->apiKey,
                ],
            ]);
            $responseJson = $responseRaw->getBody()->getContents();
            $response = Json::decode($responseJson, forceArrays: true);
            if ($response['num_results'] !== 1) {
                throw new \RuntimeException('Unable to fetch NYT article: ' . $responseJson);
            }
        } catch (ClientException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", $e->getCode(), $e);
        } catch (ServerException $e) {
            throw new \RuntimeException('Unable to request NYT API: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return $this->parseMeta($response['results'][0]);
    }

    public function parseMeta(array $article): ?Meta
    {
        $imageUrl = null;
        foreach ($article['multimedia'] as $imageDef) {
            if ($imageDef['format'] === 'mediumThreeByTwo440') {
                $imageUrl = $imageDef['url'];
                break;
            }
        }

        return new Meta(
            title: $article['title'],
            description: $article['abstract'],
            image: $imageUrl,
            authors: [$article['byline']], // e.g. "By Devlin Barrett"
        );
    }
}
