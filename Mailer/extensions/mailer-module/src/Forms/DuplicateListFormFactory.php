<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Exception;
use InvalidArgumentException;
use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;

class DuplicateListFormFactory
{
    use SmartObject;

    public $onCreate;

    public function __construct(
        private readonly ListsRepository $listsRepository,
        private readonly ListVariantsRepository $listVariantsRepository,
    ) {
    }

    public function create(int $id): Form
    {
        $form = new Form;
        $form->addProtection();

        $sourceList = $this->listsRepository->find($id);
        if (!$sourceList) {
            throw new InvalidArgumentException('Source list does not exist');
        }
        $variantsCount = $this->listVariantsRepository->getVariantsForType($sourceList)->count('*');
        if ($variantsCount !== 0) {
            throw new InvalidArgumentException('Source list has variants. Copying is not allowed.');
        }

        $defaults = $sourceList->toArray();

        $form->addText('code', 'Code')
            ->setRequired("Field 'Code' is required.")
            ->addRule(function ($input) {
                $exists = $this->listsRepository->getTable()
                    ->where('code = ?', $input->value)
                    ->count('*');
                return !$exists;
            }, "Newsletter list code must be unique. Code '%value' is already used.");

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addCheckbox('copy_subscribers', 'Copy subscribers')
            ->setDefaultValue(true);

        $form->addCheckbox('auto_subscribe', 'Auto subscribe')
            ->setDefaultValue(false);

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
     * @throws Exception
     */
    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        if (!isset($values['id'])) {
            throw new InvalidArgumentException('Missing "id" in values');
        }
        $sourceList = $this->listsRepository->find($values['id']);
        if (!$sourceList) {
            throw new InvalidArgumentException('Source list does not exist');
        }

        $this->listsRepository->updateSorting(
            $sourceList->mail_type_category_id,
            $sourceList->sorting + 1
        );

        $newList = $this->listsRepository->add(
            $sourceList->mail_type_category_id,
            $sourceList->priority,
            $values['code'],
            $values['title'],
            $sourceList->sorting + 1,
            (bool)$values['auto_subscribe'],
            (bool)$sourceList->locked,
            $sourceList->description,
            $sourceList->preview_url,
            $sourceList->page_url,
            $sourceList->image_url,
            (bool)$sourceList->public_listing,
            $sourceList->mail_from,
            $sourceList->subscribe_mail_template_id,
            $sourceList->unsubscribe_mail_template_id
        );

        ($this->onCreate)($newList, $sourceList, $values['copy_subscribers'] ?? false);
    }
}
