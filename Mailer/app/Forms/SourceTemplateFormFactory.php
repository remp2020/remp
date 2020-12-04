<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\Generators\GeneratorFactory;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;

class SourceTemplateFormFactory implements IFormFactory
{
    private $mailSourceTemplateRepository;

    private $mailGeneratorFactory;

    public $onUpdate;

    public $onSave;

    public function __construct(
        SourceTemplatesRepository $mailSourceTemplateRepository,
        GeneratorFactory $mailGeneratorFactory
    ) {
        $this->mailSourceTemplateRepository = $mailSourceTemplateRepository;
        $this->mailGeneratorFactory = $mailGeneratorFactory;
    }

    public function create(?int $id = null): Form
    {
        $defaults = [];
        if ($id !== null) {
            $mailTemplate = $this->mailSourceTemplateRepository->find($id);
            $defaults = $mailTemplate->toArray();
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addText('code', 'Code')
            ->setRequired("Field 'Code' is required.");

        $items = $this->mailGeneratorFactory->pairs();
        $form->addSelect('generator', 'Generator', $items)
            ->setRequired("Field 'Generator' is required.");

        $form->addTextArea('content_text', 'Text')
            ->setAttribute('rows', 20)
            ->getControlPrototype()
            ->addAttributes(['class' => 'ace', 'data-lang' => 'text']);

        $form->addTextArea('content_html', 'HTML')
            ->setAttribute('rows', 60)
            ->getControlPrototype()
            ->addAttributes(['class' => 'ace', 'data-lang' => 'html']);

        $form->setDefaults($defaults);

        $form->addSubmit(self::FORM_ACTION_SAVE, self::FORM_ACTION_SAVE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-check"></i> Save');

        $form->addSubmit(self::FORM_ACTION_SAVE_CLOSE, self::FORM_ACTION_SAVE_CLOSE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save and close');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        // decide if user wants to save or save and leave
        $buttonSubmitted = self::FORM_ACTION_SAVE;
        /** @var $buttonSaveClose SubmitButton */
        $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];
        if ($buttonSaveClose->isSubmittedBy()) {
            $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
        }

        if (!empty($values['id'])) {
            $id = $values['id'];
            unset($values['id']);

            $row = $this->mailSourceTemplateRepository->find($id);
            $this->mailSourceTemplateRepository->update($row, (array) $values);
            $this->onUpdate->__invoke($form, $row, $buttonSubmitted);
        } else {
            $template = $this->mailSourceTemplateRepository->findLast()->fetch();

            $row = $this->mailSourceTemplateRepository->add(
                $values['title'],
                $values['code'],
                $values['generator'],
                $values['content_html'],
                $values['content_text'],
                $template ? $template->sorting + 100 : 100
            );

            $this->onSave->__invoke($form, $row, $buttonSubmitted);
        }
    }
}
