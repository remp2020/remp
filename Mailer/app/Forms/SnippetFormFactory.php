<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Forms\Rules\StringValidator;
use Remp\MailerModule\Repositories\SnippetRepository;

class SnippetFormFactory implements IFormFactory
{
    use SmartObject;

    /** @var SnippetRepository */
    private $snippetRepository;

    public $onCreate;

    public $onUpdate;

    public function __construct(SnippetRepository $snippetRepository)
    {
        $this->snippetRepository = $snippetRepository;
    }

    public function create(?int $id = null): Form
    {
        $defaults = [];
        if ($id !== null) {
            $snippet = $this->snippetRepository->find($id);
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

        $form->addTextArea('text', 'Text version')
            ->setAttribute('rows', 3);

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

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
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
            $row = $this->snippetRepository->find($values['id']);
            $this->snippetRepository->update($row, (array) $values);
            ($this->onUpdate)($row, $buttonSubmitted);
        } else {
            $row = $this->snippetRepository->add($values['name'], $values['code'], $values['text'], $values['html']);
            ($this->onCreate)($row, $buttonSubmitted);
        }
    }
}
