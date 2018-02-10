<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Forms\SourceTemplateFormFactory;
use Remp\MailerModule\Repository\SourceTemplateRepository;

final class GeneratorPresenter extends BasePresenter
{
    private $mailSourceTemplateRepository;

    private $sourceTemplateFormFactory;

    public function __construct(
        SourceTemplateRepository $mailSourceTemplateRepository,
        SourceTemplateFormFactory $sourceTemplateFormFactory
    ) {
        parent::__construct();
        $this->mailSourceTemplateRepository = $mailSourceTemplateRepository;
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
        $mailSourceTemplates = $this->mailSourceTemplateRepository->all();
        $templatesCount = $mailSourceTemplates->count('*');

        $result = [
            'recordsTotal' => $templatesCount,
            'recordsFiltered' => $templatesCount,
            'data' => []
        ];

        /** @var ActiveRow $list */
        foreach ($mailSourceTemplates as $sourceTemplate) {
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
        $generator = $this->mailSourceTemplateRepository->find($id);
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
        $this->sourceTemplateFormFactory->onUpdate = function ($form, $mailSourceTemplate) {
            $this->flashMessage('Source template was successfully updated');
            $this->redirect('Edit', $mailSourceTemplate->id);
        };
        $this->sourceTemplateFormFactory->onSave = function ($form, $mailSourceTemplate) {
            $this->flashMessage('Source template was successfully Created');
            $this->redirect('Edit', $mailSourceTemplate->id);
        };
        return $form;
    }
}
