<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Components\IDataTableFactory;
use Remp\MailerModule\Components\INewsfilterPreviewFactory;
use Remp\MailerModule\Components\NewsfilterPreview;
use Remp\MailerModule\Forms\MailGeneratorFormFactory;
use Remp\MailerModule\Forms\SourceTemplateFormFactory;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

final class MailGeneratorPresenter extends BasePresenter
{
    private $sourceTemplatesRepository;

    private $mailGeneratorFormFactory;

    private $newsfilterPreviewFactory;

    private $templatesRepository;


    public function __construct(
        SourceTemplatesRepository $sourceTemplatesRepository,
        MailGeneratorFormFactory $mailGeneratorFormFactory,
        INewsfilterPreviewFactory $newsfilterPreviewFactory,
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->mailGeneratorFormFactory = $mailGeneratorFormFactory;
        $this->newsfilterPreviewFactory = $newsfilterPreviewFactory;
        $this->templatesRepository = $templatesRepository;
    }

    public function renderDefault()
    {
        $this->template->last = $this->sourceTemplatesRepository->findLast()->fetch();
    }

    public function renderPreview($isLocked)
    {
        $section = $this->session->getSection(NewsfilterPreview::SESSION_SECTION_NEWSFILTER_PREVIEW);
        $this->template->content = $isLocked ? $section->generatedLockedHtml : $section->generatedHtml;
    }

    protected function createComponentMailGeneratorForm()
    {
        $sourceTemplateId = $this->request->getParameter('source_template_id');
        if (!$sourceTemplateId) {
            $sourceTemplateId = $this->request->getPost('source_template_id');
        }

        $form = $this->mailGeneratorFormFactory->create($sourceTemplateId, function ($htmlContent, $textContent, $controlParams = []) {
            $this->template->htmlContent = $htmlContent;
            $this->template->textContent = $textContent;
            $this->template->addonParams = $controlParams;
        }, function ($destination) {
            return $this->link($destination);
        });
        return $form;
    }

    protected function createComponentNewsfilterPreview()
    {
        return $this->newsfilterPreviewFactory->create();
    }

    public function handleSourceTemplateChange($sourceTemplateId)
    {
        $this->template->range = $sourceTemplateId;
        $this->template->redraw = true;
        $this->redrawControl('mailFormWrapper');
        $this->redrawControl('wrapper');
    }
}
