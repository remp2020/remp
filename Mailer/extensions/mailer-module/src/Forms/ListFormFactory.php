<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SelectBox;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Remp\MailerModule\Repositories\ListCategoriesRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class ListFormFactory
{
    use SmartObject;

    private const SORTING = 'sorting';
    private const SORTING_AFTER = 'sorting_after';
    private const MAIL_TYPE_CATEGORY = 'mail_type_category_id';
    private const LIST_ID = 'id';

    public $onCreate;
    public $onUpdate;

    public function __construct(
        private readonly ListsRepository $listsRepository,
        private readonly ListCategoriesRepository $listCategoriesRepository,
        private readonly MailerFactory $mailerFactory,
        private readonly MailTypesRepository $mailTypesRepository,
        private readonly TemplatesRepository $templatesRepository,
        private readonly LinkGenerator $linkGenerator
    ) {
    }

    public function create(?int $id = null): Form
    {
        $list = null;
        $defaults = [];

        $form = new Form;
        $form->addProtection();

        if ($id !== null) {
            $list = $this->listsRepository->find($id);
            $defaults = $list->toArray();

            $templates = $this->templatesRepository
                ->findByList($id, 500)
                ->select('public_code, name')
                ->order('created_at DESC')
                ->fetchAll();

            $templatePairs = [null => "Select email to prefill email's public preview URL or enter the URL directly."];

            foreach ($templates as $template) {
                $previewUrl = $this->linkGenerator->link('Mailer:Preview:Public', [$template['public_code']]);
                $templatePairs[$previewUrl] = $template['name'];
            }

            $form->addSelect('preview_template', 'Preview email', $templatePairs);
        }

        $categoryPairs = $this->listCategoriesRepository->all()->fetchPairs('id', 'title');
        if (!isset($defaults['mail_type_category_id'])) {
            $defaults['mail_type_category_id'] = key($categoryPairs);
        }

        $form->addSelect(
            'mail_type_category_id',
            'Category',
            $categoryPairs
        )->setRequired("Field 'Category' is required.");

        $subscriptionEligibleTemplates = $this->getSubscriptionEligibleTemplates($list);
        $form->addSelect(
            'subscribe_mail_template_id',
            'Subscription welcome email',
            $subscriptionEligibleTemplates
        )->setPrompt('None');

        $form->addSelect(
            'unsubscribe_mail_template_id',
            'Unsubscribe goodbye email',
            $subscriptionEligibleTemplates
        )->setPrompt('None');

        $form->addText('priority', 'Priority')
            ->addRule(Form::INTEGER, "Priority needs to be a number")
            ->addRule(Form::MIN, "Priority needs to be greater than 0", 1)
            ->setRequired("Field 'Priority' is required.");

        $codeInput = $form->addText('code', 'Code')
            ->setRequired("Field 'Code' is required.")
            ->addRule(function ($input) {
                $exists = $this->listsRepository->getTable()
                    ->where('code = ?', $input->value)
                    ->count('*');
                return !$exists;
            }, "Newsletter list code must be unique. Code '%value' is already used.");

        $mailers = [];
        $availableMailers =  $this->mailerFactory->getAvailableMailers();
        array_walk($availableMailers, function ($mailer, $name) use (&$mailers) {
            $mailers[$name] = $mailer->getIdentifier();
        });

        $form->addSelect('mailer_alias', 'Sending mailer', $mailers)
            ->setPrompt('Using default mailer set in configuration');

        if ($list !== null) {
            $codeInput->setDisabled(true);
        }

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addTextArea('description', 'Description')
            ->setHtmlAttribute('rows', 3);

        $form->addText('mail_from', 'From');

        $form->addText('preview_url', 'Preview URL')->setNullable();

        $form->addText('page_url', 'Page URL');

        $form->addText('image_url', 'Image URL');

        $orderOptions = [
            'begin' => 'At the beginning',
            'end' => 'At the end',
        ];
        $sortingPairs = $this->listsRepository
            ->findByCategory($defaults['mail_type_category_id'])
            ->order('sorting ASC')
            ->fetchPairs('sorting', 'title');

        if (count($sortingPairs) > 0) {
            $orderOptions['after'] = 'After';
        }

        $form->addRadioList(self::SORTING, 'Order', $orderOptions)->setRequired("Field 'Order' is required.");

        if ($list !== null) {
            $keys = array_keys($sortingPairs);
            if (reset($keys) === $list->sorting) {
                $defaults[self::SORTING] = 'begin';
                unset($defaults[self::SORTING_AFTER]);
            } elseif (end($keys) === $list->sorting) {
                $defaults[self::SORTING] = 'end';
                unset($defaults[self::SORTING_AFTER]);
            } else {
                $defaults[self::SORTING] = 'after';
                foreach ($sortingPairs as $sorting => $_) {
                    if ($list->sorting <= $sorting) {
                        break;
                    }
                    $defaults[self::SORTING_AFTER] = $sorting;
                }
            }

            unset($sortingPairs[$list->sorting]);
        }

        $form->addSelect(self::SORTING_AFTER, null, $sortingPairs)
                ->setPrompt('Choose newsletter list');

        $form->addCheckbox('auto_subscribe', 'Auto subscribe');
        $form->addCheckbox('locked', 'Locked');
        $form->addCheckbox('public_listing', 'List publicly');

        $form->addHidden('id', $id);

        $form->setDefaults($defaults);

        $form->addSubmit('save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    /**
     * @param Form $form
     * @param ArrayHash $values
     * @throws \Exception
     */
    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $list = null;
        if (isset($values['id'])) {
            $list = $this->listsRepository->find($values['id']);
        }

        $listsInCategory = $this->listsRepository
            ->findByCategory($values['mail_type_category_id'])
            ->order('mail_types.sorting')
            ->fetchAll();

        switch ($values[self::SORTING]) {
            case 'begin':
                $first = reset($listsInCategory);
                $values[self::SORTING] = $first ? $first->sorting - 1 : 1;
                break;

            case 'after':
                // fix missing form value because of dynamically loading select options
                // in ListPresenter->handleRenderSorting
                if ($values[self::SORTING_AFTER] === null) {
                    $formHttpData = $form->getHttpData();

                    // + add validation
                    if (empty($formHttpData[self::SORTING_AFTER])) {
                        $form->addError("Field 'Order' is required.");
                        return;
                    }
                    $values[self::SORTING_AFTER] = $formHttpData[self::SORTING_AFTER];
                }

                $values[self::SORTING] = $values[self::SORTING_AFTER];

                if (!$list ||
                    $values[self::MAIL_TYPE_CATEGORY] != $list->mail_type_category_id ||
                    ($list && $list->sorting > $values[self::SORTING_AFTER])
                ) {
                    $values[self::SORTING] += 1;
                }
                break;
            default:
            case 'end':
                $last = end($listsInCategory);
                $values[self::SORTING] = $last ? $last->sorting + 1 : 1;
                break;
        }

        $this->listsRepository->updateSorting(
            $values[self::MAIL_TYPE_CATEGORY],
            $values[self::SORTING],
            $list->mail_type_category_id ?? null,
            $list->sorting ?? null
        );

        unset($values[self::SORTING_AFTER], $values['preview_template']);

        if ($list) {
            $this->listsRepository->update($list, (array) $values);
            $list = $this->listsRepository->find($list->id);
            ($this->onUpdate)($list);
        } else {
            $row = $this->listsRepository->add(
                $values[self::MAIL_TYPE_CATEGORY],
                $values['priority'],
                $values['code'],
                $values['title'],
                $values[self::SORTING],
                $values['auto_subscribe'],
                $values['locked'],
                $values['description'],
                $values['preview_url'],
                $values['page_url'],
                $values['image_url'],
                $values['public_listing'],
                $values['mail_from'],
                $values['subscribe_mail_template_id'],
                $values['unsubscribe_mail_template_id'],
            );
            ($this->onCreate)($row);
        }
    }

    public function getSortingControl(Form $form): BaseControl
    {
        return $form[self::SORTING];
    }

    public function getMailTypeCategoryIdControl(Form $form): BaseControl
    {
        return $form[self::MAIL_TYPE_CATEGORY];
    }

    public function getListIdControl(Form $form): BaseControl
    {
        return $form[self::LIST_ID];
    }

    public function getSortingAfterControl(Form $form): SelectBox
    {
        /** @var SelectBox $sortingAfter */
        $sortingAfter = $form[self::SORTING_AFTER];
        return $sortingAfter;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getSubscriptionEligibleTemplates(?ActiveRow $list): array
    {
        $items = [];

        // System mails
        $systemList = $this->mailTypesRepository->findBy('code', 'system');
        if ($systemList !== null) {
            $systemEmails = $this->templatesRepository
                ->getByMailTypeIds([$systemList->id])
                ->select('mail_templates.id, mail_templates.name')
                ->fetchPairs('id', 'name');

            $items[$systemList->title] = $systemEmails;
        }

        if ($list !== null && $list->id !== $systemList->id) {
            $newsletterEmails = $this->templatesRepository
                ->findByList($list->id)
                ->select('mail_templates.id, mail_templates.name')
                ->order('mail_templates.id DESC')
                ->limit(1000)
                ->fetchPairs('id', 'name');

            if (count($newsletterEmails) > 0) {
                $items[$list->title] = $newsletterEmails;
            }
        }

        return $items;
    }
}
