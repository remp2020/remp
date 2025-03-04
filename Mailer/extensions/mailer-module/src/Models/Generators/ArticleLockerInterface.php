<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

interface ArticleLockerInterface
{
    /**
     * getLockedPost determines where the content of post should be cut of and returns trimmed version of the post.
     */
    public function getLockedPost(string $post): string;

    /**
     * injectLockedMessage adds a "lock" message to the determined place in the $post - e.g. at the end, or by replacing
     * existing placeholder within the $post content.
     */
    public function injectLockedMessage(string $post): string;
}
