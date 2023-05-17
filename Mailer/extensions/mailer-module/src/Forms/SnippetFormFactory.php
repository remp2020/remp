<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Forms\Rules\StringValidator;
use Remp\MailerModule\Models\Config\LocalizationConfig;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\SnippetTranslationsRepository;

class SnippetFormFactory implements IFormFactory
{
    use SmartObject;

    private SnippetsRepository $snippetsRepository;

    private SnippetTranslationsRepository $snippetTranslationsRepository;

    private array $locales;

    public $onCreate;

    public $onUpdate;

    private ListsRepository $listsRepository;

    public function __construct(
        ListsRepository $listsRepository,
        SnippetsRepository $snippetsRepository,
        SnippetTranslationsRepository $snippetTranslationsRepository,
        LocalizationConfig $localizationConfig
    ) {
        $this->snippetsRepository = $snippetsRepository;
        $this->listsRepository = $listsRepository;
        $this->snippetTranslationsRepository = $snippetTranslationsRepository;
        $this->locales = $localizationConfig->getSecondaryLocales();
    }

    public function create(?int $id = null): Form
    {
        $defaults = [];
        if ($id !== null) {
            $snippet = $this->snippetsRepository->find($id);
            $defaults = $snippet->toArray();

            $snippetTranslations = $this->snippetTranslationsRepository->getTranslationsForSnippet($snippet)
                ->fetchAssoc('locale');
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.");

        $form->addText('code', 'code')
            ->setRequired("Field 'Code' is required.")
            ->addRule(StringValidator::SLUG, "Field 'Code' is not a URL friendly slug.")
            ->setDisabled($id !== null);

        $mailTypes = $this->listsRepository->all()->fetchPairs('id', 'title');
        $form->addSelect('mail_type_id', 'Mail type', $mailTypes)
            ->setPrompt('None (snippet is global)');

        $form->addTextArea('text', 'Text version')
            ->setHtmlAttribute('rows', 3);

        $form->addTextArea('html', 'HTML version');

        $translationFields = [];
        foreach ($this->locales as $locale) {
            $translationFields[$locale] = $form->addContainer($locale);
            $translationFields[$locale]->addTextArea('text', 'Text version')
                ->setHtmlAttribute('rows', 3)
                ->setNullable();
            $translationFields[$locale]->addTextArea('html', 'HTML version')
                ->setNullable();

            $defaults[$locale]['text'] = $snippetTranslations[$locale]['text'] ?? null;
            $defaults[$locale]['html'] = $snippetTranslations[$locale]['html'] ?? null;
        }

        $form->setDefaults($defaults);

        $form->addSubmit(self::FORM_ACTION_SAVE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-check"></i> Save');

        $form->addSubmit(self::FORM_ACTION_SAVE_CLOSE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save and close');

        $form->onValidate[] = [$this, 'validateSnippetForm'];
        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function validateSnippetForm(Form $form, ArrayHash $values): void
    {
        if (!empty($values->id)) {
            $snippet = $this->snippetsRepository->find($values->id);
            $values->code = $snippet->code;
        }

        $snippet = $this->snippetsRepository->findByCodeAndMailType($values->code, $values->mail_type_id);
        if ($snippet && $snippet->id !== (int) $values->id) {
            $mailType = $this->listsRepository->find($values->mail_type_id);
            if ($mailType) {
                $form->addError("Snippet with code \"{$values->code}\" and mail type \"{$mailType->title}\" already exists.");
            } else {
                $form->addError("Snippet with code \"{$values->code}\" and without the mail type already exists.");
            }
        }
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        // decide if user wants to save or save and leave
        $buttonSubmitted = self::FORM_ACTION_SAVE;
        /** @var SubmitButton $buttonSaveClose */
        $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];
        if ($buttonSaveClose->isSubmittedBy()) {
            $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
        }

        $values = (array) $values;

        $translations = [];
        foreach ($this->locales as $locale) {
            $translations[$locale]['html'] = $values[$locale]['html'];
            $translations[$locale]['text'] = $values[$locale]['text'];
            unset($values[$locale]);
        }

        if (!empty($values['id'])) {
            $row = $this->snippetsRepository->find($values['id']);

            $this->snippetsRepository->update($row, $values);
            $row = $this->snippetsRepository->find($row->id);
            $this->storeSnippetTranslations($row, $translations);

            ($this->onUpdate)($row, $buttonSubmitted);
        } else {
            $row = $this->snippetsRepository->add($values['name'], $values['code'], $values['text'], $values['html'], $values['mail_type_id']);
            $this->storeSnippetTranslations($row, $translations);

            ($this->onCreate)($row, $buttonSubmitted);
        }
    }

    private function storeSnippetTranslations(ActiveRow $snippet, $values)
    {
        foreach ($this->locales as $locale) {
            if ($values[$locale]['html'] === null && $values[$locale]['text'] === null) {
                $this->snippetTranslationsRepository->deleteBySnippetLocale($snippet, $locale);
                continue;
            }

            $this->snippetTranslationsRepository->upsert(
                $snippet,
                $locale,
                $values[$locale]['text'] ?? '',
                $values[$locale]['html'] ?? ''
            );
        }
    }
}
