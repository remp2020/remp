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
    private $sourceTemplatesRepository;

    private $sourceTemplateFormFactory;

    private $dataTableFactory;

    public function __construct(
        SourceTemplatesRepository $sourceTemplatesRepository,
        SourceTemplateFormFactory $sourceTemplateFormFactory,
        DataTableFactory $dataTableFactory
    ) {
        parent::__construct();
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->sourceTemplateFormFactory = $sourceTemplateFormFactory;
        $this->dataTableFactory = $dataTableFactory;
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
            $result['data'][] = [
                'actions' => [
                    'edit' => $editUrl,
                    'generate' => $generateUrl,
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
        /** @var BaseControl $sorting */
        $sorting = $this['mailSourceTemplateForm']['sorting'];
        $sorting->setValue($sorting);

        $this->redrawControl('wrapper');
        $this->redrawControl('sortingAfterSnippet');
    }
}
