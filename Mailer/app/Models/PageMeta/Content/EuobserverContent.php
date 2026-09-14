<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use Nette\Utils\Strings;
use Remp\MailerModule\Models\PageMeta\Content\JsonLDContent;

class EuobserverContent extends JsonLDContent
{
    protected function processAuthors(array $authors): array
    {
        $upperCasedAuthors = [];
        foreach ($authors as $author) {
            $upperCasedAuthors[] = Strings::upper($author);
        }

        return $upperCasedAuthors;
    }
}
