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
    ) {
    
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
            ->setRowAction('edit', 'palette-Cyan zmdi-edit');

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $layoutsCount = $this->layoutsRepository
                ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'])
                ->count('*');

        $layouts = $this->layoutsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $request['length'], $request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->layoutsRepository->totalCount(),
            'recordsFiltered' => $layoutsCount,
            'data' => []
        ];

        foreach ($layouts as $layout) {
            $editUrl = $this->link('Edit', $layout->id);
            $result['data'][] = [
                'actions' => [
                    'edit' => $editUrl,
                ],
                "<a href='{$editUrl}'>{$layout->name}</a>",
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
        $this->layoutFormFactory->onCreate = function ($layout, $buttonSubmitted) use ($presenter) {
            $presenter->flashMessage('Layout was created');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $layout->id);
        };
        $this->layoutFormFactory->onUpdate = function ($layout, $buttonSubmitted) use ($presenter) {
            $presenter->flashMessage('Layout was updated');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $layout->id);
        };

        return $form;
    }
}
