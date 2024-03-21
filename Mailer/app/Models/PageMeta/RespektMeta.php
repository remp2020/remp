<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta;

use Remp\MailerModule\Models\PageMeta\Meta;

class RespektMeta extends Meta
{
    public function __construct(
        ?string $title = null,
        ?string $image = null,
        array $authors = [],
        private readonly ?string $type = null,
        private readonly ?string $subtitle = null,
        private readonly ?string $firstParagraph = null,
    ) {
        parent::__construct(
            title: $title,
            image: $image,
            authors: $authors,
        );
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getFirstParagraph(): ?string
    {
        return $this->firstParagraph;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }
}
