<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Config;

class EditorConfig
{
    public const EDITOR_CODEMIRROR = 'codemirror';
    public const EDITOR_WYSIWYG = 'wysiwyg';

    private string $templateEditor = self::EDITOR_CODEMIRROR;

    public function setTemplateEditor(?string $editor): void
    {
        if ($editor === null) {
            return;
        }

        $this->templateEditor = match ($editor) {
            self::EDITOR_CODEMIRROR => self::EDITOR_CODEMIRROR,
            self::EDITOR_WYSIWYG => self::EDITOR_WYSIWYG,
            default => throw new \Exception('Unsupported editor configured: ' . $editor),
        };
    }

    public function getTemplateEditor(): string
    {
        return $this->templateEditor;
    }
}
