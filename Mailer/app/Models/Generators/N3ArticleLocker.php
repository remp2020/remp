<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Remp\MailerModule\Models\Generators\ArticleLockerInterface;

class N3ArticleLocker implements ArticleLockerInterface
{
    private string $placeholder = '<!--[LOCKED_TEXT_PLACEHOLDER]-->';
    private string $linkText;
    private string $linkUrl;
    private string $text;

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
        return $parts[0] . $this->placeholder;
    }

    public function injectLockedMessage(string $post): string
    {
        $lockedHtml = '';

        if (isset($this->text)) {
            $lockedHtml .= <<< HTML
\n\n<h2 style="color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;font-weight:bold;text-align:left;margin-bottom:30px;Margin-bottom:30px;font-size:24px;">{$this->text}</h2>\n\n
HTML;
        }

        if (isset($this->linkText, $this->linkUrl)) {
            $lockedHtml .= <<< HTML
{{ include('dn3-button-green', {"href": "$this->linkUrl", "text": "$this->linkText"} ) }}\n\n
HTML;
        }

        return str_replace($this->placeholder, $lockedHtml, $post);
    }

    public function setLockLink(string $linkText, string $linkUrl): self
    {
        $this->linkText = $linkText;
        $this->linkUrl = $linkUrl;
        return $this;
    }

    public function setLockText(string $text): self
    {
        $this->text = $text;
        return $this;
    }
}
