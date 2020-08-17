<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\IDataTableFactory;
use Remp\MailerModule\Forms\SnippetFormFactory;
use Remp\MailerModule\Repositories\SnippetRepository;

final class SnippetPresenter extends BasePresenter
{
    /** @var SnippetRepository */
    private $snippetRepository;

    /** @var SnippetFormFactory */
    private $snippetFormFactory;

    public function __construct(
        SnippetRepository $snippetRepository,
        SnippetFormFactory $snippetFormFactory
    ) {
        parent::__construct();
        $this->snippetRepository = $snippetRepository;
        $this->snippetFormFactory = $snippetFormFactory;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory): DataTable
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setColSetting('name', [
                'priority' => 1,
            ])
            ->setColSetting('code', [
                'priority' => 1,
            ])
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 2,
            ])
            ->setRowAction('edit', 'palette-Cyan zmdi-edit', 'Edit snippet');

        return $dataTable;
    }

    public function renderDefaultJsonData(): void
    {
        $request = $this->request->getParameters();

        $query = $request['search']['value'];
        $order = $request['columns'][$request['order'][0]['column']]['name'];
        $orderDir = $request['order'][0]['dir'];
        $snippetsCount = $this->snippetRepository
                ->tableFilter($query, $order, $orderDir)
                ->count('*');

        $snippets = $this->snippetRepository
            ->tableFilter($query, $order, $orderDir, (int)$request['length'], (int)$request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->snippetRepository->totalCount(),
            'recordsFiltered' => $snippetsCount,
            'data' => []
        ];

        foreach ($snippets as $snippet) {
            $editUrl = $this->link('Edit', $snippet->id);
            $result['data'][] = [
                'actions' => [
                    'edit' => $editUrl,
                ],
                "<a href='{$editUrl}'>{$snippet->name}</a>",
                $snippet->code,
                $snippet->created_at,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderEdit($id): void
    {
        $snippet = $this->snippetRepository->find($id);
        if (!$snippet) {
            throw new BadRequestException();
        }

        $this->template->snippet = $snippet;
    }

    public function createComponentSnippetForm(): Form
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = (int)$this->params['id'];
        }

        $form = $this->snippetFormFactory->create($id);

        $presenter = $this;
        $this->snippetFormFactory->onCreate = function ($snippet, $buttonSubmitted) use ($presenter) {
            $presenter->flashMessage('Snippet was created');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $snippet->id);
        };
        $this->snippetFormFactory->onUpdate = function ($snippet, $buttonSubmitted) use ($presenter) {
            $presenter->flashMessage('Snippet was updated');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $snippet->id);
        };

        return $form;
    }
}
