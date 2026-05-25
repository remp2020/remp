<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\SubscribersImporter;
use Remp\MailerModule\Repositories\ListVariantsRepository;

class VariantSubscribersImportFormFactory
{
    use SmartObject;

    public $onImport;

    public function __construct(
        private readonly ListVariantsRepository $listVariantsRepository,
        private readonly SubscribersImporter $subscribersImporter,
    ) {
    }

    public function create(int $variantId): Form
    {
        $variant = $this->listVariantsRepository->find($variantId);
        if (!$variant || $variant->deleted_at !== null) {
            throw new BadRequestException("List variant not found", IResponse::S404_NotFound);
        }
        if (!$variant->mail_type->is_external) {
            throw new BadRequestException("Cannot import subscribers for non-external mail type", IResponse::S400_BadRequest);
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('variant_id', (string) $variantId);

        $form->addCheckbox('remove_not_present', 'Remove missing email(s) from variant subscribers')
            ->setDefaultValue(true);

        $form->addTextArea('emails', 'Emails')
            ->setRequired("Field 'Emails' is required.")
            ->setHtmlAttribute('placeholder', "For example: user@example.com\nOne email per line.");

        $form->addSubmit('send')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-check"></i> Import');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        $variant = $this->listVariantsRepository->find((int) $values['variant_id']);
        if (!$variant || $variant->deleted_at !== null) {
            $form->addError('List variant not found or has been deleted.');
            return;
        }

        $mailType = $variant->mail_type;
        if (!$mailType->is_external) {
            $form->addError('Mail type is not external.');
            return;
        }

        $emails = explode("\n", $values['emails']);
        $emails = array_map(static fn(string $line) => strtolower(trim($line)), $emails);
        $emails = array_filter($emails, static fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $emails = array_unique($emails);

        if (empty($emails)) {
            $form->addError('No valid emails provided.');
            return;
        }

        $importedCount = $this->subscribersImporter->import(
            $mailType,
            [$variant],
            $emails,
            (bool) $values['remove_not_present'],
        );

        ($this->onImport)($variant, $importedCount);
    }
}
