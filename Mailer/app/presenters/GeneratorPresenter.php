<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Forms\SourceTemplateFormFactory;
use Remp\MailerModule\Repository\SourceTemplatesRepository;

final class GeneratorPresenter extends BasePresenter
{
    private $sourceTemplatesRepository;

    private $sourceTemplateFormFactory;

    public function __construct(
        SourceTemplatesRepository $sourceTemplatesRepository,
        SourceTemplateFormFactory $sourceTemplateFormFactory
    ) {
        parent::__construct();
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->sourceTemplateFormFactory = $sourceTemplateFormFactory;
    }

    public function createComponentDataTableDefault(IDataTableFactory $dataTableFactory)
    {
        $dataTable = $dataTableFactory->create();
        $dataTable
            ->setColSetting('created_at', ['header' => 'created at', 'render' => 'date'])
            ->setColSetting('title')
            ->setColSetting('generator')
            ->setRowAction('edit', 'palette-Cyan zmdi-edit')
            ->setRowAction('generate', 'palette-Cyan zmdi-spellcheck')
            ->setTableSetting('sorting', Json::encode([[0, 'DESC']]));

        return $dataTable;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $sourceTemplatesCount = $this->sourceTemplatesRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'])
            ->count('*');

        $sourceTemplates = $this->sourceTemplatesRepository
            ->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir'], $request['length'], $request['start'])
            ->fetchAll();

        $result = [
            'recordsTotal' => $this->sourceTemplatesRepository->totalCount(),
            'recordsFiltered' => $sourceTemplatesCount,
            'data' => []
        ];

        /** @var ActiveRow $list */
        foreach ($sourceTemplates as $sourceTemplate) {
            $editUrl = $this->link('Edit', $sourceTemplate->id);
            $generateUrl = $this->link('Generate', $sourceTemplate->id);
            $result['data'][] = [
                'actions' => [
                    'edit' => $editUrl,
                    'generate' => $generateUrl,
                ],
                $sourceTemplate->created_at,
                "<a href='{$editUrl}'>{$sourceTemplate->title}</a>",
                "<code>{$sourceTemplate->generator}</code>",
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderEdit($id)
    {
        $generator = $this->sourceTemplatesRepository->find($id);
        if (!$generator) {
            throw new BadRequestException();
        }
        $this->template->generator = $generator;
    }

    public function renderDefault()
    {
    }

    public function renderGenerate($id)
    {
        throw new \Exception('TODO');
    }

    public function createComponentMailSourceTemplateForm()
    {
        $form = $this->sourceTemplateFormFactory->create(isset($this->params['id']) ? $this->params['id'] : null);
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
}
