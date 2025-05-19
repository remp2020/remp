<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Nette\Utils\Json;
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
    coverPhoto {image {url width height title author {name}} title}
    categories {category {id name}}
    publishAt
    subtitle {parts {json order}}
    newsletterSubject
    content {
      parts {
        json
        order
        references {id type target {type externalTarget internalTarget {url article {title}}} image { title image { url title author {name} } }}
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
        } catch (ClientException $e) {
            Debugger::log($e->getMessage() . ': ' . $e->getResponse()->getBody()->getContents(), ILogger::ERROR);
            return null;
        } catch (GuzzleException $e) {
            Debugger::log($e->getMessage(), ILogger::ERROR);
            return null;
        }

        return $response->getBody()->getContents();
    }

    public function resolveRedirects(string $url): ?string
    {
        $uri = new Uri($url);
        $contentPath = trim($uri->getPath(), '/');

        $client = new Client();
        try {
            $query = <<<'GRAPHQL'
query GetRedirectUrlForMailer($articleUrl: String) {
  getUrl(by: { url: $articleUrl}) {
    redirectType
    target {
      internalTarget {
        url
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

        $response = $response->getBody()->getContents();
        $json = Json::decode($response);

        $path = $json->data->getUrl->target->internalTarget->url ?? null;
        if (!$path) {
            return null;
        }

        $resolvedUrl = new Uri($url);
        $resolvedUrl = $resolvedUrl->withPath($path);
        return (string) $resolvedUrl;
    }
}
