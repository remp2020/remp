<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Realic;

use Remp\MailerModule\Models\ImageUrlSigner\ImageUrlSigner;

class RealicUrlSigner implements ImageUrlSigner
{
    /**
     * @param string[] $retiredHosts
     * @param array<string, string> $originHosts Bucket => host serving that bucket directly.
     */
    public function __construct(
        private readonly string $key,
        private readonly string $targetHost,
        private readonly array $retiredHosts = [],
        private readonly array $originHosts = [],
    ) {
    }

    public function sign(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            return $url;
        }

        // The path is signed percent-decoded while the query is signed http_build_query()-encoded;
        // getting this pair wrong is the usual cause of a 403. See realic's README.
        $path = rawurldecode($parts['path'] ?? '');
        parse_str($parts['query'] ?? '', $params);
        unset($params['s']);

        if (in_array($parts['host'], $this->hosts(), true)) {
            if (!$params && ($originUrl = $this->originUrl($path)) !== null) {
                return $originUrl;
            }
        } else {
            $bucket = array_search($parts['host'], $this->originHosts, true);
            if ($bucket === false || !$params) {
                return $url;
            }

            // An origin URL earns a trip through the resizer as soon as someone asks it to resize.
            $path = '/' . $bucket . $path;
        }

        $signed = $params;
        ksort($signed);
        // @phpstan-ignore disallowed.function (md5 is Glide's signature algorithm, not a choice we can make here)
        $params['s'] = md5($this->key . ':' . ltrim($path, '/') . '?' . http_build_query($signed));

        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

        return 'https://' . $this->targetHost . $encodedPath . '?' . http_build_query($params);
    }

    public function rewrite(string $content): string
    {
        $hosts = array_map(fn (string $host): string => preg_quote($host, '~'), $this->hosts());

        return preg_replace_callback(
            // Whitespace ends a srcset candidate or a URL in a text email, quotes end an attribute or a css url().
            pattern: '~https?://(?:' . implode('|', $hosts) . ')/[^\s"\'<>]+~i',
            // HTML attributes separate params with &amp;, which parse_str would read as a param named "amp;h".
            // Signing normalizes them to a bare &, which is valid in an attribute.
            callback: fn (array $match): string => $this->sign(str_replace('&amp;', '&', $match[0])),
            subject: $content,
        );
    }

    /**
     * Without resize params there's no need to use Realic and we can serve the content directly from static.
     */
    private function originUrl(string $path): ?string
    {
        [, $bucket, $bucketPath] = array_pad(explode('/', $path, 3), 3, '');

        $originHost = $this->originHosts[$bucket] ?? null;
        if ($originHost === null || $bucketPath === '') {
            return null;
        }

        return 'https://' . $originHost
            . implode('/', array_map('rawurlencode', explode('/', '/' . $bucketPath)));
    }

    /**
     * @return string[]
     */
    private function hosts(): array
    {
        return [$this->targetHost, ...$this->retiredHosts];
    }
}
