<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

class TemplateTranslationsRepository extends Repository
{
    protected $tableName = 'mail_template_translations';

    public function upsert(
        ActiveRow $mailTemplate,
        string $locale,
        string $from,
        string $subject,
        string $mailBodyText,
        string $mailBodyHtml
    ) {
        $data = [
            'mail_template_id' => $mailTemplate->id,
            'locale' => $locale,
            'from' => $from,
            'subject' => $subject,
            'mail_body_text' => $mailBodyText,
            'mail_body_html' => $mailBodyHtml,
        ];

        $row = $this->getTable()
            ->where('mail_template_id', $mailTemplate->id)
            ->where('locale', $locale)
            ->fetch();

        if ($row) {
            return $this->update($row, $data);
        }

        return $this->insert($data);
    }

    public function duplicate(ActiveRow $mailTemplate, ActiveRow $duplicatedMailTemplate): void
    {
        $templateTranslations = $this->getTable()->where('mail_template_id', $mailTemplate->id);
        foreach ($templateTranslations as $templateTranslation) {
            $this->insert([
                'mail_template_id' => $duplicatedMailTemplate->id,
                'locale' => $templateTranslation->locale,
                'from' => $templateTranslation->from,
                'subject' => $templateTranslation->subject,
                'mail_body_text' => $templateTranslation->mail_body_text,
                'mail_body_html' => $templateTranslation->mail_body_html,
            ]);
        }
    }
}
