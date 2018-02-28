<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Forms\Rules\FormRules;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class TemplateFormFactory extends Object
{
    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var LayoutsRepository */
    private $layoutsRepository;

    /** @var ListsRepository */
    private $listsRepository;

    public $onCreate;

    public $onUpdate;

    public function __construct(
        TemplatesRepository $templatesRepository,
        LayoutsRepository $layoutsRepository,
        ListsRepository $listsRepository
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
    }

    public function create($id)
    {
        $count = 0;
        $defaults = [];

        if (isset($id)) {
            $template = $this->templatesRepository->find($id);
            $count = $template->related('mail_logs')->count('*');
            $defaults = $template->toArray();
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('name', 'Name')
            ->setRequired('Name is required');

        if (isset($id) && $count > 0) {
            $form->addText('code', 'Code')
                ->setRequired('Code is required')
                ->setDisabled();
        } else {
            $form->addText('code', 'Code')
                ->setRequired('Code is required');
        }

        $form->addText('description', 'Description');

        $form->addSelect('mail_layout_id', 'Layout', $this->layoutsRepository->all()->fetchPairs('id', 'name'));

        $form->addSelect('mail_type_id', 'Newsletter list', $this->listsRepository->all()->fetchPairs('id', 'title'))
            ->setRequired('Newsletter list is required');

        $form->addText('from', 'From')
            ->addRule(FormRules::ADVANCED_EMAIL, 'Enter correct email')
            ->setRequired('From field is required');

        $form->addText('subject', 'Subject')
            ->setRequired('Subject is required');

        $form->addTextArea('mail_body_text', 'Text version')
            ->setAttribute('rows', 3);

        $form->addTextArea('mail_body_html', 'HTML version');

        $form->setDefaults($defaults);

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        if (!empty($values['id'])) {
            $row = $this->templatesRepository->find($values['id']);
            $this->templatesRepository->update($row, $values);
            ($this->onUpdate)($row);
        } else {
            $row = $this->templatesRepository->add(
                $values['name'],
                $values['code'],
                $values['description'],
                $values['from'],
                $values['subject'],
                $values['mail_body_text'],
                $values['mail_body_html'],
                $values['mail_layout_id'],
                $values['mail_type_id']
            );
            ($this->onCreate)($row);
        }
    }
}
