<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

class LayoutTranslationsRepository extends Repository
{
    protected $tableName = 'mail_layout_translations';

    public function upsert(ActiveRow $layout, string $locale, string $text, string $html)
    {
        $data = [
            'mail_layout_id' => $layout->id,
            'locale' => $locale,
            'layout_text' => $text,
            'layout_html' => $html,
        ];

        $row = $this->getTable()
            ->where('mail_layout_id', $layout->id)
            ->where('locale', $locale)
            ->fetch();

        if ($row) {
            return $this->update($row, $data);
        }

        return $this->insert($data);
    }

    public function deleteByLayoutLocale(ActiveRow $layout, string $locale): void
    {
        $this->getTable()
            ->where('mail_layout_id', $layout->id)
            ->where('locale', $locale)
            ->delete();
    }

    public function getTranslationsForLocale(string $locale): Selection
    {
        return $this->getTable()->where('locale', $locale);
    }

    public function getAllTranslationsForLayout(ActiveRow $layout): Selection
    {
        return $this->getTable()->where('mail_layout_id', $layout->id);
    }
}
