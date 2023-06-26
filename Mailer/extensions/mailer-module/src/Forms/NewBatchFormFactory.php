<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tracy\Debugger;

class NewBatchFormFactory
{
    use SmartObject;

    private const FORM_ACTION_SAVE_START = 'save_start';

    public $onSuccess;

    public function __construct(
        private JobsRepository $jobsRepository,
        private BatchesRepository $batchesRepository,
        private TemplatesRepository $templatesRepository,
        private BatchTemplatesRepository $batchTemplatesRepository,
        private ListsRepository $listsRepository,
        private Aggregator $segmentAggregator,
        private PermissionManager $permissionManager,
        private User $user,
        private LinkGenerator $linkGenerator,
        private ListVariantsRepository $listVariantsRepository,
    ) {
    }

    public function create(?int $jobId)
    {
        $form = new Form;
        $form->addProtection();

        if ($jobId === null) {
            $segments = [];
            $segmentList = $this->segmentAggregator->list();
            array_walk($segmentList, function ($segment) use (&$segments) {
                $segments[$segment['provider']][$segment['provider'] . '::' . $segment['code']] = $segment['name'];
            });
            if ($this->segmentAggregator->hasErrors()) {
                $form->addError('Unable to fetch list of segments, please check the application configuration.');
                Debugger::log($this->segmentAggregator->getErrors()[0], Debugger::WARNING);
            }

            $form->addMultiSelect('include_segment_codes', 'Include segments', $segments)
                ->setRequired("You have to include at least one segment.");

            $form->addMultiSelect('exclude_segment_codes', 'Exclude segments', $segments);
        }

        $methods = [
            'random' => 'Random',
            'sequential' => 'Sequential',
        ];
        $form->addSelect('method', 'Method', $methods);

        $listPairs = $this->listsRepository->all()->fetchPairs('id', 'title');

        $mailTypeField = $form->addSelect('mail_type_id', 'Newsletter list', $listPairs)
            ->setPrompt('Select newsletter list');

        if (isset($_POST['mail_type_id'])) {
            $variantsList = $this->getMailTypeVariants((int) $_POST['mail_type_id']);
            $templateList = $this->templatesRepository->pairs((int) $_POST['mail_type_id']);
        } else {
            $variantsList = $templateList = null;
        }

        if ($jobId === null) {
            $form->addSelect('mail_type_variant_id', 'List variant', $variantsList)
                ->setPrompt('Select variant')
                ->setDisabled(!$variantsList || count($variantsList) === 0)
                ->setHtmlAttribute('data-depends', $mailTypeField->getHtmlName())
                // %value% will be replaced by selected ID from 'data-depends' input
                ->setHtmlAttribute('data-url', $this->linkGenerator->link('Mailer:Job:MailTypeVariants', ['id'=>'%value%']));
        }

        $templateFieldA = $form->addSelect('template_id', 'Email A alternative', $templateList)
            ->setPrompt('Select email')
            ->setRequired('Email for A alternative is required')
            ->setHtmlAttribute('data-depends', $mailTypeField->getHtmlName())
            // %value% will be replaced by selected ID from 'data-depends' input
            ->setHtmlAttribute('data-url', $this->linkGenerator->link('Mailer:Job:MailTypeTemplates', ['id'=>'%value%']));

        $templateFieldB = $form->addSelect('b_template_id', 'Email B alternative', $templateList)
            ->setPrompt('Select alternative email');

        // Mirror dependent data (mail_templates) to template_b, to avoid duplicate ajax call
        $templateFieldA->setHtmlAttribute('data-mirror-to', $templateFieldB->getHtmlName());

        $form->addText('email_count', 'Number of emails');

        $form->addText('start_at', 'Start date');

        if ($jobId === null) {
            $form->addText('context', 'Context')
                ->setNullable();
        }

        $form->addHidden('job_id', $jobId);

        $form->addSubmit('save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save draft');

        if ($this->permissionManager->isAllowed($this->user, 'batch', 'start')) {
            $form->addSubmit(self::FORM_ACTION_SAVE_START)
                ->getControlPrototype()
                ->setName('button')
                ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save and start sending now');
        }

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    private function getMailTypeVariants($mailTypeId): array
    {
        $mailType = $this->listsRepository->find($mailTypeId);
        if (!$mailType) {
            return [];
        }

        return $this->listVariantsRepository->getVariantsForType($mailType)
            ->order('sorting')
            ->fetchPairs('id', 'title');
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        if ($values['template_id'] === $values['b_template_id']) {
            $form->addError("Email A alternative and Email B Alternative cannot be the same.");
            return;
        }

        if (!$values['job_id']) {
            $jobSegmentsManager = new JobSegmentsManager();
            foreach ($values['include_segment_codes'] as $includeSegment) {
                [$provider, $code] = explode('::', $includeSegment);
                $jobSegmentsManager->includeSegment($code, $provider);
            }
            foreach ($values['exclude_segment_codes'] as $excludeSegment) {
                [$provider, $code] = explode('::', $excludeSegment);
                $jobSegmentsManager->excludeSegment($code, $provider);
            }

            $variant = null;
            if (isset($values['mail_type_variant_id'])) {
                $variant = $this->listVariantsRepository->find($values['mail_type_variant_id']);
            }
            $values['job_id'] = $this->jobsRepository->add($jobSegmentsManager, $values->context, $variant)->id;
        } else {
            $values['job_id'] = (int)$values['job_id'];
        }

        $batch = $this->batchesRepository->add(
            (int) $values['job_id'],
            !empty($values['email_count']) ? (int)$values['email_count'] : null,
            $values['start_at'],
            $values['method']
        );

        $this->batchTemplatesRepository->add(
            (int) $values['job_id'],
            $batch->id,
            $values['template_id']
        );

        if ($values['b_template_id'] !== null) {
            $this->batchTemplatesRepository->add(
                (int) $values['job_id'],
                $batch->id,
                $values['b_template_id']
            );
        }

        if ($this->permissionManager->isAllowed($this->user, 'batch', 'start')) {
            /** @var SubmitButton $buttonSaveStart */
            $buttonSaveStart = $form[self::FORM_ACTION_SAVE_START];
            if ($buttonSaveStart->isSubmittedBy()) {
                $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND);
            }
        }

        ($this->onSuccess)($batch->job);
    }
}
