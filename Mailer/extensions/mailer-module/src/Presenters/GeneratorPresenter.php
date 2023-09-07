<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Forms\SourceTemplateFormFactory;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;

final class GeneratorPresenter extends BasePresenter
{
    public function __construct(
        private SourceTemplatesRepository $sourceTemplatesRepository,
        private SourceTemplateFormFactory $sourceTemplateFormFactory,
        private DataTableFactory $dataTableFactory
    ) {
        parent::__construct();
    }

    public function createComponentDataTableDefault(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setColSetting('title', [
                'priority' => 1,
                'render' => 'link',
            ])
            ->setColSetting('code', [
                'priority' => 1,
                'render' => 'code',
            ])
            ->setColSetting('generator', [
                'priority' => 2,
                'render' => 'code',
            ])
            ->setColSetting('created_at', [
                'header' => 'created at',
                'render' => 'date',
                'priority' => 3,
            ])
            ->setColSetting('updated_at', [
                'header' => 'updated at',
                'render' => 'date',
                'priority' => 3,
            ])
            ->setRowAction('edit', 'palette-Cyan zmdi-edit', 'Edit generator')
            ->setRowAction('delete', 'palette-Red zmdi-delete', 'Delete generator', [
                'onclick' => 'return confirm(\'Are you sure you want to delete this item?\');'
            ])
            ->setRowAction('generate', 'palette-Cyan zmdi-spellcheck', 'Generate emails')
            ->setTableSetting('order', '[]');

        return $dataTable;
    }

    public function renderDefaultJsonData(): void
    {
        $request = $this->request->getParameters();
        [$orderColumn, $orderDir] = $this->dataTableFactory->getOrderFromRequest($request);

        $sourceTemplatesCount = $this->sourceTemplatesRepository
            ->tableFilter($request['search']['value'], $orderColumn, $orderDir)
            ->count('*');

        $sourceTemplates = $this->sourceTemplatesRepository
            ->tableFilter($request['search']['value'], $orderColumn, $orderDir, (int)$request['length'], (int)$request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->sourceTemplatesRepository->totalCount(),
            'recordsFiltered' => $sourceTemplatesCount,
            'data' => []
        ];

        /** @var ActiveRow $sourceTemplate */
        foreach ($sourceTemplates as $i => $sourceTemplate) {
            $editUrl = $this->link('Edit', $sourceTemplate->id);
            $generateUrl = $this->link('Generate', $sourceTemplate->id);
            $deleteUrl = $this->link('Delete!', $sourceTemplate->id);
            $result['data'][] = [
                'actions' => [
                    'edit' => $editUrl,
                    'generate' => $generateUrl,
                    'delete' => $deleteUrl,
                ],
                [
                    'url' => $editUrl,
                    'text' => $sourceTemplate->title,
                ],
                $sourceTemplate->code,
                $sourceTemplate->generator,
                $sourceTemplate->created_at,
                $sourceTemplate->updated_at,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderEdit($id): void
    {
        $generator = $this->sourceTemplatesRepository->find($id);
        if (!$generator) {
            throw new BadRequestException();
        }
        $this->template->generator = $generator;
    }

    public function renderGenerate($id): void
    {
        $this->redirect("MailGenerator:default", ['source_template_id' => $id]);
    }

    public function createComponentMailSourceTemplateForm(): Form
    {
        $form = $this->sourceTemplateFormFactory->create(isset($this->params['id']) ? (int)$this->params['id'] : null);

        $this->sourceTemplateFormFactory->onUpdate = function ($form, $mailSourceTemplate, $buttonSubmitted) {
            $this->flashMessage('Source template was successfully updated');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $mailSourceTemplate->id);
        };
        $this->sourceTemplateFormFactory->onSave = function ($form, $mailSourceTemplate, $buttonSubmitted) {
            $this->flashMessage('Source template was successfully created');
            $this->redirectBasedOnButtonSubmitted($buttonSubmitted, $mailSourceTemplate->id);
        };
        return $form;
    }

    public function handleRenderSorting($sorting): void
    {
        /** @var BaseControl $sortingInput */
        $sortingInput = $this['mailSourceTemplateForm']['sorting'];
        $sortingInput->setValue($sorting);

        $this->redrawControl('wrapper');
        $this->redrawControl('sortingAfterSnippet');
    }

    public function handleDelete($id): void
    {
        $sourceTemplate = $this->sourceTemplatesRepository->find($id);
        $this->sourceTemplatesRepository->softDelete($sourceTemplate);
        $this->flashMessage("Generator {$sourceTemplate->title} was deleted.");
        $this->redirect('default');
    }
}
