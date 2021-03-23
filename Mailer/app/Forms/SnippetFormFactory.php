<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Forms\Rules\StringValidator;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\SnippetsRepository;

class SnippetFormFactory implements IFormFactory
{
    use SmartObject;

    private $snippetsRepository;

    public $onCreate;

    public $onUpdate;

    private $listsRepository;

    public function __construct(
        ListsRepository $listsRepository,
        SnippetsRepository $snippetsRepository
    ) {
        $this->snippetsRepository = $snippetsRepository;
        $this->listsRepository = $listsRepository;
    }

    public function create(?int $id = null): Form
    {
        $defaults = [];
        if ($id !== null) {
            $snippet = $this->snippetsRepository->find($id);
            $defaults = $snippet->toArray();
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

        $form->setDefaults($defaults);

        $form->addSubmit(self::FORM_ACTION_SAVE, self::FORM_ACTION_SAVE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-check"></i> Save');

        $form->addSubmit(self::FORM_ACTION_SAVE_CLOSE, self::FORM_ACTION_SAVE_CLOSE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save and close');

        $form->onValidate[] = [$this, 'validateSnippetForm'];
        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function validateSnippetForm(Form $form): void
    {
        $snippet = null;

        $values = $form->getValues();
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

            return;
        }
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        // decide if user wants to save or save and leave
        $buttonSubmitted = self::FORM_ACTION_SAVE;
        /** @var $buttonSaveClose SubmitButton */
        $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];
        if ($buttonSaveClose->isSubmittedBy()) {
            $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
        }

        if (!empty($values['id'])) {
            $row = $this->snippetsRepository->find($values['id']);
            $this->snippetsRepository->update($row, (array) $values);
            ($this->onUpdate)($row, $buttonSubmitted);
        } else {
            $row = $this->snippetsRepository->add($values['name'], $values['code'], $values['text'], $values['html'], $values['mail_type_id']);
            ($this->onCreate)($row, $buttonSubmitted);
        }
    }
}
