<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\Config\LocalizationConfig;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\LayoutTranslationsRepository;

class LayoutFormFactory implements IFormFactory
{
    use SmartObject;

    private LayoutsRepository $layoutsRepository;

    private LayoutTranslationsRepository $layoutTranslationsRepository;

    private array $locales;

    public $onCreate;

    public $onUpdate;

    public function __construct(
        LayoutsRepository $layoutsRepository,
        LayoutTranslationsRepository $layoutTranslationsRepository,
        LocalizationConfig $localizationConfig
    ) {
        $this->layoutsRepository = $layoutsRepository;
        $this->layoutTranslationsRepository = $layoutTranslationsRepository;
        $this->locales = $localizationConfig->getSecondaryLocales();
    }

    public function create(?int $id = null): Form
    {
        $layout = null;
        $defaults = [];
        if ($id !== null) {
            $layout = $this->layoutsRepository->find($id);
            $defaults = $layout->toArray();

            $layoutTranslations = $this->layoutTranslationsRepository->getAllTranslationsForLayout($layout)
                ->fetchAssoc('locale');
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.");

        $codeInput = $form->addText('code', 'Code')
            ->setRequired("Field 'Code' is required.")
            ->addRule(function ($input) {
                $exists = $this->layoutsRepository->getTable()
                    ->where('code = ?', $input->value)
                    ->count('*');
                return !$exists;
            }, "Layout code must be unique. Code '%value' is already used.");

        if ($layout !== null) {
            $codeInput->setDisabled(true);
        }

        $form->addTextArea('layout_text', 'Text version')
            ->setHtmlAttribute('rows', 3);

        $form->addTextArea('layout_html', 'HTML version');

        $translationFields = [];
        foreach ($this->locales as $locale) {
            $translationFields[$locale] = $form->addContainer($locale);
            $translationFields[$locale]->addTextArea('layout_text', 'Text version')
                ->setHtmlAttribute('rows', 3)
                ->setNullable();
            $translationFields[$locale]->addTextArea('layout_html', 'HTML version')
                ->setNullable();

            $defaults[$locale]['layout_text'] = $layoutTranslations[$locale]['layout_text'] ?? '';
            $defaults[$locale]['layout_html'] = $layoutTranslations[$locale]['layout_html'] ?? '';
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

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
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
            $translations[$locale]['layout_text'] = $values[$locale]['layout_text'];
            $translations[$locale]['layout_html'] = $values[$locale]['layout_html'];
            unset($values[$locale]);
        }

        if (!empty($values['id'])) {
            $row = $this->layoutsRepository->find($values['id']);
            $this->layoutsRepository->update($row, $values);
            $row = $this->layoutsRepository->find($row->id);
            $this->storeLayoutTranslations($row, $translations);
            ($this->onUpdate)($row, $buttonSubmitted);
        } else {
            $row = $this->layoutsRepository->add(
                $values['name'],
                $values['code'],
                $values['layout_text'],
                $values['layout_html']
            );
            $this->storeLayoutTranslations($row, $translations);
            ($this->onCreate)($row, $buttonSubmitted);
        }
    }

    private function storeLayoutTranslations(ActiveRow $layout, array $values)
    {
        foreach ($this->locales as $locale) {
            if ($values[$locale]['layout_html'] === null && $values[$locale]['layout_text'] === null) {
                $this->layoutTranslationsRepository->deleteByLayoutLocale($layout, $locale);
                continue;
            }

            $this->layoutTranslationsRepository->upsert(
                $layout,
                $locale,
                $values[$locale]['layout_text'] ?? '',
                $values[$locale]['layout_html'] ?? '',
            );
        }
    }
}
