<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Remp\MailerModule\Forms\IFormFactory;
use Remp\MailerModule\Forms\ListCategoryFormFactory;
use Remp\MailerModule\Repositories\ListCategoriesRepository;

final class ListCategoryPresenter extends BasePresenter
{
    public function __construct(
        private readonly ListCategoriesRepository $listCategoriesRepository,
        private readonly ListCategoryFormFactory $listCategoryFormFactory
    ) {
        parent::__construct();
    }

    public function renderEdit($id): void
    {
        $listCategory = $this->listCategoriesRepository->find($id);
        if (!$listCategory) {
            throw new BadRequestException();
        }

        $this->template->listCategory = $listCategory;
    }

    public function createComponentListCategoryForm(): Form
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = (int)$this->params['id'];
        }

        $form = $this->listCategoryFormFactory->create($id);

        $this->listCategoryFormFactory->onUpdate = function ($list, $buttonSubmitted) {
            $this->flashMessage('Newsletter list category was updated');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $list->id);
        };

        return $form;
    }

    protected function redirectBasedOnButtonSubmitted(string $buttonSubmitted, int $itemID = null): void
    {
        if ($buttonSubmitted === IFormFactory::FORM_ACTION_SAVE_CLOSE || is_null($itemID)) {
            $this->redirect('List:Default');
        } else {
            $this->redirect('Edit', $itemID);
        }
    }
}
