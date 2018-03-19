<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\SubmitButton;
use Nette\Object;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class EditBatchFormFactory extends Object implements IFormFactory
{
    /** @var JobsRepository */
    private $jobsRepository;

    /** @var BatchesRepository */
    private $batchesRepository;

    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var BatchTemplatesRepository */
    private $batchTemplatesRepository;

    public $onSuccess;

    public $onError;

    public function __construct(
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        TemplatesRepository $templatesRepository,
        BatchTemplatesRepository $batchTemplatesRepository
    ) {
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->templatesRepository = $templatesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
    }

    public function create(ActiveRow $batch)
    {
        $form = new Form;
        $form->addProtection();

        $methods = [
            'random' => 'Random',
            'sequential' => 'Sequential',
        ];
        $form->addSelect('method', 'Method', $methods);

        $form->addText('max_emails', 'Number of emails')->setHtmlType('number');

        $form->addText('start_at', 'Start date');

        $form->addHidden('id', $batch->id);

        $form->addSubmit(self::FORM_ACTION_SAVE, self::FORM_ACTION_SAVE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-check"></i> Save');

        $form->addSubmit(self::FORM_ACTION_SAVE_CLOSE, self::FORM_ACTION_SAVE_CLOSE)
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save and close');

        $form->setDefaults([
            'method' => $batch->method,
            'max_emails' => $batch->max_emails,
            'start_at' => $batch->start_at,
            'id' => $batch->id,
        ]);

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $batch = $this->batchesRepository->find($values['id']);

        if (!in_array($batch->status, [BatchesRepository::STATE_CREATED, BatchesRepository::STATE_UPDATED, BatchesRepository::STATE_READY])) {
            $form->addError("Unable to edit batch, already in non-editable status: {$batch->status}");
            return;
        }

        $this->batchesRepository->update($batch, array_filter([
            'method' => $values['method'],
            'max_emails' => $values['max_emails'],
            'start_at' => new \DateTime($values['start_at']),
        ]));

        // decide if user wants to save or save and leave
        $buttonSubmitted = self::FORM_ACTION_SAVE;
        /** @var $buttonSaveClose SubmitButton */
        $buttonSaveClose = $form[self::FORM_ACTION_SAVE_CLOSE];
        if ($buttonSaveClose->isSubmittedBy()) {
            $buttonSubmitted = self::FORM_ACTION_SAVE_CLOSE;
        }

        ($this->onSuccess)($batch, $buttonSubmitted);
    }
}
