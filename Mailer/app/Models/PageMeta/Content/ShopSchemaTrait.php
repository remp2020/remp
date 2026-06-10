<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use Nette\Utils\Strings;
use Remp\MailerModule\Models\PageMeta\Meta;

trait ShopSchemaTrait
{
    protected function parseShopSchema(?\stdClass $schema): ?Meta
    {
        if ($schema === null) {
            return null;
        }

        $element = $schema->dataFeedElement[0] ?? null;

        // authors
        $authors = [];
        if (isset($element->author)) {
            $authors = explode(',', $element->author->name);
        }

        // title
        $title = $element->name ?? null;

        // description
        $description = null;
        if (isset($element->workExample[0]->abstract)) {
            $description = str_replace('\n', '', Strings::truncate($element->workExample[0]->abstract, 200));
        }

        // image
        $image = $element->workExample[0]->image ?? null;

        return new Meta($title, $description, $image, $authors);
    }
}
