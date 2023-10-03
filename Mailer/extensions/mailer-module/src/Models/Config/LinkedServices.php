<?php

namespace Remp\MailerModule\Models\Config;

class LinkedServices
{
    private array $linkedServices = [];

    public function linkService(string $code, ?string $url, ?string $icon): void
    {
        if (empty($url)) {
            return;
        }
        $this->linkedServices[$code] = [
            'url' => $url,
            'icon' => $icon,
        ];
    }

    public function getServices(): array
    {
        return $this->linkedServices;
    }
}
