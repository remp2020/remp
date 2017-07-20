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

            if ($defaults['sorting'] == 1) {
                $defaults['sorting'] = 'begin';
            } elseif ($defaults['sorting'] == $this->listsRepository->totalCount()) {
                $defaults['sorting'] = 'end';
            } else {
                $defaults['sorting_after'] = --$defaults['sorting'];
                $defaults['sorting'] = 'after';
            }
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('code', 'Code')
            ->setRequired('Required');

        $form->addText('title', 'Title')
            ->setRequired('Required');

        $form->addTextArea('description', 'Description')
            ->setAttribute('rows', 3);

        $order = ['begin' => 'At the beginning', 'end' => 'At the end', 'after' => 'After'];
        $form->addRadioList('sorting', 'Order', $order);

        $orderPairs = $this->listsRepository->all()->fetchPairs('sorting', 'title');
        $form->addSelect('sorting_after', null, $orderPairs);

        $form->addCheckbox('auto_subscribe', 'Required user consent');
        $form->addCheckbox('locked', 'Locked');
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
        switch ($values['sorting']) {
            case 'begin':
                $values['sorting'] = 1;
                break;

            case 'after':
                $values['sorting'] = $values['sorting_after'] + 1;
                break;

            default:
            case 'end':
                $values['sorting'] = $this->listsRepository->totalCount();
                break;
        }

        if (!empty($values['id'])) {
            $row = $this->listsRepository->find($values['id']);

            unset($values['sorting_after']);
            $this->listsRepository->update($row, $values);
            ($this->onUpdate)($row);
        } else {
            $row = $this->listsRepository->add(
                $values['code'],
                $values['title'],
                $values['description'],
                $values['sorting'],
                $values['auto_subscribe'],
                $values['locked'],
                $values['is_public']
            );
            ($this->onCreate)($row);
        }
    }
}
