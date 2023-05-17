<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

class SnippetTranslationsRepository extends Repository
{
    protected $tableName = 'mail_snippet_translations';

    public function upsert(ActiveRow $snippet, string $locale, string $text, string $html)
    {
        $data = [
            'mail_snippet_id' => $snippet->id,
            'locale' => $locale,
            'text' => $text,
            'html' => $html,
        ];

        $row = $this->getTable()
            ->where('mail_snippet_id', $snippet->id)
            ->where('locale', $locale)
            ->fetch();

        if ($row) {
            return $this->update($row, $data);
        }

        return $this->insert($data);
    }

    public function deleteBySnippetLocale(ActiveRow $snippet, string $locale): void
    {
        $this->getTable()
            ->where('mail_snippet_id', $snippet->id)
            ->where('locale', $locale)
            ->delete();
    }

    public function getTranslationsForSnippet(ActiveRow $snippet): Selection
    {
        return $this->getTable()->where('mail_snippet_id', $snippet->id);
    }
}
