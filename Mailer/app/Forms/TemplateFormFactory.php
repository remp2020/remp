<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Database\Explorer;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Forms\Rules\FormRules;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesCodeNotUniqueException;
use Remp\MailerModule\Repositories\TemplatesRepository;

class TemplateFormFactory implements IFormFactory
{
    use SmartObject;

    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var LayoutsRepository */
    private $layoutsRepository;

    /** @var ListsRepository */
    private $listsRepository;

    public $onCreate;

    public $onUpdate;

    private $contentGenerator;

    private $database;

    private $generatorInputFactory;

    public function __construct(
        TemplatesRepository $templatesRepository,
        LayoutsRepository $layoutsRepository,
        ListsRepository $listsRepository,
        ContentGenerator $contentGenerator,
        Explorer $database,
        GeneratorInputFactory $generatorInputFactory
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
        $this->contentGenerator = $contentGenerator;
        $this->database = $database;
        $this->generatorInputFactory = $generatorInputFactory;
    }

    public function create(?int $id = null): Form
    {
        $count = 0;
        $defaults = [];

        $layouts = $this->layoutsRepository->all()->fetchPairs('id', 'name');

        if (isset($id)) {
            $template = $this->templatesRepository->find($id);
            $count = $template->related('mail_logs')->count('*');
            $defaults = $template->toArray();
        } else {
            $defaults['mail_layout_id'] = key($layouts);
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.");

        if (isset($id) && $count > 0) {
            $form->addText('code', 'Code')
                ->setRequired("Field 'Code' is required.")
                ->setDisabled();
        } else {
            $form->addText('code', 'Code')
                ->setRequired("Field 'Code' is required.");
        }

        $form->addText('description', 'Description');

        $form->addSelect('mail_layout_id', 'Layout', $layouts);

        $form->addSelect('mail_type_id', 'Newsletter list', $this->listsRepository->all()->fetchPairs('id', 'title'))
            ->setRequired("Field 'Newsletter list' is required.");

        $form->addText('from', 'From')
            ->addRule(FormRules::ADVANCED_EMAIL, 'Enter correct email')
            ->setRequired("Field 'From' is required.");

        $form->addText('subject', 'Subject')
            ->setRequired("Field 'Subject' is required.");

        $form->addSelect('click_tracking', 'Click tracking', [
            null => 'Default mailer settings (depends on your provider configuration)',
            1 => "Enabled",
            0 => "Disabled",
        ]);

        $form->addTextArea('mail_body_text', 'Text version')
            ->setHtmlAttribute('rows', 3);

        $form->addTextArea('mail_body_html', 'HTML version');

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

        /** @var SubmitButton $buttonSaveClose */
        $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];
        if ($buttonSaveClose->isSubmittedBy()) {
            $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
        }

        if ($values['click_tracking'] === "") {
            // handle null (default) selection
            $values['click_tracking'] = null;
        } else {
            $values['click_tracking'] = (bool) $values['click_tracking'];
        }

        $this->database->beginTransaction();

        try {
            $row = null;
            $callback = null;
            if (!empty($values['id'])) {
                $row = $this->templatesRepository->find($values['id']);
                $this->templatesRepository->update($row, (array) $values);
                $callback = $this->onUpdate;
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
                    $values['mail_type_id'],
                    $values['click_tracking']
                );
                $callback = $this->onCreate;
            }

            $this->contentGenerator->render($this->generatorInputFactory->create($row));
        } catch (TemplatesCodeNotUniqueException $e) {
            $this->database->rollback();
            $form['code']->addError($e->getMessage());
            return;
        } catch (\Exception $exception) {
            $this->database->rollback();
            $form->addError($exception->getMessage());
            return;
        }

        $this->database->commit();
        ($callback)($row, $buttonSubmitted);
    }
}
