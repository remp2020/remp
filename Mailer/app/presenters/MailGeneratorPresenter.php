<?php

namespace Remp\MailerModule\Presenters;

use Remp\MailerModule\Components\IGeneratorWidgetsFactory;
use Remp\MailerModule\Forms\MailGeneratorFormFactory;
use Remp\MailerModule\Repository\SourceTemplatesRepository;

final class MailGeneratorPresenter extends BasePresenter
{
    const SESSION_SECTION_CONTENT_PREVIEW = "content_preview";

    private $sourceTemplatesRepository;

    private $mailGeneratorFormFactory;

    private $generatorWidgetsFactory;

    public function __construct(
        SourceTemplatesRepository $sourceTemplatesRepository,
        MailGeneratorFormFactory $mailGeneratorFormFactory,
        IGeneratorWidgetsFactory $generatorWidgetsFactory
    ) {
        parent::__construct();
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->mailGeneratorFormFactory = $mailGeneratorFormFactory;
        $this->generatorWidgetsFactory = $generatorWidgetsFactory;
    }

    public function renderDefault()
    {
        $this->template->last = $this->sourceTemplatesRepository->findLast()->fetch();
    }

    public function renderPreview($isLocked)
    {
        $section = $this->session->getSection(self::SESSION_SECTION_CONTENT_PREVIEW);
        $this->template->content = $isLocked ? $section->generatedLockedHtml : $section->generatedHtml;
    }

    protected function createComponentMailGeneratorForm()
    {
        $sourceTemplateId = $this->getSourceTemplateIdParameter();

        $form = $this->mailGeneratorFormFactory->create($sourceTemplateId, function ($htmlContent, $textContent, $controlParams = []) {
            $this->template->htmlContent = $htmlContent;
            $this->template->textContent = $textContent;
            $this->template->addonParams = $controlParams;
        }, function ($destination) {
            return $this->link($destination);
        });
        return $form;
    }

    protected function createComponentGeneratorWidgets()
    {
        return $this->generatorWidgetsFactory->create($this->getSourceTemplateIdParameter());
    }

    private function getSourceTemplateIdParameter()
    {
        $sourceTemplateId = $this->request->getParameter('source_template_id');
        if (!$sourceTemplateId) {
            $sourceTemplateId = $this->request->getPost('source_template_id');
        }
        return $sourceTemplateId;
    }

    public function handleSourceTemplateChange($sourceTemplateId)
    {
        $this->template->range = $sourceTemplateId;
        $this->template->redraw = true;
        $this->redrawControl('mailFormWrapper');
        $this->redrawControl('wrapper');
    }
}
