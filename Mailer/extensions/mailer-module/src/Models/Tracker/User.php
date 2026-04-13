<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Tracker;

readonly class User
{
    public function __construct(
        private string|int|null $id = null,
        private ?string $ipAddress = null,
        private ?string $url = null,
        private ?string $userAgent = null,
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ipAddress,
            'url' => $this->url,
            'user_agent' => $this->userAgent,
        ];
    }
}
