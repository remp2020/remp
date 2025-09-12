<?php
declare(strict_types=1);

namespace Remp\Mailer\Forms;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Request;
use Nette\Security\User;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Crm;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class NewsfilterTemplateFormFactory
{
    private const FORM_ACTION_WITH_JOBS_CREATED = 'generate_emails_jobs_created';
    private const FORM_ACTION_WITH_JOBS_STARTED = 'generate_emails_jobs';

    private $activeUsersSegment;
    private $inactiveUsersSegment;

    public $onUpdate;
    public $onSave;

    public function __construct(
        $activeUsersSegment,
        $inactiveUsersSegment,
        private TemplatesRepository $templatesRepository,
        private LayoutsRepository $layoutsRepository,
        private ListsRepository $listsRepository,
        private ListVariantsRepository $listVariantsRepository,
        private SourceTemplatesRepository $sourceTemplatesRepository,
        private JobsRepository $jobsRepository,
        private BatchesRepository $batchesRepository,
        private PermissionManager $permissionManager,
        private User $user,
        private LinkGenerator $linkGenerator,
        private Request $request,
    ) {
        $this->activeUsersSegment = $activeUsersSegment;
        $this->inactiveUsersSegment = $inactiveUsersSegment;
    }

    public function create(): Form
    {
        $defaults = array_filter($this->getDefaults((int) $this->request->getPost('source_template_id')));

        $form = new Form;
        $form->addProtection();

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.");

        $form->addText('code', 'Identifier')
            ->setRequired("Field 'Identifier' is required.");

        $form->addSelect('mail_layout_code', 'Template', $this->layoutsRepository->all()->fetchPairs('code', 'name'));

        $form->addSelect('locked_mail_layout_code', 'Template for non-subscribers', $this->layoutsRepository->all()->fetchPairs('code', 'name'));

        $mailTypes = $this->listsRepository->all()->where(['public_listing' => true])->fetchPairs('code', 'title');

        $mailTypeField = $form->addSelect('mail_type_code', 'Type', $mailTypes)
            ->setRequired("Field 'Type' is required.");

        $variantsList = $selectedMailType = null;
        if ($this->request->getPost('mail_type_code')) {
            $selectedMailType = $this->listsRepository->findByCode($this->request->getPost('mail_type_code'))->fetch();
        } elseif (isset($defaults['mail_type_code'])) {
            $selectedMailType = $this->listsRepository->findByCode($defaults['mail_type_code'])->fetch();
        }
        if ($selectedMailType) {
            $variantsList = $this->listVariantsRepository->getVariantsForType($selectedMailType)->fetchPairs('code', 'title');
        }

        $form->addSelect('mail_type_variant_code', 'Type variant', $variantsList)
            ->setPrompt('Select variant')
            ->setDisabled(!$variantsList || count($variantsList) === 0)
            ->setHtmlAttribute('data-depends', $mailTypeField->getHtmlName())
            // %value% will be replaced by selected ID from 'data-depends' input
            ->setHtmlAttribute('data-url', $this->linkGenerator->link('Mailer:Job:MailTypeCodeVariants', ['id'=>'%value%']));

        $form->addText('from', 'Sender')
            ->setHtmlAttribute('placeholder', 'e.g. info@domain.com')
            ->setRequired("Field 'Sender' is required.");

        $form->addText('subject', 'Subject')
            ->setRequired("Field 'Subject' is required.");

        $form->addHidden('html_content');
        $form->addHidden('text_content');
        $form->addHidden('locked_html_content');
        $form->addHidden('locked_text_content');
        $form->addHidden('article_id');

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

    private function getDefaults(?int $sourceTemplateId): array
    {
        $defaults = [
            'name' => 'Newsfilter ' . date('j.n.Y'),
            'code' => 'nwsf_' . date('dmY'),
            'mail_layout_code' => '33_empty-layout', // layout for subscribers
            'locked_mail_layout_code' => '33_empty-layout', // layout for non-subscribers
            'mail_type_code' => 'newsfilter',
            'mail_type_variant_code' => null,
            'from' => 'Denník N <info@dennikn.sk>',
        ];

        if (!$sourceTemplateId) {
            return $defaults;
        }
        $sourceTemplate = $this->sourceTemplatesRepository->find($sourceTemplateId);
        if (!$sourceTemplate) {
            return $defaults;
        }

        $override = match ($sourceTemplate->code) {
            'rano-nhl' => [
                'name' => 'Ráno s NHL ' . date('j.n.Y'),
                'code' => 'nwsf_rano-nhl_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'mail_type_variant_code' => 'newsfilter_sport.rano-nhl'
            ],
            'pod-slankom' => [
                'name' => 'Pod slnkom Jany Shemesh ' . date('Y. n. j.'),
                'code' => 'pod_slnkom_' . date('dmY'),
                'mail_type_code' => 'pod-slnkom',
            ],
            'napunk-newsfilter' => [
                'name' => 'Napunk newsfilter ' . date('Y. n. j.'),
                'code' => 'napunk_nwsf_' . date('dmY'),
                'mail_type_code' => 'napunk-newsfilter',
                'from' => 'Napunk <napunk@napunk.sk>',
            ],
            'vyvoj-bojov' => [
                'name' => 'Vývoj bojov ' . date('j.n.Y'),
                'code' => 'nwsf_vyvoj_bojov_' . date('dmY'),
                'mail_type_code' => 'vyvoj-bojov',
            ],
            'boxova-ulicka' => [
                'name' => 'Boxová ulička ' . date('j.n.Y'),
                'code' => 'nwsf_boxova_ulicka_' . date('dmY'),
                'mail_type_code' => 'boxova-ulicka',
            ],
            'tyzden-v-zdravi' => [
                'name' => 'Týždeň v zdraví ' . date('j.n.Y'),
                'code' => 'nwsf_tyzdenvzdravi_' . date('dmY'),
                'mail_type_code' => 'tyzden-v-zdravi',
            ],
            'tyzden-v-prave' => [
                'name' => 'Týždeň v práve Rada Procházku ' . date('j.n.Y'),
                'code' => 'nwsf_pravoprochazka_' . date('dmY'),
                'mail_type_code' => 'tyzden-v-prave',
            ],
            'greenfilter' => [
                'name' => 'Greenfilter ' . date('j.n.Y'),
                'code' => 'nwsf_greenfilter_' . date('dmY'),
                'mail_type_code' => 'greenfilter',
            ],
            'vlhova-newsletter' => [
                'name' => 'Vlhová newsletter ' . date('j.n.Y'),
                'code' => 'nwsf_vlhova_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'mail_type_variant_code' => 'newsfilter_sport.vlhova-newsletter'
            ],
            'skolsky-newsfilter' => [
                'name' => 'Školský newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_skolsky_' . date('dmY'),
                'mail_type_code' => 'skolsky-newsfilter',
            ],
            'suhrn-letna-olympiada' => [
                'name' => 'Súhrn dňa letnej olympiády ' . date('j.n.Y'),
                'code' => 'nwsf_let_olympiada_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'mail_type_variant_code' => 'newsfilter_sport.suhrn-letna-olympiada'
            ],
            'suhrn-futbalove-euro' => [
                'name' => 'Súhrn dňa futbalového Eura ' . date('j.n.Y'),
                'code' => 'nwsf_fut_euro_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'mail_type_variant_code' => 'newsfilter_sport.suhrn-futbalove-euro'
            ],
            'ako-cita-ivan-miklo' => [
                'name' => 'Ako to číta Ivan Mikloš ' . date('j.n.Y'),
                'code' => 'nwsf_miklos_' . date('dmY'),
                'mail_type_code' => 'ako-cita-ivan-miklo',
            ],
            'cesky-tyzden' => [
                'name' => 'Český týždeň ' . date('j.n.Y'),
                'code' => 'nwsf_cz_tyzden_' . date('dmY'),
                'mail_type_code' => 'cesky-tyzden',
            ],
            'suhrn-ms-hokej' => [
                'name' => 'Súhrn MS v hokeji ' . date('j.n.Y'),
                'code' => 'nwsf_ms_hokej_2021_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'mail_type_variant_code' => 'newsfilter_sport.suhrn-ms-hokej'
            ],
            'porazeni' => [
                'name' => 'Porazení ' . date('j.n.Y'),
                'code' => 'nwsf_porazeni_' . date('dmY'),
                'mail_type_code' => 'porazeni',
            ],
            'kosicky-newsfilter' => [
                'name' => 'Košický newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_kosice_' . date('dmY'),
                'mail_type_code' => 'kosicky-newsfilter',
            ],
            'tyzden-v-behu' => [
                'name' => 'Týždeň v behu ' . date('j.n.Y'),
                'code' => 'nwsf_beh_' . date('dmY'),
                'mail_type_code' => 'tyzden-v-behu',
            ],
            'grandslamove-turnaje' => [
                'name' => 'Grandslamové turnaje ' . date('j.n.Y'),
                'code' => 'nwsf_grandslam_' . date('dmY'),
                'mail_type_code' => 'grandslamove-turnaje',
            ],
            'euroligy' => [
                'name' => 'Euroligy ' . date('j.n.Y'),
                'code' => 'nwsf_euroligy_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'mail_type_variant_code' => 'newsfilter_sport.euroligy'
            ],
            'tour-de-france' => [
                'name' => 'Súhrn Tour de France ' . date('j.n.Y'),
                'code' => 'nwsf_tourdefrance_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'mail_type_variant_code' => 'newsfilter_sport.suhrn-tour-defrance'
            ],
            'suhrn-ligy-majstrov' => [
                'name' => 'Súhrn Ligy majstrov ' . date('j.n.Y'),
                'code' => 'nwsf_ligamajstrov_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'mail_type_variant_code' => 'newsfilter_sport.liga-majstrov'
            ],
            'tyzden-v-nhl' => [
                'name' => 'Týždeň v NHL ' . date('j.n.Y'),
                'code' => 'nwsf_nhl_' . date('dmY'),
                'mail_type_code' => 'tyzden-nhl',
            ],
            'ofsajd' => [
                'name' => 'Ofsajd ' . date('j.n.Y'),
                'code' => 'nwsf_ofsajd_' . date('dmY'),
                'mail_type_code' => 'ofsajd',
                'from' => 'Lukáš Vráblik Denník N <lukas.vrablik@dennikn.sk>',
            ],
            'svetovy-newsfilter-v2' => [
                'name' => 'Svetový newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_world_' . date('dmY'),
                'mail_type_code' => 'svetovy_newsfilter',
                'from' => 'Rastislav Kačmár Denník N <rastislav.kacmar@dennikn.sk>',
            ],
            'newsfilter-sport-v2' => [
                'name' => 'Športový newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_sport_' . date('dmY'),
                'mail_type_code' => 'newsfilter_sport',
                'from' => 'Michal Červený Denník N <michal.cerveny@dennikn.sk>',
            ],
            'high-five-v2' => [
                'name' => 'High Five ' . date('j.n.Y'),
                'code' => 'high_five_' . date('dmY'),
                'mail_type_code' => 'high_five',
                'from' => 'Kultúra Denník N <kultura@dennikn.sk>',
            ],
            'newsfilter-v3' => [
                'name' => 'Newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_' . date('dmY'),
                'mail_type_code' => 'newsfilter',
                'from' => 'Denník N <info@dennikn.sk>',
            ],
            'psychologicka-poradna' => [
                'name' => 'Psychologická poradňa ' . date('j.n.Y'),
                'code' => 'psypod_' . date('dmY'),
                'mail_type_code' => 'psychologicka-poradna',
                'from' => 'Poradňa Denníka N <poradna@dennikn.sk>',
            ],
            'pat-knih' => [
                'name' => 'Päť kníh ' . date('j.n.Y'),
                'code' => 'patknih_' . date('dmY'),
                'mail_type_code' => 'pat-knih',
                'from' => 'Denník N <info@dennikn.sk>',
            ],
            'do-hlbky' => [
                'name' => 'Do hĺbky s Jánom Markošom ' . date('j.n.Y'),
                'code' => 'dohlbky_' . date('dmY'),
                'mail_type_code' => 'do-hlbky',
                'from' => 'Denník N <info@dennikn.sk>',
            ],
            'trumpov-svet' => [
                'name' => 'Trumpov svet ' . date('j.n.Y'),
                'code' => 'trumpov_svet_' . date('dmY'),
                'mail_type_code' => 'trumpov-svet',
                'from' => 'Denník N <info@dennikn.sk>',
            ],
            'poradna-o-tele' => [
                'name' => 'Poradňa o tele ' . date('j.n.Y'),
                'code' => 'poradna_o_tele_' . date('dmY'),
                'mail_type_code' => 'poradna-o-tele',
                'from' => 'Denník N <info@dennikn.sk>',
            ],
            'vikend-bez-politiky' => [
                'name' => 'Víkend bez politiky ' . date('j.n.Y'),
                'code' => 'vikend_bez_politiky_' . date('dmY'),
                'mail_type_code' => 'bez-politiky',
                'from' => 'Denník N <info@dennikn.sk>',
            ],
            'predvolebne-madarsko' => [
                'name' => 'Predvolebné Maďarsko ' . date('j.n.Y'),
                'code' => 'predvolebne_madarsko_' . date('dmY'),
                'mail_type_code' => 'predvolebne-madarsko',
                'from' => 'Denník N <posta@dennikn.sk>',
            ],
            'valaszt-a-magyar' => [
                'name' => 'Választ a magyar ' . date('Y. n. j.'),
                'code' => 'valaszt_a_magyar_' . date('dmY'),
                'mail_type_code' => 'valaszt-a-magyar',
                'from' => 'Napunk <napunk@dennikn.sk>',
            ],
            default => throw new \Exception("No default values found for source template code='{$sourceTemplate->code}'"),
        };

        return array_merge($defaults, $override);
    }

    public function formSucceeded(Form $form, $values): void
    {
        $startSending = true;
        /** @var SubmitButton $withJobsCreatedSubmit */
        $withJobsCreatedSubmit = $form[self::FORM_ACTION_WITH_JOBS_CREATED];
        if ($withJobsCreatedSubmit->isSubmittedBy()) {
            $startSending = false;
        }

        $this->createJob(
            values: $values,
            htmlBody: $values['locked_html_content'],
            textBody: $values['locked_text_content'],
            mailLayoutCode: $values['locked_mail_layout_code'],
            segmentCode: $this->inactiveUsersSegment,
            startSending: $startSending,
        );

        $this->createJob(
            values: $values,
            htmlBody: $values['html_content'],
            textBody: $values['text_content'],
            mailLayoutCode: $values['mail_layout_code'],
            segmentCode: $this->activeUsersSegment,
            startSending: $startSending,
        );

        $this->onSave->__invoke();
    }

    private function createJob(iterable $values, $htmlBody, $textBody, $mailLayoutCode, $segmentCode, bool $startSending): void
    {
        $mailLayout = $this->layoutsRepository->findBy('code', $mailLayoutCode);
        if (!$mailLayout) {
            throw new \Exception("Unable to find mail_layout with code '{$mailLayoutCode}'");
        }
        $mailType = $this->listsRepository->findByCode($values['mail_type_code'])->fetch();
        if (!$mailType) {
            throw new \Exception("Unable to find mail_type with code '{$values['mail_type_code']}'");
        }

        $mailTemplate = $this->templatesRepository->add(
            name: $values['name'],
            code: $this->templatesRepository->getUniqueTemplateCode($values['code']),
            description: '',
            from: $values['from'],
            subject: $values['subject'],
            templateText: $textBody,
            templateHtml: $htmlBody,
            layoutId: $mailLayout->id,
            typeId: $mailType->id,
        );

        $jobContext = null;
        if ($values['article_id']) {
            $jobContext = 'newsletter.' . $values['article_id'];
        }

        $mailTypeVariant = null;
        if (isset($values['mail_type_variant_code'])) {
            $mailTypeVariant = $this->listVariantsRepository->findByCode($values['mail_type_variant_code']);
        }

        $mailJob = $this->jobsRepository->add(
            (new JobSegmentsManager())->includeSegment($segmentCode, Crm::PROVIDER_ALIAS),
            $jobContext,
            $mailTypeVariant,
        );
        $batch = $this->batchesRepository->add($mailJob->id, null, null, BatchesRepository::METHOD_RANDOM);
        $this->batchesRepository->addTemplate($batch, $mailTemplate);

        $batchStatus = $startSending ? BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND : BatchesRepository::STATUS_CREATED;
        $this->batchesRepository->updateStatus($batch, $batchStatus);
    }
}
