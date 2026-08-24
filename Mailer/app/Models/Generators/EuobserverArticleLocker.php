<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Remp\MailerModule\Models\Generators\ArticleLockerInterface;

final class EuobserverArticleLocker implements ArticleLockerInterface
{
    private const string LOCK_BLOCK_MARKER = '<!-- wp:eo/lock -->';

    public function getLockedPost(string $post): string
    {
        $lockPos = stripos($post, self::LOCK_BLOCK_MARKER);
        if ($lockPos === false) {
            return $post;
        }
        return substr($post, 0, $lockPos);
    }

    public function injectLockedMessage(string $post): string
    {
        return $post . "\n\n{{ include('eo-button-red', {href: 'https://account.euobserver.com/membership', text: 'Subscribe to read the full story'}) }}\n";
    }
}
