<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Models\Config\LocalizationConfig;
use Remp\MailerModule\Repositories\ActiveRowFactory;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class MailTranslator
{
    private TemplatesRepository $templatesRepository;

    private SnippetsRepository $snippetsRepository;

    private LocalizationConfig $localizationConfig;

    public function __construct(
        TemplatesRepository $templatesRepository,
        SnippetsRepository $snippetsRepository,
        LocalizationConfig $localizationConfig
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->snippetsRepository = $snippetsRepository;
        $this->localizationConfig = $localizationConfig;
    }

    public function translateTemplate(ActiveRow $templateRow, string $locale = null): MailTemplate
    {
        if ($templateRow->getTable()->getName() === ActiveRowFactory::TABLE_NAME_DATAROW || !$this->localizationConfig->isTranslatable($locale)) {
            return new MailTemplate($templateRow->from, $templateRow->subject, $templateRow->mail_body_text, $templateRow->mail_body_html);
        }

        $templateRow = $this->templatesRepository->getByCode($templateRow->code);

        $translatedTemplate = $templateRow->related('mail_template_translations', 'mail_template_id')
            ->where('locale', $locale)
            ->fetch();

        if ($translatedTemplate) {
            return new MailTemplate($translatedTemplate->from, $translatedTemplate->subject, $translatedTemplate->mail_body_text, $translatedTemplate->mail_body_html);
        }

        return new MailTemplate($templateRow->from, $templateRow->subject, $templateRow->mail_body_text, $templateRow->mail_body_html);
    }

    public function translateLayout(ActiveRow $layoutRow, string $locale = null): MailLayout
    {
        if (!$this->localizationConfig->isTranslatable($locale)) {
            return new MailLayout($layoutRow->layout_text, $layoutRow->layout_html);
        }

        $translatedLayout = $layoutRow->related('mail_layout_translations', 'mail_layout_id')
            ->where('locale', $locale)
            ->fetch();

        if ($translatedLayout) {
            return new MailLayout($translatedLayout->layout_text, $translatedLayout->layout_html);
        }

        return new MailLayout($layoutRow->layout_text, $layoutRow->layout_html);
    }

    public function translateSnippets(array $snippetsToTranslate, string $locale = null): array
    {
        if (!$this->localizationConfig->isTranslatable($locale)) {
            return $snippetsToTranslate;
        }

        $snippets = $this->snippetsRepository->getTable()
            ->where('code IN (?)', array_keys($snippetsToTranslate))
            ->fetchAll();

        $result = [];
        foreach ($snippets as $snippet) {
            $translatedSnippet = $snippet->related('mail_snippet_translations', 'mail_snippet_id')
                ->where('locale', $locale)
                ->fetch();

            $result[$snippet->code] = $translatedSnippet->html ?? $snippet->html;
        }

        return $result;
    }
}
