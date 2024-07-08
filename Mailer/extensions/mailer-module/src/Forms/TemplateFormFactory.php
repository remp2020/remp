<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Database\Explorer;
use Nette\Forms\Controls\SubmitButton;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Api\v1\Handlers\Mailers\MailCreateTemplateHandler;
use Remp\MailerModule\Forms\Rules\FormRules;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesCodeNotUniqueException;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Repositories\TemplateTranslationsRepository;

class TemplateFormFactory implements IFormFactory
{
    use SmartObject;

    private TemplatesRepository $templatesRepository;

    private LayoutsRepository $layoutsRepository;

    private ListsRepository $listsRepository;

    private TemplateTranslationsRepository $templateTranslationsRepository;

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
        GeneratorInputFactory $generatorInputFactory,
        TemplateTranslationsRepository $templateTranslationsRepository
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
        $this->contentGenerator = $contentGenerator;
        $this->database = $database;
        $this->generatorInputFactory = $generatorInputFactory;
        $this->templateTranslationsRepository = $templateTranslationsRepository;
    }

    public function create(?int $id = null, ?string $lang = null): Form
    {
        $count = 0;
        $defaults = [];

        $layouts = $this->layoutsRepository->all()->fetchPairs('id', 'name');
        $lists = $this->listsRepository->all()->fetchPairs('id', 'title');
        $firstList = $this->listsRepository->find(key($lists));

        if (isset($id)) {
            $template = $this->templatesRepository->find($id);
            $count = $template->related('mail_logs', 'mail_template_id')->count('*');
            $defaults = $template->toArray();
            if (isset($lang)) {
                $templateTranslation = $template->related('mail_template_translations', 'mail_template_id')
                    ->where('locale', $lang)
                    ->fetch();

                $defaults['from'] = $templateTranslation->from ?? '';
                $defaults['subject'] = $templateTranslation->subject ?? '';
                $defaults['mail_body_text'] = $templateTranslation->mail_body_text ?? '';
                $defaults['mail_body_html'] = $templateTranslation->mail_body_html ?? '';
            }
        } else {
            $defaults['mail_layout_id'] = key($layouts);
            $defaults['from'] = $firstList->mail_from ?? '';
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);
        $form->addHidden('lang', $lang);

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.")
            ->addRule(
                Form::MaxLength,
                "The name cannot be longer than %d characters.",
                MailCreateTemplateHandler::NAME_MAX_LENGTH
            );

        if (isset($id) && $count > 0) {
            $form->addText('code', 'Code')
                ->setRequired("Field 'Code' is required.")
                ->setDisabled();
        } else {
            $form->addText('code', 'Code')
                ->setRequired("Field 'Code' is required.");
        }

        $form->addText('description', 'Description');

        $select = $form->addSelect('mail_layout_id', 'Layout', $layouts);
        if (!$layouts) {
            $select->addError("No Layouts found, please create some first.");
        }

        $field = $form->addSelect('mail_type_id', 'Newsletter list', $lists)
            ->setRequired("Field 'Newsletter list' is required.");
        if (!$lists) {
            $field->addError("No Newsletter lists found, please create some first.");
        }

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

        $form->addSubmit(self::FORM_ACTION_SAVE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml($id ? '<i class="zmdi zmdi-check"></i> Save' : '<i class="zmdi zmdi-caret-right"></i> Continue');

        $form->addSubmit(self::FORM_ACTION_SAVE_CLOSE)
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

            $lang = $values['lang'] ?? null;
            unset($values['lang']);

            if (!empty($values['id'])) {
                $row = $this->templatesRepository->find($values['id']);
                if ($lang) {
                    $this->templateTranslationsRepository->upsert(
                        $row,
                        $lang,
                        $values['from'],
                        $values['subject'],
                        $values['mail_body_text'],
                        $values['mail_body_html']
                    );

                    unset($values['from'], $values['subject'], $values['mail_body_text'], $values['mail_body_html']);
                }

                $this->templatesRepository->update($row, (array) $values);
                $row = $this->templatesRepository->find($values['id']);
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
            $form->getComponent('code')->addError($e->getMessage());
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
