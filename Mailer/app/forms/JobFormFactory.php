<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Nette\Utils\Json;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Segment\Aggregator;
use Remp\MailerModule\Segment\SegmentException;

class JobFormFactory extends Object
{
    private $jobsRepository;

    private $templatesRepository;

    private $listsRepository;

    private $batchesRepository;

    private $batchTemplatesRepository;

    private $segmentAggregator;

    public $onSuccess;

    public function __construct(
        JobsRepository $jobsRepository,
        TemplatesRepository $templatesRepository,
        ListsRepository $listsRepository,
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        Aggregator $segmentAggregator
    ) {
        $this->jobsRepository = $jobsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->listsRepository = $listsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->segmentAggregator = $segmentAggregator;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        $segments = [];
        try {
            $segmentList = $this->segmentAggregator->list();
            array_walk($segmentList, function ($segment) use (&$segments) {
                $segments[$segment['provider']][$segment['provider'] . '::' . $segment['code']] = $segment['name'];
            });
        } catch (SegmentException $e) {
            $form->addError('Unable to fetch list of segments, please check the application configuration.');
        }

        $listPairs = $this->listsRepository->all()->fetchPairs('id', 'title');
        $templatePairs = $this->templatesRepository->all()->fetchPairs('id', 'name');

        $form->addSelect('segment_code', 'Segment', $segments)
            ->setPrompt('Select segment')
            ->setRequired('Segment is required');

        $form->addSelect('mail_type_id', 'Email A alternative', $listPairs)
            ->setPrompt('Select newsletter list');

        $form->addSelect('template_id', null, $templatePairs)
            ->setPrompt('Select email')
            ->setRequired('Email for A alternative is required');

        $form->addSelect('b_mail_type_id', 'Email B alternative (optional, can be added later)', $listPairs)
            ->setPrompt('Select newsletter list');

        $form->addSelect('b_template_id', null, $templatePairs)
            ->setPrompt('Select alternative email');

        $form->addText('email_count', 'Max number of sent emails');

        $form->addText('start_at', 'Start date');

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $templatePairs = [];
        foreach ($this->templatesRepository->all() as $template) {
            $templatePairs[$template->mail_type_id][] = [
                'value' => $template->id,
                'label' => $template->name,
            ];
        }
        $form->addHidden('template_pairs', Json::encode($templatePairs))->setHtmlId('template_pairs');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $segment = explode('::', $values['segment_code']);

        $job = $this->jobsRepository->add($segment[1], $segment[0]);

        $batch = $this->batchesRepository->add(
            $job->id,
            !empty($values['email_count']) ? $values['email_count'] : null,
            !empty($values['start_at']) ? $values['start_at'] : null
        );

        $this->batchTemplatesRepository->add(
            $job->id,
            $batch->id,
            $values['template_id']
        );

        if ($values['b_template_id'] !== null) {
            $this->batchTemplatesRepository->add(
                $job->id,
                $batch->id,
                $values['b_template_id']
            );
        }

        ($this->onSuccess)($job);
    }
}
