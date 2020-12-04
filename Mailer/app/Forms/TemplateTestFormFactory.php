<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Models\Sender;

class TemplateTestFormFactory
{
    use SmartObject;

    /** @var TemplatesRepository */
    private $templateRepository;

    /** @var Sender */
    private $sender;

    public $onSuccess;

    public function __construct(TemplatesRepository $templateRepository, Sender $sender)
    {
        $this->templateRepository = $templateRepository;
        $this->sender = $sender;
    }

    public function create(int $id): Form
    {
        $form = new Form;
        $form->addProtection();

        $form->addText('email', 'Email')
            ->addRule(Form::EMAIL)
            ->setRequired("Field 'Email' is required.");

        $form->addSubmit('save', 'Send')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Send');

        $template = $this->templateRepository->find($id);
        $form->addHidden('id', $template->id);

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $template = $this->templateRepository->find($values['id']);
        $this->sender->setTemplate($template)
            ->addRecipient($values['email'])
            ->send(false);

        ($this->onSuccess)($template);
    }
}
