<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Remp\MailerModule\Repository\ListCategoriesRepository;
use Remp\MailerModule\Repository\ListsRepository;

class ListFormFactory
{
    use SmartObject;

    /** @var ListsRepository */
    private $listsRepository;

    /** @var ListCategoriesRepository */
    private $listCategoriesRepository;

    public $onCreate;
    public $onUpdate;

    public function __construct(
        ListsRepository $listsRepository,
        ListCategoriesRepository $listCategoriesRepository
    ) {
        $this->listsRepository = $listsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
    }

    public function create($id)
    {
        $list = null;
        $defaults = [];

        $form = new Form;
        $form->addProtection();

        if (isset($id)) {
            $list = $this->listsRepository->find($id);
            $defaults = $list->toArray();
        };

        $categoryPairs = $this->listCategoriesRepository->all()->fetchPairs('id', 'title');
        if (!isset($defaults['mail_type_category_id'])) {
            $defaults['mail_type_category_id'] = key($categoryPairs);
        }
        $form->addSelect(
            'mail_type_category_id',
            'Category',
            $categoryPairs
        )->setRequired("Field 'Category' is required.");

        $form->addText('priority', 'Priority')
            ->setRequired("Field 'Priority' is required.");

        $codeInput = $form->addText('code', 'Code')
            ->setRequired("Field 'Code' is required.");

        if ($list !== null) {
            $codeInput->setDisabled(true);
        }

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addTextArea('description', 'Description')
            ->setAttribute('rows', 3);

        $form->addText('preview_url', 'Preview URL');

        $form->addText('image_url', 'Image URL');

        $form->addRadioList('sorting', 'Order', [
            'begin' => 'At the beginning',
            'end' => 'At the end',
            'after' => 'After'
        ])->setRequired("Field 'Order' is required.");

        $orderPairs = $this->listsRepository
                        ->findByCategory($defaults['mail_type_category_id'])
                        ->order('sorting ASC')
                        ->fetchPairs('sorting', 'title');

        if ($list !== null) {
            $keys = array_keys($orderPairs);
            if (reset($keys) === $list->sorting) {
                $defaults['sorting'] = 'begin';
                unset($defaults['sorting_after']);
            } elseif(end($keys) === $list->sorting) {
                $defaults['sorting'] = 'end';
                unset($defaults['sorting_after']);
            } else {
                $defaults['sorting'] = 'after';
                $defaults['sorting_after'] = $list->sorting - 1;
            }

            unset($orderPairs[$list->sorting]);
        }

        $form->addSelect('sorting_after', null, $orderPairs)
                ->setPrompt('Choose newsletter list');

        $form->addCheckbox('auto_subscribe', 'Auto subscribe');
        $form->addCheckbox('locked', 'Locked');
        $form->addCheckbox('is_public', 'Public');

        $form->addHidden('id', $id);

        $form->setDefaults($defaults);

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    /**
     * @param Form $form
     * @param $values
     * @throws \Exception
     */
    public function formSucceeded($form, $values)
    {
        $list = null;
        if (isset($values['id'])) {
            $list = $this->listsRepository->find($values['id']);
        }

        $listsInCategory = $this->listsRepository->findByCategory($values['mail_type_category_id'])
                                        ->order('mail_types.sorting')
                                        ->fetchAll();

        switch ($values['sorting']) {
            case 'begin':
                $values['sorting'] = reset($listsInCategory)->sorting;
                break;

            case 'after':
                // fix missing form value because of dynamically loading select options
                // in ListPresenter->handleRenderSorting
                if ($values['sorting_after'] === null) {
                    $formHttpData = $form->getHttpData();

                    // + add validation
                    if (empty($formHttpData['sorting_after'])) {
                        $form->addError("Field 'Order' is required.");
                        return;
                    }
                    $values['sorting_after'] = $formHttpData['sorting_after'];
                }

                $values['sorting'] = $values['sorting_after'];
                if ($values['mail_type_category_id'] != $list->mail_type_category_id) {
                    $values['sorting'] += 1;
                }
                break;
            default:
            case 'end':
                $values['sorting'] = end($listsInCategory)->sorting + 1;
                break;
        }

        if (
            $values['mail_type_category_id'] == $list->mail_type_category_id
            && $list->sorting < $values['sorting']
        ) {
            $values['sorting'] -= 1;
        }

        $this->listsRepository->updateSorting(
            $values['mail_type_category_id'],
            $values['sorting'],
            is_null($list) ? null : $list->mail_type_category_id,
            is_null($list) ? null : $list->sorting
        );

        unset($values['sorting_after']);

        if ($list !== null) {
            $this->listsRepository->update($list, $values);
            $list = $this->listsRepository->find($list->id);
            ($this->onUpdate)($list);
        } else {
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
            ($this->onCreate)($row);
        }
    }
}
