<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Repositories\TemplatesRepository;

final class PreviewPresenter extends Presenter
{
    /** @var TemplatesRepository @inject */
    public $templatesRepository;

    /** @var EngineFactory @inject */
    public $engineFactory;

    /** @var ContentGenerator @inject */
    public $contentGenerator;

    /** @var GeneratorInputFactory @inject */
    public $generatorInputFactory;

    public function renderPreview($code): void
    {
        $template = $this->templatesRepository->getByCode($code);
        if (!$template) {
            throw new BadRequestException();
        }
        if (!$template->mail_type->is_public) {
            throw new BadRequestException();
        }

        $mailContent = $this->contentGenerator->render($this->generatorInputFactory->create($template));
        $this->template->content = $mailContent->html();
    }
}
