<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\ImageUrlSigner;

interface ImageUrlSigner
{
    /**
     * Returns the URL the image should actually be requested from.
     */
    public function sign(string $url): string;

    /**
     * Applies sign() to every image URL found in a block of HTML or text.
     */
    public function rewrite(string $content): string;
}
