<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;
use Remp\MailerModule\Models\ImageUrlSigner\ImageUrlSigner;

/**
 * ImageUrlSignReplace signs image URLs, so the image proxy accepts the parameters other replacers added to them.
 */
class ImageUrlSignReplace implements IReplace
{
    public function __construct(
        private readonly ImageUrlSigner $imageUrlSigner,
    ) {
    }

    public function replace(string $content, GeneratorInput $generatorInput, ?array $context = null): string
    {
        return $this->imageUrlSigner->rewrite($content);
    }
}
