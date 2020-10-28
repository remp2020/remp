<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\ContentGenerator\GeneratorInput;
use Remp\MailerModule\Repository\TemplatesRepository;

final class PreviewPresenter extends Presenter
{
    /** @var TemplatesRepository @inject */
    public $templatesRepository;

    /** @var EngineFactory @inject */
    public $engineFactory;

    /** @var ContentGenerator @inject */
    public $contentGenerator;

    public function renderPreview($code): void
    {
        $template = $this->templatesRepository->getByCode($code);
        if (!$template) {
            throw new BadRequestException();
        }
        if (!$template->mail_type->is_public) {
            throw new BadRequestException();
        }

        $mailContent = $this->contentGenerator->render(new GeneratorInput($template));
        $this->template->content = $mailContent->html();
    }
}
