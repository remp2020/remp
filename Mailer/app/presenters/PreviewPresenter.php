<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Repository\TemplatesRepository;

final class PreviewPresenter extends Presenter
{
    /** @var TemplatesRepository @inject */
    public $templatesRepository;

    public function renderPreview($code)
    {
        $template = $this->templatesRepository->getByCode($code);
        if (!$template) {
            throw new BadRequestException();
        }
        if (!$template->mail_type->is_public) {
            throw new BadRequestException();
        }

        $params = [];
        $mailContentGenerator = new ContentGenerator($template, $template->layout);
        $this->template->content = $mailContentGenerator->getHtmlBody($params);
    }
}
