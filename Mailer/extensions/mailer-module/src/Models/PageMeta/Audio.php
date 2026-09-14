<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta;

/**
 * Audio attachment of a page, built from a schema.org `AudioObject`.
 */
class Audio
{
    public function __construct(
        private readonly ?string $url = null,
        private readonly ?int $size = null,
        private readonly ?int $durationInSeconds = null,
        private readonly ?string $thumbnailUrl = null,
    ) {
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * File size in bytes.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getDurationInSeconds(): ?int
    {
        return $this->durationInSeconds;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }
}
