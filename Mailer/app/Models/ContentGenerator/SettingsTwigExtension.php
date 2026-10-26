<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\ContentGenerator;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SettingsTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('parse_settings', [$this, 'parseSettings']),
        ];
    }

    /**
     * Parses settings written as JSON object members without the wrapping braces (see dn3-settings snippets), e.g.:
     *
     *   "title": "Graf dňa",
     *   "show_author": true
     */
    public function parseSettings(string $settings): array
    {
        try {
            return json_decode('{' . $settings . '}', true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException("Unable to parse settings: {$e->getMessage()}", 0, $e);
        }
    }
}
