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

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getAuthors()
    {
        return $this->authors;
    }
}
