<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class RespektApiTransport implements TransportInterface
{
    public function __construct(
        private readonly string $respektContentUrl,
        private readonly string $respektContentToken,
    ) {
    }

    public function getContent(string $url): ?string
    {
        $uri = new Uri($url);
        $contentPath = trim($uri->getPath(), '/');

        $client = new Client();
        try {
            $query = <<<'GRAPHQL'
query GetArticleForMailer($articleUrl: String) {
  getArticle(by: { url: { url: $articleUrl } }) {
    title
    authors {author {name}}
    coverPhoto {image {url width height}}
    categories {category {id name}}
    publishAt
    subtitle {parts {json order}}
    content {
      parts(filter: { order: { eq: 0 } }) {
        json
        order
      }
    }
  }
}
GRAPHQL;

            $response = $client->post($this->respektContentUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $this->respektContentToken],
                'json' => [
                    'query' => $query,
                    'variables' => [
                        'articleUrl' => $contentPath
                    ]
                ]
            ]);
        } catch (GuzzleException $e) {
            Debugger::log($e->getMessage(), ILogger::ERROR);
            return null;
        }

        return $response->getBody()->getContents();
    }
}
