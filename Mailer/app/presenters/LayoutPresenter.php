<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Remp\MailerModule\Forms\LayoutFormFactory;
use Remp\MailerModule\Repository\LayoutsRepository;

final class LayoutPresenter extends BasePresenter
{
    /** @var LayoutsRepository */
    private $layoutsRepository;

    /** @var LayoutFormFactory */
    private $layoutFormFactory;

    public function __construct(
        LayoutsRepository $layoutsRepository,
        LayoutFormFactory $layoutFormFactory
    )
    {
        parent::__construct();
        $this->layoutsRepository = $layoutsRepository;
        $this->layoutFormFactory = $layoutFormFactory;
    }

    public function renderDefault()
    {
        $this->template->layouts = $this->layoutsRepository->all();
    }

    public function renderEdit($id)
    {
        $layout = $this->layoutsRepository->find($id);
        if (!$layout) {
            throw new BadRequestException();
        }

        $this->template->layout = $layout;
    }

    public function createComponentLayoutForm()
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = intval($this->params['id']);
        }

        $form = $this->layoutFormFactory->create($id);

        $presenter = $this;
        $this->layoutFormFactory->onCreate = function ($layout) use ($presenter) {
            $presenter->flashMessage('Layout was created');
            $presenter->redirect('Edit', $layout->id);
        };
        $this->layoutFormFactory->onUpdate = function ($layout) use ($presenter) {
            $presenter->flashMessage('Layout was updated');
            $presenter->redirect('Edit', $layout->id);
        };

        return $form;
    }
}
