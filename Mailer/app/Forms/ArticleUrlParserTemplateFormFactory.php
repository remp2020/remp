<?php
declare(strict_types=1);

namespace Remp\Mailer\Forms;

use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Request;
use Nette\Security\User;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Crm;
use Remp\MailerModule\Models\Segment\Mailer;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class ArticleUrlParserTemplateFormFactory
{
    private const FORM_ACTION_WITH_JOBS_CREATED = 'generate_emails_jobs_created';
    private const FORM_ACTION_WITH_JOBS_STARTED = 'generate_emails_jobs';

    private string $segmentCode;

    private string $segmentProvider = Mailer::PROVIDER_ALIAS;

    private string $layoutCode;

    public $onUpdate;

    public $onSave;

    public function __construct(
        private readonly TemplatesRepository $templatesRepository,
        private readonly LayoutsRepository $layoutsRepository,
        private readonly ListsRepository $listsRepository,
        private readonly JobsRepository $jobsRepository,
        private readonly BatchesRepository $batchesRepository,
        private readonly PermissionManager $permissionManager,
        private readonly User $user,
        private readonly Request $request,
        private readonly MailTypesRepository $mailTypesRepository,
        private readonly SourceTemplatesRepository $sourceTemplatesRepository,
    ) {
    }

    public function setSegmentCode(string $segmentCode, string $segmentProvider = Mailer::PROVIDER_ALIAS): void
    {
        $this->segmentCode = $segmentCode;
        $this->segmentProvider = $segmentProvider;
    }

    public function setLayoutCode(string $layoutCode): void
    {
        $this->layoutCode = $layoutCode;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        if (!$this->layoutCode) {
            $form->addError("Default value 'layout code' is missing.");
        }

        $mailTypes = $this->listsRepository->all()
            ->where(['public_listing' => true])
            ->fetchPairs('code', 'title');

        $form->addSelect('mail_type_code', 'Newsletter list', $mailTypes)
            ->setPrompt('Select type')
            ->setRequired("Field 'Type' is required.");

        $form->addText('from', 'Sender')
            ->setRequired("Field 'Sender' is required.");

        $form->addText('subject', 'Subject')
            ->setRequired("Field 'Subject' is required.");

        $form->addText('subject_b', 'Subject (B version)')
            ->setNullable();

        $form->addText('email_count', 'Batch size')
            ->setNullable();

        $form->addText('start_at', 'Start date')
            ->setNullable();

        $form->addHidden('mail_layout_code');
        $form->addHidden('source_template_id');
        $form->addHidden('html_content');
        $form->addHidden('text_content');

        $sourceTemplate = $this->sourceTemplatesRepository->find($this->request->getPost('source_template_id'));
        $defaults = [
            'source_template_id' => $sourceTemplate->id,
            'mail_layout_code' => $this->getLayoutCode($sourceTemplate->code),
        ];

        $form->setDefaults($defaults);

        if ($this->permissionManager->isAllowed($this->user, 'batch', 'start')) {
            $withJobs = $form->addSubmit(self::FORM_ACTION_WITH_JOBS_STARTED);
            $withJobs->getControlPrototype()
                ->setName('button')
                ->setHtml('Generate newsletter batch and start sending');
        }

        $withJobsCreated = $form->addSubmit(self::FORM_ACTION_WITH_JOBS_CREATED);
        $withJobsCreated->getControlPrototype()
            ->setName('button')
            ->setHtml('Generate newsletter batch');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, $values)
    {
        try {
            $defaults = $this->getMailTypeDefaults($values['mail_type_code']);
        } catch (\UnhandledMatchError) {
            $form->addError("Unsupported mail type selected.");
            return;
        }

        $generate = function ($htmlBody, $textBody, $mailLayoutCode) use ($values, $form, $defaults) {
            $mailType = $this->mailTypesRepository->findBy('code', $values['mail_type_code']);
            $mailLayout = $this->layoutsRepository->findBy('code', $mailLayoutCode);

            $mailTemplate = $this->templatesRepository->add(
                $defaults['name'],
                $this->templatesRepository->getUniqueTemplateCode($defaults['code']),
                '',
                $values['from'],
                $values['subject'],
                $textBody,
                $htmlBody,
                $mailLayout->id,
                $mailType->id,
            );

            if (isset($this->segmentCode)) {
                $segmentCode = $this->segmentCode;
                $segmentProvider = $this->segmentProvider;
            } else {
                $segmentCode = Mailer::mailTypeSegment($mailTemplate->mail_type->code);
                $segmentProvider = Mailer::PROVIDER_ALIAS;
            }

            $jobContext = null;
            $jobSegmentsManager = (new JobSegmentsManager())->includeSegment($segmentCode, $segmentProvider);

            $mailJob = $this->jobsRepository->add($jobSegmentsManager, $jobContext);
            $batch = $this->batchesRepository->add(
                $mailJob->id,
                (int)$values['email_count'],
                $values['start_at'],
                BatchesRepository::METHOD_RANDOM
            );
            $this->batchesRepository->addTemplate($batch, $mailTemplate);

            if (isset($values['subject_b'])) {
                $mailTemplateB = $this->templatesRepository->add(
                    $defaults['name'],
                    $this->templatesRepository->getUniqueTemplateCode($defaults['code']),
                    '',
                    $values['from'],
                    $values['subject_b'],
                    $textBody,
                    $htmlBody,
                    $mailLayout->id,
                    $mailType->id
                );
                $this->batchesRepository->addTemplate($batch, $mailTemplateB);
            }

            $batchStatus = BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND;
            /** @var SubmitButton $withJobsCreatedSubmit */
            $withJobsCreatedSubmit = $form[self::FORM_ACTION_WITH_JOBS_CREATED];
            if ($withJobsCreatedSubmit->isSubmittedBy()) {
                $batchStatus = BatchesRepository::STATUS_CREATED;
            }

            $this->batchesRepository->updateStatus($batch, $batchStatus);
        };

        $generate(
            $values['html_content'],
            $values['text_content'],
            $values['mail_layout_code'],
        );

        $this->onSave->__invoke();
    }

    public function getLayoutCode(string $sourceTemplateCode): string
    {
        return match ($sourceTemplateCode) {
            'dn3-article-url-parser' => 'dn3-default-wide',
            default => $this->layoutCode,
        };
    }

    public function getMailTypeDefaults(string $mailTypeCode): array
    {
        return match ($mailTypeCode) {
            'newsletter_weekly' => [
                'name' => 'Piatkový výber šéfredaktora ' . date('j.n.Y'),
                'code' => 'piatkovy_vyber_sefredaktora_' . date('dmY'),
            ],
            'napunk-weekly' => [
                'name' => 'Napunk piatkový výber ' . date('j.n.Y'),
                'code' => 'napunk_piatkovy_vyber_' . date('dmY'),
            ],
            'kultura-vyber' => [
                'name' => 'Najlepšie z kultúry ' . date('j.n.Y'),
                'code' => 'najlepsie_z_kultury_' . date('dmY'),
            ],
            'vyber-sport' => [
                'name' => 'Najlepšie zo športu ' . date('j.n.Y'),
                'code' => 'najlepsie_zo_sportu_' . date('dmY'),
            ],
            'veda' => [
                'name' => 'Najlepšie z vedy ' . date('j.n.Y'),
                'code' => 'najlepsie_z_vedy_' . date('dmY'),
            ],
            'ekonomika-vyber' => [
                'name' => 'Najlepšie z Denníka E ' . date('j.n.Y'),
                'code' => 'najlepsie_z_dennika_e_' . date('dmY'),
            ],
            'novinky-obchod' => [
                'name' => 'Novinky v obchode Denníka N ' . date('j.n.Y'),
                'code' => 'novinky_v_obchode_dennika_n_' . date('dmY'),
            ],
            'blogs' => [
                'name' => 'Najlepšie z Blogov N ' . date('j.n.Y'),
                'code' => 'najlepsie_z_blogov_n_' . date('dmY'),
            ],
            'health' => [
                'name' => 'Rodina a vzťahy ' . date('j.n.Y'),
                'code' => 'rodina_a_vztahy_' . date('dmY'),
            ],
        };
    }
}
