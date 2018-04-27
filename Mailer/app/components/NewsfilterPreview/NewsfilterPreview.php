<?php

namespace Remp\MailerModule\Components;

use Nette\Application\Responses\JsonResponse;
use Nette\Database\Connection;
use Nette\Http\Session;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Forms\NewsfilterTemplateFormFactory;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class NewsfilterPreview extends BaseControl
{
    const SESSION_SECTION_NEWSFILTER_PREVIEW = "newsfilter_preview";

    private $templateName = 'newsfilter_preview.latte';

    private $connection;

    private $layoutsRepository;

    private $templatesRepository;

    /**
     * @var Session
     */
    private $session;


    public function __construct(
        Connection $connection,
        Session $session,
        LayoutsRepository $layoutsRepository,
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->layoutsRepository = $layoutsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->session = $session;
    }

    public function render($params)
    {
        if (!isset($params['addonParams']['render']) || !$params['addonParams']['render']) {
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

    public function createComponentNewsfilterTemplateForm(NewsfilterTemplateFormFactory $newsfilterTemplateFormFactory)
    {
        $form = $newsfilterTemplateFormFactory->create();
        $newsfilterTemplateFormFactory->onSave = function () {
            $this->getPresenter()->flashMessage("Newsfilter batches were created and run.");
            $this->getPresenter()->redirect("Job:Default");
        };

        return $form;
    }

    public function handleNewsfilterPreview()
    {
        $request = $this->getPresenter()->getRequest();

        $htmlContent = $request->getPost('html_content');
        $textContent = $request->getPost('text_content');
        $lockedHtmlContent = $request->getPost('locked_html_content');
        $lockedTextContent = $request->getPost('locked_text_content');

        $mailLayout = $this->layoutsRepository->find($_POST['mail_layout_id']);
        $lockedMailLayout = $this->layoutsRepository->find($_POST['locked_mail_layout_id']);

        $generate = function ($htmlContent, $textContent, $mailLayout) use ($request) {
            $this->connection->beginTransaction();
            $mailTemplate = $this->templatesRepository->add(
                $request->getPost('name'),
                'tmp_' . microtime(true),
                '',
                $request->getPost('from'),
                $request->getPost('subject'),
                $textContent,
                $htmlContent,
                $mailLayout->id,
                $request->getPost('mail_type_id')
            );

            $mailContentGenerator = new ContentGenerator($mailTemplate, $mailTemplate->layout, null);
            $generatedHtml = $mailContentGenerator->getHtmlBody([]);
            $generatedText = $mailContentGenerator->getTextBody([]);

            $this->connection->rollBack();
            return [$generatedHtml, $generatedText];
        };

        list($generatedHtml, $generatedText) = $generate($htmlContent, $textContent, $mailLayout);
        $this->template->generatedHtml = $generatedHtml;
        $this->template->generatedText = $generatedText;

        list($generatedLockedHtml, $generatedLockedText) = $generate($lockedHtmlContent, $lockedTextContent, $lockedMailLayout);
        $this->template->generatedLockedHtml = $generatedLockedHtml;
        $this->template->generatedLockedText = $generatedLockedText;

        // Store data in session for full-screen preview
        $sessionSection = $this->session->getSection(self::SESSION_SECTION_NEWSFILTER_PREVIEW);
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
