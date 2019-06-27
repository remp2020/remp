<?php

namespace Remp\MailerModule\PageMeta;

class Meta
{
    private $title;

    private $description;

    private $image;

    private $authors;

    public function __construct($title, $description, $image, $authors)
    {
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
        $this->authors = $authors;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getAuthors(): array
    {
        return is_array($this->authors) ? $this->authors : [];
    }
}
