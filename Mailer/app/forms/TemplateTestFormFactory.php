<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Sender;

class TemplateTestFormFactory extends Object
{
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

    public function create($id)
    {
        $form = new Form;
        $form->addProtection();

        $form->addText('email', 'Email')
            ->addRule(Form::EMAIL)
            ->setRequired('Required');

        $form->addSubmit('save', 'Send')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Send');

        $template = $this->templateRepository->find($id);
        $form->addHidden('id', $template->id);

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $template = $this->templateRepository->find($values['id']);
        $this->sender->setTemplate($template)
            ->setRecipient($values['email'])
            ->send();

        ($this->onSuccess)($template);
    }
}
