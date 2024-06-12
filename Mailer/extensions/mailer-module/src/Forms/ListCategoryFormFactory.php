<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Exception;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Repositories\ListCategoriesRepository;

class ListCategoryFormFactory implements IFormFactory
{
    use SmartObject;

    public $onUpdate;

    public function __construct(
        private readonly ListCategoriesRepository $listCategoriesRepository
    ) {
    }

    public function create(int $id): Form
    {
        $form = new Form;
        $form->addProtection();

        $listCategory = $this->listCategoriesRepository->find($id);
        $defaults = $listCategory->toArray();

        $form->addText('sorting', 'Sorting')
            ->addRule(Form::INTEGER, "Sorting needs to be a number")
            ->addRule(Form::MIN, "Sorting needs to be greater than 0", 1)
            ->setRequired("Field 'Sorting' is required.");

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addHidden('id', $id);

        $form->setDefaults($defaults);

        $form->addSubmit(self::FORM_ACTION_SAVE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-check"></i> Save');

        $form->addSubmit(self::FORM_ACTION_SAVE_CLOSE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save and close');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    /**
     * @param Form $form
     * @param ArrayHash $values
     * @throws Exception
     */
    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $listCategory = null;
        if (isset($values['id'])) {
            $listCategory = $this->listCategoriesRepository->find($values['id']);
        }

        if ($listCategory) {
            $buttonSubmitted = self::FORM_ACTION_SAVE;
            /** @var SubmitButton $buttonSaveClose */
            $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];

            if ($buttonSaveClose->isSubmittedBy()) {
                $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
            }

            $this->listCategoriesRepository->update($listCategory, (array)$values);
            $listCategory = $this->listCategoriesRepository->find($listCategory->id);
            ($this->onUpdate)($listCategory, $buttonSubmitted);
        }

        throw new Exception('List category not found.');
    }
}
