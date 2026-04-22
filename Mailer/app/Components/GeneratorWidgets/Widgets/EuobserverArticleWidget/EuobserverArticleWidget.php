<?php
declare(strict_types=1);

namespace Remp\Mailer\Components\GeneratorWidgets\Widgets\EuobserverArticleWidget;

use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Remp\Mailer\Forms\EuobserverTemplateFormFactory;
use Remp\MailerModule\Components\BaseControl;
use Remp\MailerModule\Components\GeneratorWidgets\Widgets\IGeneratorWidget;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Presenters\MailGeneratorPresenter;
use Remp\MailerModule\Repositories\ActiveRowFactory;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;

class EuobserverArticleWidget extends BaseControl implements IGeneratorWidget
{
    private string $templateName = 'euobserver_article_widget.latte';

    public function __construct(
        private Session $session,
        private LayoutsRepository $layoutsRepository,
        private ListsRepository $listsRepository,
        private ContentGenerator $contentGenerator,
        private GeneratorInputFactory $generatorInputFactory,
        private ActiveRowFactory $activeRowFactory,
        private EuobserverTemplateFormFactory $euobserverTemplateFormFactory,
    ) {
    }

    public function identifier(): string
    {
        return 'euobserverarticlewidget';
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

    public function createComponentEuobserverTemplateForm(): Form
    {
        $form = $this->euobserverTemplateFormFactory->create();
        $this->euobserverTemplateFormFactory->onSave = function () {
            $this->getPresenter()->flashMessage('Article email batch was created.');
            $this->getPresenter()->redirect('Job:Default');
        };

        return $form;
    }

    public function handleArticlePreview(): void
    {
        $request = $this->getPresenter()->getRequest();

        $htmlContent = $request->getPost('html_content');
        $textContent = $request->getPost('text_content');
        $lockedHtmlContent = $request->getPost('locked_html_content');
        $lockedTextContent = $request->getPost('locked_text_content');

        $mailLayout = $this->layoutsRepository->find($request->getPost('mail_layout_id'));
        $mailType = $this->listsRepository->find($request->getPost('mail_type_id'));

        $generate = function ($htmlContent, $textContent, $mailLayout, $mailType) use ($request) {
            $mailTemplate = $this->activeRowFactory->create([
                'name' => $request->getPost('name'),
                'code' => 'tmp_' . microtime(true),
                'description' => '',
                'from' => $request->getPost('from'),
                'autologin' => true,
                'subject' => $request->getPost('subject'),
                'preheader' => null,
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
        $lockedMailContent = $generate($lockedHtmlContent, $lockedTextContent, $mailLayout, $mailType);

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
