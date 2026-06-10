<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use Nette\Utils\Strings;
use Remp\MailerModule\Models\PageMeta\Content\JsonLDContent;
use Remp\MailerModule\Models\PageMeta\Meta;

class EuobserverContent extends JsonLDContent
{
    protected function postProcessMeta(Meta $meta): Meta
    {
        $authors = [];
        foreach ($meta->getAuthors() as $author) {
            $authors[] = Strings::upper($author);
        }

        return new Meta(
            $meta->getTitle(),
            $meta->getDescription(),
            $meta->getImage(),
            $authors,
        );
    }
}
