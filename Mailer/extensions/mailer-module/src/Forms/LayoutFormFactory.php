<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Repositories\LayoutsRepository;

class LayoutFormFactory implements IFormFactory
{
    use SmartObject;

    /** @var LayoutsRepository */
    private $layoutsRepository;

    public $onCreate;

    public $onUpdate;

    public function __construct(LayoutsRepository $layoutsRepository)
    {
        $this->layoutsRepository = $layoutsRepository;
    }

    public function create(?int $id = null): Form
    {
        $layout = null;
        $defaults = [];
        if ($id !== null) {
            $layout = $this->layoutsRepository->find($id);
            $defaults = $layout->toArray();
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

        /** @var SubmitButton $buttonSaveClose */
        $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];
        if ($buttonSaveClose->isSubmittedBy()) {
            $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
        }

        if (!empty($values['id'])) {
            $row = $this->layoutsRepository->find($values['id']);
            $this->layoutsRepository->update($row, (array) $values);
            ($this->onUpdate)($row, $buttonSubmitted);
        } else {
            $row = $this->layoutsRepository->add(
                $values['name'],
                $values['code'],
                $values['layout_text'],
                $values['layout_html']
            );
            ($this->onCreate)($row, $buttonSubmitted);
        }
    }
}
