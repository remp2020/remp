<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\TemplatesRepository;

class TemplateTestFormFactory extends Object
{
    /** @var TemplatesRepository */
    private $templateRepository;

    public $onSuccess;

    public function __construct(TemplatesRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function create($id)
    {
        $form = new Form;
        $form->addProtection();

        $form->addText('email', 'Email')
            ->addRule($form::EMAIL)
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
        $row = $this->templateRepository->find($values['id']);
        //$this->mailer->send($values['email'], $row->code, []);

        ($this->onSuccess)($row);
    }
}
