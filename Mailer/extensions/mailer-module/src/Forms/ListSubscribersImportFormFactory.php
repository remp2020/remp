<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\SubscribersImporter;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;

class ListSubscribersImportFormFactory
{
    use SmartObject;

    public $onImport;

    public function __construct(
        private readonly ListsRepository $listsRepository,
        private readonly ListVariantsRepository $listVariantsRepository,
        private readonly SubscribersImporter $subscribersImporter,
    ) {
    }

    public function create(int $mailTypeId): Form
    {
        $mailType = $this->listsRepository->find($mailTypeId);
        if (!$mailType) {
            throw new BadRequestException("Mail type not found", IResponse::S404_NotFound);
        }
        if (!$mailType->is_external) {
            throw new BadRequestException("Cannot import subscribers for non-external mail type", IResponse::S400_BadRequest);
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('mail_type_id', (string) $mailTypeId);

        $variants = $this->listVariantsRepository->getVariantsForType($mailType)
            ->fetchPairs('id', 'title');
        if (count($variants) > 0) {
            $isMultiVariant = (bool) $mailType->is_multi_variant;

            $enableForceNoVariant = true;

            if ($mailType->default_variant_id && isset($variants[$mailType->default_variant_id])) {
                $description = "Leave empty to subscribe to \"{$variants[$mailType->default_variant_id]}\" (default variant).";
            } elseif ($isMultiVariant) {
                $description = 'Leave empty to subscribe to all variants. (unless "Force no variants subscription" is enabled)';
            } else {
                $description = '';
                $enableForceNoVariant = false;
            }

            if ($isMultiVariant) {
                $form->addMultiSelect('variant_ids', 'Variants to subscribe to', $variants)
                    ->setOption('description', $description)
                    ->setOption('multiple', $isMultiVariant);
            } else {
                $form->addSelect('variant_ids', 'Variant to subscribe to', $variants)
                    ->setPrompt('Select variant')
                    ->setOption('description', $description);
            }

            if ($enableForceNoVariant) {
                $form->addCheckbox('force_no_variant_subscription', 'Force no variant subscription')
                    ->addCondition($form::Equal, true)
                    ->toggle('#variant-ids-wrapper', false);
            }
        }

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
        $mailType = $this->listsRepository->find((int) $values['mail_type_id']);
        if (!$mailType) {
            $form->addError('Mail type not found.');
            return;
        }
        if (!$mailType->is_external) {
            $form->addError('Mail type is not external.');
            return;
        }

        $forceNoVariant = (bool) ($values['force_no_variant_subscription'] ?? false);

        $variants = null;
        if (!$forceNoVariant && !empty($values['variant_ids'])) {
            $variants = $this->listVariantsRepository->getVariantsForType($mailType)
                ->where('id', (array) $values['variant_ids'])
                ->fetchAll();
        }

        $emails = explode("\n", $values['emails']);
        $emails = array_map(static fn(string $line) => strtolower(trim($line)), $emails);
        $emails = array_filter($emails, static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $emails = array_unique($emails);

        if (empty($emails)) {
            $form->addError('No valid emails provided.');
            return;
        }

        $importedCount = $this->subscribersImporter->import(
            $mailType,
            $variants,
            $emails,
            false,
            $forceNoVariant,
        );

        ($this->onImport)($mailType, $importedCount);
    }
}
