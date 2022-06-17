<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Forms\SnippetFormFactory;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\SnippetTranslationsRepository;

final class SnippetPresenter extends BasePresenter
{
    private SnippetsRepository $snippetsRepository;

    private SnippetFormFactory $snippetFormFactory;

    private DataTableFactory $dataTableFactory;

    private SnippetTranslationsRepository $snippetTranslationsRepository;

    public function __construct(
        SnippetsRepository $snippetsRepository,
        SnippetFormFactory $snippetFormFactory,
        DataTableFactory $dataTableFactory,
        SnippetTranslationsRepository $snippetTranslationsRepository
    ) {
        parent::__construct();
        $this->snippetsRepository = $snippetsRepository;
        $this->snippetFormFactory = $snippetFormFactory;
        $this->dataTableFactory = $dataTableFactory;
        $this->snippetTranslationsRepository = $snippetTranslationsRepository;
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
            ->setColSetting('mail_type_id', [
                'header' => 'mail type',
                'priority' => 1,
            ])
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 2,
            ])
            ->setRowAction('edit', 'palette-Cyan zmdi-edit', 'Edit snippet')
            ->setRowAction('delete', 'palette-Red zmdi-delete', 'Delete snippet', [
                'onclick' => 'return confirm(\'Are you sure you want to delete this item?\');'
            ]);

        return $dataTable;
    }

    public function renderDefaultJsonData(): void
    {
        $request = $this->request->getParameters();

        $query = $request['search']['value'];
        $order = $request['columns'][$request['order'][0]['column']]['name'];
        $orderDir = $request['order'][0]['dir'];
        $snippetsCount = $this->snippetsRepository
                ->tableFilter($query, $order, $orderDir)
                ->count('*');

        $snippets = $this->snippetsRepository
            ->tableFilter($query, $order, $orderDir, (int)$request['length'], (int)$request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->snippetsRepository->totalCount(),
            'recordsFiltered' => $snippetsCount,
            'data' => []
        ];

        foreach ($snippets as $snippet) {
            $editUrl = $this->link('Edit', $snippet->id);
            $deleteUrl = $this->link('Delete!', $snippet->id);
            $result['data'][] = [
                'actions' => [
                    'edit' => $editUrl,
                    'delete' => $deleteUrl
                ],
                [
                    'url' => $editUrl,
                    'text' => $snippet->name,
                ],
                $snippet->code,
                $snippet->mail_type->title ?? null,
                $snippet->created_at,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderEdit($id): void
    {
        $snippet = $this->snippetsRepository->find($id);
        if (!$snippet) {
            throw new BadRequestException();
        }

        $this->template->snippet = $snippet;
    }

    public function handleDelete($id): void
    {
        $snippet = $this->snippetsRepository->find($id);
        $this->snippetTranslationsRepository->getTranslationsForSnippet($snippet)->delete();
        $this->snippetsRepository->delete($snippet);
        $this->flashMessage("Snippet {$snippet->name} was deleted.");
        $this->redirect('default');
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
