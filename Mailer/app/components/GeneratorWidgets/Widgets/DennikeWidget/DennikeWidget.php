<?php

namespace Remp\MailerModule\Components\GeneratorWidgets\Widgets;

use Nette\Application\Responses\JsonResponse;
use Nette\Http\Session;
use Remp\MailerModule\Components\BaseControl;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Forms\DennikeTemplateFormFactory;
use Remp\MailerModule\Presenters\MailGeneratorPresenter;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class DennikeWidget extends BaseControl implements IGeneratorWidget
{
    private $templateName = 'dennike_widget.latte';

    private $layoutsRepository;

    private $templatesRepository;

    private $session;

    private $listsRepository;

    public function __construct(
        Session $session,
        LayoutsRepository $layoutsRepository,
        TemplatesRepository $templatesRepository,
        ListsRepository $listsRepository
    ) {
        parent::__construct();
        $this->layoutsRepository = $layoutsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->session = $session;
        $this->listsRepository = $listsRepository;
    }

    public function identifier()
    {
        return "dennikewidget";
    }

    public function render($params)
    {
        if (!isset($params['addonParams'])) {
            return;
        }
        foreach ($params['addonParams'] as $var => $param) {
            $this->template->$var = $param;
        }
        $this->template->htmlContent = $params['htmlContent'];
        $this->template->textContent = $params['textContent'];
        $this->template->setFile(__DIR__ . '/' . $this->templateName);
        $this->template->render();
    }

    public function createComponentDennikeTemplateForm(DennikeTemplateFormFactory $dennikeTemplateFormFactory)
    {
        $form = $dennikeTemplateFormFactory->create();
        $dennikeTemplateFormFactory->onSave = function () {
            $this->getPresenter()->flashMessage("Dennike batches were created and run.");
            $this->getPresenter()->redirect("Job:Default");
        };

        return $form;
    }

    public function handleDennikePreview()
    {
        $request = $this->getPresenter()->getRequest();

        $htmlContent = $request->getPost('html_content');
        $textContent = $request->getPost('text_content');
        $lockedHtmlContent = $request->getPost('locked_html_content');
        $lockedTextContent = $request->getPost('locked_text_content');

        $mailLayout = $this->layoutsRepository->find($_POST['mail_layout_id']);
        $lockedMailLayout = $this->layoutsRepository->find($_POST['locked_mail_layout_id']);
        $mailType = $this->listsRepository->find($_POST['mail_type_id']);

        $generate = function ($htmlContent, $textContent, $mailLayout, $mailType) use ($request) {
            $mailTemplate = new DataRow([
                'name' => $request->getPost('name'),
                'code' => 'tmp_' . microtime(true),
                'description' => '',
                'from' => $request->getPost('from'),
                'autologin' => true,
                'subject' => $request->getPost('subject'),
                'mail_body_text' => $textContent,
                'mail_body_html' => $htmlContent,
                'mail_layout_id' => $mailLayout->id,
                'mail_layout' => $mailLayout,
                'mail_type_id' => $request->getPost('mail_type_id'),
                'mail_type' => $mailType
            ]);

            $mailContentGenerator = new ContentGenerator($mailTemplate, $mailLayout, null);
            $generatedHtml = $mailContentGenerator->getHtmlBody([]);
            $generatedText = $mailContentGenerator->getTextBody([]);

            return [$generatedHtml, $generatedText];
        };

        list($generatedHtml, $generatedText) = $generate($htmlContent, $textContent, $mailLayout, $mailType);
        $this->template->generatedHtml = $generatedHtml;
        $this->template->generatedText = $generatedText;

        list($generatedLockedHtml, $generatedLockedText) = $generate($lockedHtmlContent, $lockedTextContent, $lockedMailLayout, $mailType);
        $this->template->generatedLockedHtml = $generatedLockedHtml;
        $this->template->generatedLockedText = $generatedLockedText;

        // Store data in session for full-screen preview
        $sessionSection = $this->session->getSection(MailGeneratorPresenter::SESSION_SECTION_CONTENT_PREVIEW);
        $sessionSection->generatedHtml = $generatedHtml;
        $sessionSection->generatedLockedHtml = $generatedLockedHtml;

        $response = new JsonResponse([
            'generatedHtml' => $generatedHtml,
            'generatedText' => $generatedText,
            'generatedLockedHtml' => $generatedLockedHtml,
            'generatedLockedText' => $generatedLockedText,
        ]);
        $this->getPresenter()->sendResponse($response);
    }
}
