<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Remp\MailerModule\Components\IDataTableFactory;
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

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setColSetting('name')
            ->setColSetting('created_at', ['header' => 'created at', 'render' => 'date'])
            ->setRowLink($this->link('Edit', 'RowId'))
            ->setRowAction('edit', $this->link('Edit', 'RowId'),'palette-Cyan zmdi-edit');

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $layouts = $this->layoutsRepository->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir']);
        $result = [
            'recordsTotal' => $this->layoutsRepository->totalCount(),
            'recordsFiltered' => count($layouts),
            'data' => []
        ];

        $layouts = array_slice($layouts, $request['start'], $request['length']);

        foreach ($layouts as $layout) {
            $result['data'][] = [
                'RowId' => $layout->id,
                $layout->name,
                $layout->created_at,
            ];
        }
        $this->presenter->sendJson($result);
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
