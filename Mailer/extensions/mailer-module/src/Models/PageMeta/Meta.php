<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta;

class Meta
{
    private ?string $title;

    private ?string $description;

    private ?string $image;

    private array $authors;

    public function __construct(?string $title = null, ?string $description = null, ?string $image = null, array $authors = [])
    {
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
        $this->authors = $authors;
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
