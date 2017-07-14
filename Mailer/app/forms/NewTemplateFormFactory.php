<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\SegmentsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class NewTemplateFormFactory extends Object
{
    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var BatchesRepository */
    private $batchesRepository;

    /** @var BatchTemplatesRepository */
    private $batchTemplatesRepository;

    public $onSuccess;

    public function __construct(
        TemplatesRepository $templatesRepository,
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
    }

    public function create($batchId)
    {
        $form = new Form;
        $form->addProtection();

        $form->addSelect('template_id', 'Email', $this->templatesRepository->all()->fetchPairs('id', 'name'));
        $form->addHidden('batch_id', $batchId);

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $batch = $this->batchesRepository->find((int)$values['batch_id']);
        $job = $batch->ref('job');

        $batchTemplate = $this->batchTemplatesRepository->add(
            $job->id,
            $batch->id,
            $values['template_id']
        );

        ($this->onSuccess)($job);
    }
}
