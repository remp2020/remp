<?php
declare(strict_types=1);

namespace Remp\Mailer\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Security\User;
use Remp\MailerModule\Models\Auth\PermissionManager;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Crm;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class NewsfilterTemplateFormFactory
{
    private const FORM_ACTION_WITH_JOBS_CREATED = 'generate_emails_jobs_created';
    private const FORM_ACTION_WITH_JOBS_STARTED = 'generate_emails_jobs';

    private $activeUsersSegment;

    private $inactiveUsersSegment;

    private $templatesRepository;

    private $layoutsRepository;

    private $jobsRepository;

    private $batchesRepository;

    private $listsRepository;

    private $permissionManager;

    private $user;

    public $onUpdate;

    public $onSave;

    public function __construct(
        $activeUsersSegment,
        $inactiveUsersSegment,
        TemplatesRepository $templatesRepository,
        LayoutsRepository $layoutsRepository,
        ListsRepository $listsRepository,
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        PermissionManager $permissionManager,
        User $user
    ) {
        $this->activeUsersSegment = $activeUsersSegment;
        $this->inactiveUsersSegment = $inactiveUsersSegment;
        $this->templatesRepository = $templatesRepository;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->permissionManager = $permissionManager;
        $this->user = $user;
    }

    public function create()
    {
        $form = new Form;
        $form->addProtection();

        $form->addText('name', 'Name')
            ->setRequired("Field 'Name' is required.");

        $form->addText('code', 'Identifier')
            ->setRequired("Field 'Identifier' is required.");

        $form->addSelect('mail_layout_id', 'Template', $this->layoutsRepository->all()->fetchPairs('id', 'name'));

        $form->addSelect('locked_mail_layout_id', 'Template for non-subscribers', $this->layoutsRepository->all()->fetchPairs('id', 'name'));

        $mailTypes = $this->listsRepository->all()->where(['public_listing' => true])->fetchPairs('id', 'code');

        $form->addSelect('mail_type_id', 'Type', $mailTypes)
            ->setRequired("Field 'Type' is required.");

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

        $sourceTemplateId = $_POST['source_template_id'] ?? null;
        if ($sourceTemplateId == 77) {
            $defaults = [
                'name' => 'Pod slnkom Jany Shemesh ' . date('Y. n. j.'),
                'code' => 'pod_slnkom_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 59, // Pod slnkom Jany Shemesh
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 76) {
            $defaults = [
                'name' => 'Napunk newsfilter ' . date('Y. n. j.'),
                'code' => 'napunk_nwsf_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 55, // Napunk newsfilter
                'from' => 'Napunk <napunk@napunk.sk>',
            ];
        } elseif ($sourceTemplateId == 70) {
            $defaults = [
                'name' => 'Vývoj bojov ' . date('j.n.Y'),
                'code' => 'nwsf_vyvoj_bojov_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 54, // Vývoj bojov na Ukrajine
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 69) {
            $defaults = [
                'name' => 'Boxová ulička ' . date('j.n.Y'),
                'code' => 'nwsf_boxova_ulicka_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 52, // Boxová ulička
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 67) {
            $defaults = [
                'name' => 'Týždeň v práve Rada Procházku ' . date('j.n.Y'),
                'code' => 'nwsf_pravoprochazka_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 50, // Týždeň v práve Rada Procházku
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 68) {
            $defaults = [
                'name' => 'Týždeň v zdraví ' . date('j.n.Y'),
                'code' => 'nwsf_tyzdenvzdravi_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 51, // Týždeň v zdraví
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 66) {
            $defaults = [
                'name' => 'Greenfilter ' . date('j.n.Y'),
                'code' => 'nwsf_greenfilter_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 49, // Vlhová newsletter
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 65) {
            $defaults = [
                'name' => 'Vlhová newsletter ' . date('j.n.Y'),
                'code' => 'nwsf_vlhova_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 47, // Vlhová newsletter
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 64) {
            $defaults = [
                'name' => 'Školský newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_skolsky_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 46, // Školský newsfilter
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 59) {
            $defaults = [
                'name' => 'Súhrn dňa letnej olympiády ' . date('j.n.Y'),
                'code' => 'nwsf_let_olympiada_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 37, // Súhrn dňa letnej olympiády
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 58) {
            $defaults = [
                'name' => 'Súhrn dňa futbalového Eura ' . date('j.n.Y'),
                'code' => 'nwsf_fut_euro_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 39, // Súhrn dňa futbalového Eura
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 55) {
            $defaults = [
                'name' => 'Ako to číta Ivan Mikloš ' . date('j.n.Y'),
                'code' => 'nwsf_miklos_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 40, // Ako to číta Ivan Mikloš
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 54) {
            $defaults = [
                'name' => 'Český týždeň ' . date('j.n.Y'),
                'code' => 'nwsf_cz_tyzden_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 38, // Český týždeň
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 53) {
            $defaults = [
                'name' => 'Súhrn MS v hokeji ' . date('j.n.Y'),
                'code' => 'nwsf_ms_hokej_2021_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 36, // Súhrn MS v hokeji
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 50) {
            $defaults = [
                'name' => 'Porazení ' . date('j.n.Y'),
                'code' => 'nwsf_porazeni_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 35, // Porazeni newsfilter
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 49) {
            $defaults = [
                'name' => 'Košický newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_kosice_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 34, // Kosickz newsfilter
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 48) {
            $defaults = [
                'name' => 'Týždeň v behu ' . date('j.n.Y'),
                'code' => 'nwsf_beh_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 33, // Týždeň v behu
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 47) {
            $defaults = [
                'name' => 'Grandslamové turnaje ' . date('j.n.Y'),
                'code' => 'nwsf_grandslam_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 32, // Grandslamové turnaje
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 44) {
            $defaults = [
                'name' => 'Euroligy ' . date('j.n.Y'),
                'code' => 'nwsf_euroligy_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 31, // Euroligy
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 43) {
            $defaults = [
                'name' => 'Súhrn Tour de France ' . date('j.n.Y'),
                'code' => 'nwsf_tourdefrance_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 30, // Súhrn Tour de France
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 42) {
            $defaults = [
                'name' => 'Súhrn Ligy majstrov ' . date('j.n.Y'),
                'code' => 'nwsf_ligamajstrov_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 29, // Súhrn Ligy majstrov
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 41) {
            $defaults = [
                'name' => 'Týždeň v NHL ' . date('j.n.Y'),
                'code' => 'nwsf_nhl_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 28, // tyzden v nhl
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 81) {
            $defaults = [
                'name' => 'Ráno s NHL ' . date('j.n.Y'),
                'code' => 'nwsf_rano-nhl_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 61, // rano s NHL
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 40) {
            $defaults = [
                'name' => 'Ofsajd ' . date('j.n.Y'),
                'code' => 'nwsf_ofsajd_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 26, // ofsajd
                'from' => 'Lukáš Vráblik Denník N <lukas.vrablik@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 33) {
            $defaults = [
                'name' => 'High Five ' . date('j.n.Y'),
                'code' => 'high_five_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 25, // high five
                'from' => 'Kultúra Denník N <kultura@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 37) {
            $defaults = [
                'name' => 'Športový newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_sport_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 20, // newsfilter sport,
                'from' => 'Michal Červený Denník N <michal.cerveny@dennikn.sk>',
            ];
        } elseif ($sourceTemplateId == 38) {
            $defaults = [
                'name' => 'Svetový newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_world_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 24, // newsfilter world,
                'from' => 'Rastislav Kačmár Denník N <rastislav.kacmar@dennikn.sk>',
            ];
        } else {
            $defaults = [
                'name' => 'Newsfilter ' . date('j.n.Y'),
                'code' => 'nwsf_' . date('dmY'),
                'mail_layout_id' => 33, // layout for subscribers
                'locked_mail_layout_id' => 33, // layout for non-subscribers
                'mail_type_id' => 9, // newsfilter,
                'from' => 'Denník N <info@dennikn.sk>',
            ];
        }


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
        $generate = function ($htmlBody, $textBody, $mailLayoutId, $segmentCode = null) use ($values, $form) {
            $mailTemplate = $this->templatesRepository->add(
                $values['name'],
                $this->templatesRepository->getUniqueTemplateCode($values['code']),
                '',
                $values['from'],
                $values['subject'],
                $textBody,
                $htmlBody,
                $mailLayoutId,
                $values['mail_type_id']
            );

            $jobContext = null;
            if ($values['article_id']) {
                $jobContext = 'newsletter.' . $values['article_id'];
            }

            $mailJob = $this->jobsRepository->add((new JobSegmentsManager())->includeSegment($segmentCode, Crm::PROVIDER_ALIAS), $jobContext);
            $batch = $this->batchesRepository->add($mailJob->id, null, null, BatchesRepository::METHOD_RANDOM);
            $this->batchesRepository->addTemplate($batch, $mailTemplate);

            $batchStatus = BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND;

            /** @var SubmitButton $withJobsCreatedSubmit */
            $withJobsCreatedSubmit = $form[self::FORM_ACTION_WITH_JOBS_CREATED];
            if ($withJobsCreatedSubmit->isSubmittedBy()) {
                $batchStatus = BatchesRepository::STATUS_CREATED;
            }

            $this->batchesRepository->updateStatus($batch, $batchStatus);
        };

        $generate(
            $values['locked_html_content'],
            $values['locked_text_content'],
            $values['locked_mail_layout_id'],
            $this->inactiveUsersSegment
        );
        $generate(
            $values['html_content'],
            $values['text_content'],
            $values['mail_layout_id'],
            $this->activeUsersSegment
        );

        $this->onSave->__invoke();
    }
}
