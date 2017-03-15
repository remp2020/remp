<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\LayoutsRepository;

class LayoutFormFactory extends Object
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
        // $form->setRenderer(new BootstrapRenderer());
        $form->addProtection();

        $form->addHidden('layout_id', $id);

        $form->addText('name', 'Name')
            ->setRequired('Required');

        $form->addTextArea('layout_text', 'Text version')
            ->setAttribute('rows', 3);

        $form->addTextArea('layout_html', 'HTML version');

        $form->setDefaults($defaults);

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $id = $values['layout_id'];
        unset($values['layout_id']);

        if (!empty($id)) {
            $row = $this->layoutsRepository->find($id);
            $this->layoutsRepository->update($row, $values);
            ($this->onUpdate)($row);
        } else {
            $row = $this->layoutsRepository->add($values['name'], $values['layout_text'], $values['layout_html']);
            ($this->onCreate)($row);
        }
    }
}
