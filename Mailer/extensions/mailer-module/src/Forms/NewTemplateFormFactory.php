<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class NewTemplateFormFactory
{
    use SmartObject;

    private $templatesRepository;

    private $batchesRepository;

    private $batchTemplatesRepository;

    private $listsRepository;

    public $onSuccess;

    public function __construct(
        TemplatesRepository $templatesRepository,
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        ListsRepository $listsRepository
    ) {
        $this->templatesRepository = $templatesRepository;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->listsRepository = $listsRepository;
    }

    public function create($batchId)
    {
        $form = new Form;
        $form->addProtection();

        $batchTemplates = $this->batchTemplatesRepository->findByBatchId((int) $batchId);
        $mailTypeId = $batchTemplates->fetch()->mail_template->mail_type_id;

        $templateList = $this->templatesRepository->filteredPairs(
            listId: $mailTypeId,
            filterTemplateIds: array_values($batchTemplates->fetchPairs('id', 'mail_template_id')),
            limit: 10000, // this limit is arbitrary, but things could get ugly without it
        );

        $form->addSelect('template_id', 'Email', $templateList);
        $form->addHidden('batch_id', $batchId);

        $form->addSubmit('save')
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
