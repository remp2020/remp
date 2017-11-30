<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Object;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class EditBatchFormFactory extends Object
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

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

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
        $this->batchesRepository->update($batch, array_filter([
            'method' => $values['method'],
            'max_emails' => $values['max_emails'],
            'start_at' => new \DateTime($values['start_at']),
        ]));

        ($this->onSuccess)($batch);
    }
}
