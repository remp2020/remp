<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Remp\MailerModule\Repositories\ListCategoriesRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class ListFormFactory
{
    use SmartObject;

    private ListsRepository $listsRepository;

    private ListCategoriesRepository $listCategoriesRepository;

    private TemplatesRepository $templatesRepository;

    private MailerFactory $mailerFactory;

    public $onCreate;
    public $onUpdate;

    public function __construct(
        ListsRepository $listsRepository,
        ListCategoriesRepository $listCategoriesRepository,
        MailerFactory $mailerFactory,
        TemplatesRepository $templatesRepository
    ) {
        $this->listsRepository = $listsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
        $this->mailerFactory = $mailerFactory;
        $this->templatesRepository = $templatesRepository;
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

        $systemEmails = $this->templatesRepository
            ->getByMailTypeCategoryCode('system')
            ->select('mail_templates.id, mail_templates.name')
            ->fetchPairs('id', 'name');

        $form->addSelect(
            'subscribe_mail_template_id',
            'Subscription welcome email',
            $systemEmails
        )->setPrompt('None');

        $form->addText('priority', 'Priority')
            ->addRule(Form::INTEGER, "Priority needs to be a number")
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

        $form->addText('preview_url', 'Preview URL');

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

        $form->addRadioList('sorting', 'Order', $orderOptions)->setRequired("Field 'Order' is required.");

        if ($list !== null) {
            $keys = array_keys($sortingPairs);
            if (reset($keys) === $list->sorting) {
                $defaults['sorting'] = 'begin';
                unset($defaults['sorting_after']);
            } elseif (end($keys) === $list->sorting) {
                $defaults['sorting'] = 'end';
                unset($defaults['sorting_after']);
            } else {
                $defaults['sorting'] = 'after';
                foreach ($sortingPairs as $sorting => $_) {
                    if ($list->sorting <= $sorting) {
                        break;
                    }
                    $defaults['sorting_after'] = $sorting;
                }
            }

            unset($sortingPairs[$list->sorting]);
        }

        $form->addSelect('sorting_after', null, $sortingPairs)
                ->setPrompt('Choose newsletter list');

        $form->addCheckbox('auto_subscribe', 'Auto subscribe');
        $form->addCheckbox('locked', 'Locked');
        $form->addCheckbox('public_listing', 'List publicly');

        $form->addHidden('id', $id);

        $form->setDefaults($defaults);

        $form->addSubmit('save', 'Save')
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

        switch ($values['sorting']) {
            case 'begin':
                $first = reset($listsInCategory);
                $values['sorting'] = $first ? $first->sorting - 1 : 1;
                break;

            case 'after':
                // fix missing form value because of dynamically loading select options
                // in ListPresenter->handleRenderSorting
                if ($values['sorting_after'] === null) {
                    $formHttpData = $form->getHttpData();

                    // + add validation
                    if (empty($formHttpData['sorting_after'])) {
                        $form->addError("Field 'Order' is required.");
                        return;
                    }
                    $values['sorting_after'] = $formHttpData['sorting_after'];
                }

                $values['sorting'] = $values['sorting_after'];

                if (!$list ||
                    $values['mail_type_category_id'] != $list->mail_type_category_id ||
                    ($list && $list->sorting > $values['sorting_after'])
                ) {
                    $values['sorting'] += 1;
                }
                break;
            default:
            case 'end':
                $last = end($listsInCategory);
                $values['sorting'] = $last ? $last->sorting + 1 : 1;
                break;
        }

        $this->listsRepository->updateSorting(
            $values['mail_type_category_id'],
            $values['sorting'],
            $list->mail_type_category_id ?? null,
            $list->sorting ?? null
        );

        unset($values['sorting_after']);

        if ($list) {
            $this->listsRepository->update($list, (array) $values);
            $list = $this->listsRepository->find($list->id);
            ($this->onUpdate)($list);
        } else {
            $row = $this->listsRepository->add(
                $values['mail_type_category_id'],
                $values['priority'],
                $values['code'],
                $values['title'],
                $values['sorting'],
                $values['auto_subscribe'],
                $values['locked'],
                $values['description'],
                $values['preview_url'],
                $values['page_url'],
                $values['image_url'],
                $values['public_listing'],
                $values['mail_from'],
                $values['subscribe_mail_template_id']
            );
            ($this->onCreate)($row);
        }
    }
}
