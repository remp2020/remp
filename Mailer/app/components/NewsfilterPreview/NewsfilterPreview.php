<?php

namespace Remp\MailerModule\Components;

use Kdyby\Autowired\AutowireComponentFactories;
use Nette\Application\Responses\JsonResponse;
use Nette\Database\Connection;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Form\Rendering\MaterialRenderer;
use Remp\MailerModule\Forms\NewsfilterTemplateFormFactory;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class NewsfilterPreview extends BaseControl
{
    private $templateName = 'newsfilter_preview.latte';

    private $connection;

    private $mailLayoutsRepository;

    private $mailTemplatesRepository;

    public function __construct(
        Connection $connection,
        LayoutsRepository $mailLayoutsRepository,
        TemplatesRepository $mailTemplatesRepository
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->mailLayoutsRepository = $mailLayoutsRepository;
        $this->mailTemplatesRepository = $mailTemplatesRepository;
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
        $newsfilterTemplateFormFactory->onSave = function ($withMailJob) {

            if ($withMailJob) {
                $this->getPresenter()->redirect(":Mail:MailJobsAdmin:Default");
            }
            $this->getPresenter()->redirect(":Mail:MailTemplatesAdmin:Default");
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

        $mailLayout = $this->mailLayoutsRepository->find($_POST['mail_layout_id']);
        $lockedMailLayout = $this->mailLayoutsRepository->find($_POST['locked_mail_layout_id']);

        $generate = function ($htmlContent, $textContent, $mailLayout) use ($request) {
            $this->connection->beginTransaction();
            $mailTemplate = $this->mailTemplatesRepository->add(
                'tmp_' . microtime(true),
                $request->getPost('name'),
                '',
                $request->getPost('from'),
                $request->getPost('subject'),
                $textContent,
                $htmlContent,
                $mailLayout->id,
                $request->getPost('mail_type_id')
            );

            // TODO batchId - what it should be?
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

        $response = new JsonResponse([
            'generatedHtml' => $generatedHtml,
            'generatedText' => $generatedText,
            'generatedLockedHtml' => $generatedLockedHtml,
            'generatedLockedText' => $generatedLockedText
        ]);
        $this->getPresenter()->sendResponse($response);
    }
}
