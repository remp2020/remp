<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta;

class Meta
{
    public function __construct(
        private readonly ?string $title = null,
        private readonly ?string $description = null,
        private readonly ?string $image = null,
        private readonly array $authors = [],
    ) {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }
}
