<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\NewslettersRepository;

class NewsletterFormFactory extends Object
{
    /** @var NewslettersRepository */
    private $newslettersRepository;

    public $onCreate;

    public $onUpdate;

    public function __construct(NewslettersRepository $newslettersRepository)
    {
        $this->newslettersRepository = $newslettersRepository;
    }

    public function create($id)
    {
        $defaults = [];
        if (isset($id)) {
            $newsletter = $this->newslettersRepository->find($id);
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
            $row = $this->newslettersRepository->find($values['id']);
            $this->newslettersRepository->update($row, $values);
            ($this->onUpdate)($row);
        } else {
            $row = $this->newslettersRepository->add($values['name'], $values['consent_required']);
            ($this->onCreate)($row);
        }
    }
}
