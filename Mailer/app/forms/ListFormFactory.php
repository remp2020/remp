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

        $form->addText('code', 'Code')
            ->setRequired('Required');

        $form->addText('name', 'Name')
            ->setRequired('Required');

        $form->addTextArea('description', 'Description')
            ->setAttribute('rows', 3);

        $form->addCheckbox('is_consent_required', 'Required user consent');
        $form->addCheckbox('is_locked', 'Locked');
        $form->addCheckbox('is_public', 'Public');

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
            $row = $this->listsRepository->add(
                $values['code'],
                    $values['name'],
                    $values['description'],
                    $values['is_consent_required'],
                    $values['is_locked'],
                    $values['is_public']
            );
            ($this->onCreate)($row);
        }
    }
}
