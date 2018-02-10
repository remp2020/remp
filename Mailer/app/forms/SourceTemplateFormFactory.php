<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Remp\MailerModule\Generators\GeneratorFactory;
use Remp\MailerModule\Repository\SourceTemplateRepository;

class SourceTemplateFormFactory
{
    private $mailSourceTemplateRepository;

    private $mailGeneratorFactory;

    public $onUpdate;

    public $onSave;

    public function __construct(
        SourceTemplateRepository $mailSourceTemplateRepository,
        GeneratorFactory $mailGeneratorFactory
    ) {
        $this->mailSourceTemplateRepository = $mailSourceTemplateRepository;

        $this->mailGeneratorFactory = $mailGeneratorFactory;
    }

    public function create($id = null)
    {
        $defaults = [];
        if (isset($id)) {
            $mailTemplate = $this->mailSourceTemplateRepository->find($id);
            $defaults = $mailTemplate->toArray();
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('title', 'title')
            ->setRequired()
            ->setAttribute('placeholder', 'Source template title');

        $items = $this->mailGeneratorFactory->pairs();
        $form->addSelect('generator', 'Generator', $items)
            ->setRequired();

        $form->addTextArea('content_text', 'Text')
            ->setAttribute('rows', 20)
            ->getControlPrototype()
            ->addAttributes(['class' => 'ace', 'data-lang' => 'text']);

        $form->addTextArea('content_html', 'HTML')
            ->setAttribute('rows', 60)
            ->getControlPrototype()
            ->addAttributes(['class' => 'ace', 'data-lang' => 'html']);

        $form->setDefaults($defaults);

        $form->addSubmit('submit', 'system.save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-save"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, $values)
    {
        if (!empty($values['id'])) {
            $id = $values['id'];
            unset($values['id']);

            $row = $this->mailSourceTemplateRepository->find($id);
            $this->mailSourceTemplateRepository->update($row, $values);
            $this->onUpdate->__invoke($form, $row);
        } else {
            $template = $this->mailSourceTemplateRepository->findLast()->fetch();

            $row = $this->mailSourceTemplateRepository->add(
                $values['title'],
                $values['generator'],
                $values['content_html'],
                $values['content_text'],
                $template ? $template->sorting + 100 : 100
            );

            $this->onSave->__invoke($form, $row);
        }
    }
}
