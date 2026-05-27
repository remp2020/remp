<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\BaseControl;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;

class ListVariantFormFactory implements IFormFactory
{
    use SmartObject;

    public $onCreate;

    public $onUpdate;

    public function __construct(
        private readonly ListVariantsRepository $listVariantsRepository,
        private readonly ListsRepository $listsRepository,
    ) {
    }

    public function create(?ActiveRow $variant = null, ?ActiveRow $mailType = null): Form
    {
        $defaults = [];
        if ($variant !== null) {
            if ($mailType && $variant->mail_type_id !== $mailType->id) {
                throw new BadRequestException("Provided mail type [{$mailType->id}] does not match variant mail type id [{$variant->mail_type_id}]");
            }

            $defaults = $variant->toArray();
        }

        $form = new Form;
        $form->addProtection();

        $form->addText('title', 'Title')
            ->setRequired("Field 'Title' is required.");

        $form->addText('code', 'Code')
            ->setRequired("Field 'Code' is required.")
            ->setDisabled($variant !== null)
            ->addRule(Form::Pattern, "Field 'Code' can only contain letters, numbers, underscores, slashes and dots (no whitespace or diacritics).", '[A-Za-z0-9_\/.]+')
            ->addRule(function (BaseControl $input) {
                $exists = $this->listVariantsRepository->getTable()
                    ->where('code = ?', $input->value)
                    ->count('*');
                return !$exists;
            }, "Variant code must be unique. Code '%value' is already used.");

        $form->addText('sorting', 'Sorting')
            ->setHtmlType('number');

        $form->setDefaults($defaults);

        $form->addSubmit(self::FORM_ACTION_SAVE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-check"></i> Save');

        $form->onSuccess[] = function (Form $form, ArrayHash $values) use ($variant, $mailType) {
            if ($variant) {
                $this->listVariantsRepository->update($variant, [
                    'title' => $values['title'],
                    'sorting' => (int) $values['sorting'],
                ]);
                $variant = $this->listVariantsRepository->find($variant->id);
                ($this->onUpdate)($variant);
            } else {
                $sorting = !empty($values['sorting']) ? (int) $values['sorting'] : null;
                $variant = $this->listVariantsRepository->add(
                    mailType: $mailType,
                    title: $values['title'],
                    code: $values['code'],
                    sorting: $sorting,
                );
                ($this->onCreate)($variant);
            }
        };

        return $form;
    }
}
