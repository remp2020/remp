<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\ListsRepository;

class ListFormFactory extends Object
{
    /** @var ListsRepository */
    private $listsRepository;

    public $onCreate;

    public $onUpdate;

    public function __construct(ListsRepository $listsRepository)
    {
        $this->listsRepository = $listsRepository;
    }

    public function create($id)
    {
        $defaults = [];
        if (isset($id)) {
            $newsletter = $this->listsRepository->find($id);
            $defaults = $newsletter->toArray();
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('name', 'Name')
            ->setRequired('Required');

        $form->addCheckbox('consent_required', 'Required user consent');

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
        if (!empty($values['id'])) {
            $row = $this->listsRepository->find($values['id']);
            $this->listsRepository->update($row, $values);
            ($this->onUpdate)($row);
        } else {
            $row = $this->listsRepository->add($values['name'], $values['consent_required']);
            ($this->onCreate)($row);
        }
    }
}
