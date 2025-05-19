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
        public readonly ?string $type = null,
        public readonly ?string $subtitle = null,
        public readonly ?string $firstParagraph = null,
        public readonly ?string $firstContentPartType = null,
        public readonly ?string $fullContent = null,
        public readonly ?string $unlockedContent = null,
        public readonly ?string $imageTitle = null,
        public readonly ?string $subject = null,
    ) {
        parent::__construct(
            title: $title,
            image: $image,
            authors: $authors,
        );
    }
}
