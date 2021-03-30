<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

final class PreviewPresenter extends Presenter
{
    /** @var TemplatesRepository @inject */
    public $templatesRepository;

    /** @var EngineFactory @inject */
    public $engineFactory;

    /** @var ContentGenerator @inject */
    public $contentGenerator;

    /** @var SnippetsRepository @inject */
    public $snippetsRepository;

    public function renderPreview($code): void
    {
        $template = $this->templatesRepository->getByCode($code);
        if (!$template) {
            throw new BadRequestException();
        }
        if (!$template->mail_type->is_public) {
            throw new BadRequestException();
        }

        $snippets = $this->snippetsRepository
            ->getSnippetsForMailType($this->template->mail_type_id)->fetchPairs('code', 'html');

        $mailContent = $this->contentGenerator->render(new GeneratorInput($template, ['snippets' => $snippets]));
        $this->template->content = $mailContent->html();
    }
}
