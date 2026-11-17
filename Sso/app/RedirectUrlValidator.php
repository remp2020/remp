<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Guards URLs SSO redirects to (successUrl / errorUrl).
 *
 * A URL is trusted only if it is exactly one of the allowed origins (scheme://host[:port]), or an allowed
 * origin immediately followed by "/", "?" or "#". The check works on the raw string (no URL parsing),
 * so a URL can't be interpreted differently here and in the browser.
 */
class RedirectUrlValidator
{
    /**
     * @var string[]
     */
    private array $allowedOrigins = [];

    /**
     * @param string[] $allowedOrigins
     */
    public function __construct(array $allowedOrigins)
    {
        foreach ($allowedOrigins as $origin) {
            $normalized = strtolower(rtrim(trim($origin), '/'));
            if (!preg_match('#^https?://[a-z0-9.-]+(:[0-9]+)?$#', $normalized)) {
                Log::warning('SSO ignored invalid allowed redirect origin', ['origin' => $origin]);
                continue;
            }
            $this->allowedOrigins[] = $normalized;
        }
        $this->allowedOrigins = array_values(array_unique($this->allowedOrigins));

        if ($allowedOrigins !== [] && $this->allowedOrigins === []) {
            Log::error('SSO has no valid allowed redirect origin, all redirects are rejected');
        }
    }

    public static function fromConfig(): self
    {
        $origins = config('auth.allowed_redirect_origins');

        if ($origins === []) {
            $origins = array_filter([
                config('services.remp.linked.beam.url'),
                config('services.remp.linked.campaign.url'),
                config('services.remp.linked.mailer.url'),
                config('services.remp.sso.web_addr'),
            ], 'is_string');
        }

        return new self($origins);
    }

    public function isAllowed(mixed $url): bool
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        // control characters, whitespace and backslashes are interpreted inconsistently by browsers
        if (preg_match('/[\x00-\x20\x7F\\\\]/', $url)) {
            return false;
        }

        $url = strtolower($url);
        foreach ($this->allowedOrigins as $origin) {
            if ($url === $origin
                || str_starts_with($url, $origin . '/')
                || str_starts_with($url, $origin . '?')
                || str_starts_with($url, $origin . '#')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the URL if it is trusted, otherwise logs the attempt and responds with 400 - never redirects.
     */
    public function ensureAllowed(mixed $url, string $name): string
    {
        if (!$this->isAllowed($url)) {
            Log::notice('SSO rejected untrusted redirect URL', [
                'parameter' => $name,
                'url' => is_scalar($url) ? (string) $url : get_debug_type($url),
            ]);

            throw new BadRequestHttpException("Untrusted {$name}");
        }

        /** @var string $url */
        return $url;
    }
}
