<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Repositories\TemplatesRepository;

final class PreviewPresenter extends FrontendPresenter
{
    /** @var TemplatesRepository @inject */
    public $templatesRepository;

    /** @var ContentGenerator @inject */
    public $contentGenerator;

    /** @var GeneratorInputFactory @inject */
    public $generatorInputFactory;

    public function renderPublic(string $id, ?string $lang = null): void
    {
        $template = $this->templatesRepository->getByPublicCode($id);
        if (!$template) {
            throw new BadRequestException();
        }

        $mailContent = $this->contentGenerator->render($this->generatorInputFactory->create($template, [], null, $lang));
        $this->template->content = $mailContent->html();
    }
}
