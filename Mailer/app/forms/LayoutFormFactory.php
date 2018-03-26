<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Object;
use Remp\MailerModule\Repository\LayoutsRepository;

class LayoutFormFactory extends Object implements IFormFactory
{
    /** @var LayoutsRepository */
    private $layoutsRepository;

    public $onCreate;

    public $onUpdate;

    public function __construct(LayoutsRepository $layoutsRepository)
    {
        $this->layoutsRepository = $layoutsRepository;
    }

    public function create($id)
    {
        $defaults = [];
        if (isset($id)) {
            $layout = $this->layoutsRepository->find($id);
            $defaults = $layout->toArray();
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('name', 'Name')
            ->setRequired('Required');

        $form->addTextArea('layout_text', 'Text version')
            ->setAttribute('rows', 3);

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

    public function formSucceeded($form, $values)
    {
        // decide if user wants to save or save and leave
        $buttonSubmitted = self::FORM_ACTION_SAVE;
        /** @var $buttonSaveClose SubmitButton */
        $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];
        if ($buttonSaveClose->isSubmittedBy()) {
            $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
        }

        if (!empty($values['id'])) {
            $row = $this->layoutsRepository->find($values['id']);
            $this->layoutsRepository->update($row, $values);
            ($this->onUpdate)($row, $buttonSubmitted);
        } else {
            $row = $this->layoutsRepository->add($values['name'], $values['layout_text'], $values['layout_html']);
            ($this->onCreate)($row, $buttonSubmitted);
        }
    }
}
