<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\ListCategoriesRepository;
use Remp\MailerModule\Repository\ListsRepository;

class ListFormFactory extends Object
{
    /** @var ListsRepository */
    private $listsRepository;

    /** @var ListCategoriesRepository */
    private $listCategoriesRepository;

    public $onSuccess;

    public function __construct(
        ListsRepository $listsRepository,
        ListCategoriesRepository $listCategoriesRepository
    ) {
        $this->listsRepository = $listsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        $categoryPairs = $this->listCategoriesRepository->all()->fetchPairs('id', 'title');
        $form
            ->addSelect('mail_type_category_id', 'Category', $categoryPairs)
            ->setRequired('Category is required');

        $form->addSelect('priority', 'Priority', [10 => 'High', 100 => 'Normal', 1000 => 'Low']);

        $form->addText('code', 'Code')
            ->setRequired('Code is required');

        $form->addText('title', 'Title')
            ->setRequired('Title is required');

        $form->addTextArea('description', 'Description')
            ->setAttribute('rows', 3);

        $form->addText('preview_url', 'Preview URL');

        $form->addText('image_url', 'Image URL');

        $order = ['begin' => 'At the beginning', 'end' => 'At the end', 'after' => 'After'];
        $form->addRadioList('sorting', 'Order', $order);

        $orderPairs = $this->listsRepository->findByCategory(key($categoryPairs))->fetchPairs('sorting', 'title');
        $form->addSelect('sorting_after', null, $orderPairs);

        $form->addCheckbox('auto_subscribe', 'Auto subscribe');
        $form->addCheckbox('locked', 'Locked');
        $form->addCheckbox('is_public', 'Public');

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
                $values['sorting'] = $this->listsRepository->findByCategory($values['mail_type_category_id'])->count('*') + 1;
                break;
        }

        $row = $this->listsRepository->add(
            $values['mail_type_category_id'],
            $values['priority'],
            $values['code'],
            $values['title'],
            $values['sorting'],
            $values['auto_subscribe'],
            $values['locked'],
            $values['is_public'],
            $values['description'],
            $values['preview_url'],
            $values['image_url']
        );
        ($this->onSuccess)($row);
    }
}
