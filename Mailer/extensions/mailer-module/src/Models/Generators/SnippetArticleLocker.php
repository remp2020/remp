<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Remp\MailerModule\Repositories\SnippetsRepository;
use Tracy\Debugger;
use Tracy\ILogger;

class SnippetArticleLocker implements ArticleLockerInterface
{
    private string $lockSnippetCode;
    public function __construct(private readonly SnippetsRepository $snippetsRepository)
    {
    }

    public const LOCKED_TEXT_PLACEHOLDER = '<!--[LOCKED_TEXT_PLACEHOLDER]-->';

    public function getLockedPost(string $post): string
    {
        if (stripos($post, '[lock newsletter]') !== false) {
            $lock = '[lock newsletter]';
        } elseif (stripos($post, '[lock]') !== false) {
            $lock = '[lock]';
        } else {
            // no lock, no placeholder
            return $post;
        }

        $parts = explode($lock, $post);
        return $parts[0] . self::LOCKED_TEXT_PLACEHOLDER;
    }

    public function injectLockedMessage(string $post): string
    {
        if (!isset($this->lockSnippetCode)) {
            Debugger::log("Unable to inject lock message to the generated email, snippet for SnippetArticleLocker was not configured.", ILogger::ERROR);
            return str_replace(self::LOCKED_TEXT_PLACEHOLDER, '', $post);
        }

        $lockSnippet = $this->snippetsRepository->findByCodeAndMailType($this->lockSnippetCode, null);
        if (!$lockSnippet) {
            Debugger::log("Unable to inject lock message to the generated email, snippet '{$this->lockSnippetCode}' doesn't exist.", ILogger::ERROR);
            return str_replace(self::LOCKED_TEXT_PLACEHOLDER, '', $post);
        }

        return str_replace(self::LOCKED_TEXT_PLACEHOLDER, $lockSnippet->html, $post);
    }

    public function setLockSnippetCode(string $lockSnippetCode): void
    {
        $this->lockSnippetCode = $lockSnippetCode;
    }
}
