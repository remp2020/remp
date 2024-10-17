<?php
declare(strict_types=1);

namespace Remp\Mailer\Components\GeneratorWidgets\Widgets\NewsfilterWidget;

use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Remp\Mailer\Forms\NewsfilterTemplateFormFactory;
use Remp\MailerModule\Components\BaseControl;
use Remp\MailerModule\Components\GeneratorWidgets\Widgets\IGeneratorWidget;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Presenters\MailGeneratorPresenter;
use Remp\MailerModule\Repositories\ActiveRowFactory;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;

class NewsfilterWidget extends BaseControl implements IGeneratorWidget
{
    private string $templateName = 'newsfilter_widget.latte';

    public function __construct(
        private Session $session,
        private LayoutsRepository $layoutsRepository,
        private ListsRepository $listsRepository,
        private ContentGenerator $contentGenerator,
        private NewsfilterTemplateFormFactory $newsfilterTemplateFormFactory,
        private GeneratorInputFactory $generatorInputFactory,
        private ActiveRowFactory $activeRowFactory,
    ) {
    }

    public function identifier(): string
    {
        return "newsfilterwidget";
    }

    public function render($params): void
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

    public function createComponentNewsfilterTemplateForm(): Form
    {
        $form = $this->newsfilterTemplateFormFactory->create();
        $this->newsfilterTemplateFormFactory->onSave = function () {
            $this->getPresenter()->flashMessage("Newsfilter batches were created and run.");
            $this->getPresenter()->redirect("Job:Default");
        };

        return $form;
    }

    public function handleNewsfilterPreview(): void
    {
        $request = $this->getPresenter()->getRequest();

        $htmlContent = $request->getPost('html_content');
        $textContent = $request->getPost('text_content');
        $lockedHtmlContent = $request->getPost('locked_html_content');
        $lockedTextContent = $request->getPost('locked_text_content');

        $mailLayout = $this->layoutsRepository->findBy('code', $request->getPost('mail_layout_code'));
        $lockedMailLayout = $this->layoutsRepository->findBy('code', $request->getPost('locked_mail_layout_code'));
        $mailType = $this->listsRepository->findBy('code', $request->getPost('mail_type_code'));

        $generate = function ($htmlContent, $textContent, $mailLayout, $mailType) use ($request) {
            $mailTemplate = $this->activeRowFactory->create([
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
                'mail_type_id' => $mailType->id,
                'mail_type' => $mailType,
                'params' => null,
                'click_tracking' => false,
            ]);

            return $this->contentGenerator->render($this->generatorInputFactory->create($mailTemplate));
        };

        $mailContent = $generate($htmlContent, $textContent, $mailLayout, $mailType);
        $this->template->generatedHtml = $mailContent->html();
        $this->template->generatedText = $mailContent->text();

        $lockedMailContent = $generate($lockedHtmlContent, $lockedTextContent, $lockedMailLayout, $mailType);
        $this->template->generatedLockedHtml = $lockedMailContent->html();
        $this->template->generatedLockedText = $lockedMailContent->text();

        // Store data in session for full-screen preview
        $sessionSection = $this->session->getSection(MailGeneratorPresenter::SESSION_SECTION_CONTENT_PREVIEW);
        $sessionSection->generatedHtml = $mailContent->html();
        $sessionSection->generatedLockedHtml = $lockedMailContent->html();

        $response = new JsonResponse([
            'generatedHtml' => $mailContent->html(),
            'generatedText' => $mailContent->text(),
            'generatedLockedHtml' => $lockedMailContent->html(),
            'generatedLockedText' => $lockedMailContent->text(),
        ]);
        $this->getPresenter()->sendResponse($response);
    }
}
