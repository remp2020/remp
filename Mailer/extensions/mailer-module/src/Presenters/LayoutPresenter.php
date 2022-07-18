<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Forms\LayoutFormFactory;
use Remp\MailerModule\Repositories\LayoutsRepository;

final class LayoutPresenter extends BasePresenter
{
    private $layoutsRepository;

    private $layoutFormFactory;

    private $dataTableFactory;

    public function __construct(
        LayoutsRepository $layoutsRepository,
        LayoutFormFactory $layoutFormFactory,
        DataTableFactory $dataTableFactory
    ) {
        parent::__construct();
        $this->layoutsRepository = $layoutsRepository;
        $this->layoutFormFactory = $layoutFormFactory;
        $this->dataTableFactory = $dataTableFactory;
    }

    public function createComponentDataTableDefault(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setColSetting('name', [
                'priority' => 1,
                'render' => 'link',
            ])
            ->setColSetting('code', [
                'priority' => 1,
            ])
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 2,
            ])
            ->setRowAction('edit', 'palette-Cyan zmdi-edit', 'Edit layout')
            ->setRowAction('delete', 'palette-Red zmdi-delete', 'Delete layout', [
                'onclick' => 'return confirm(\'Are you sure you want to delete this item?\');'
            ]);

        return $dataTable;
    }

    public function renderDefaultJsonData(): void
    {
        $request = $this->request->getParameters();

        $layoutsCount = $this->layoutsRepository
                ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'])
                ->count('*');

        $layouts = $this->layoutsRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], (int)$request['length'], (int)$request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->layoutsRepository->totalCount(),
            'recordsFiltered' => $layoutsCount,
            'data' => []
        ];

        foreach ($layouts as $layout) {
            $editUrl = $this->link('Edit', $layout->id);
            $deleteUrl = $this->link('Delete!', $layout->id);
            $result['data'][] = [
                'actions' => [
                    'edit' => $editUrl,
                    'delete' => $deleteUrl,
                ],
                [
                    'url' => $editUrl,
                    'text' => $layout->name,
                ],
                $layout->code,
                $layout->created_at,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderEdit($id): void
    {
        $layout = $this->layoutsRepository->find($id);
        if (!$layout) {
            throw new BadRequestException();
        }

        $this->template->layout = $layout;
    }

    public function createComponentLayoutForm(): Form
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = (int)$this->params['id'];
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

    public function handleDelete($id): void
    {
        $layout = $this->layoutsRepository->find($id);

        if ($this->layoutsRepository->canBeDeleted($layout)) {
            $this->layoutsRepository->softDelete($layout);
            $this->flashMessage("Layout {$layout->name} was deleted.");
            $this->redirect('default');
        } else {
            $this->flashMessage("There are emails using layout {$layout->name}.", 'danger');
            $this->redirect('Template:default', ['layout' => $layout->id]);
        }
    }
}
