<?php

namespace Remp\BeamModule\Console\Commands;

use Carbon\Carbon;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;
use Recurr\Transformer\Constraint\AfterConstraint;
use Remp\BeamModule\Console\Command;
use Remp\BeamModule\Contracts\Mailer\MailerContract;
use Remp\BeamModule\Model\Newsletter;
use Remp\BeamModule\Model\Newsletter\NewsletterCriterionEnum;
use Remp\BeamModule\Model\NewsletterCriterion;

class SendNewslettersCommand extends Command
{
    const COMMAND = 'newsletters:send';

    protected $signature = self::COMMAND;

    protected $description = 'Process newsletters data and generate Mailer jobs.';

    private $transformer;

    private $mailer;

    public function __construct(MailerContract $mailer)
    {
        parent::__construct();

        $config = new ArrayTransformerConfig();
        $this->transformer = new ArrayTransformer($config);
        $this->mailer = $mailer;
    }

    public function handle()
    {
        $this->line(Carbon::now() . ': Processing newsletters');

        $newsletters = Newsletter::where('state', Newsletter::STATE_STARTED)
            ->where('starts_at', '<=', Carbon::now())
            ->get();

        if ($newsletters->count() === 0) {
            $this->info("No newsletters to process");
            return 0;
        }

        foreach ($newsletters as $newsletter) {
            $this->line(Carbon::now() . ":   * {$newsletter->name}");
            $nextSending = $newsletter->starts_at;
            $hasMore = false;

            if ($newsletter->rule_object) {
                [$nextSending, $hasMore] = $this->retrieveNextSending($newsletter);
            }

            if ($nextSending) {
                $this->line(Carbon::now() . ":     * sending");
                $this->sendNewsletter($newsletter);
                $this->line(Carbon::now() . ":     * sent");
                $newsletter->last_sent_at = Carbon::now();

                if (!$hasMore) {
                    $newsletter->state = Newsletter::STATE_FINISHED;
                }
            } elseif (!$hasMore) {
                $this->line(Carbon::now() . ":     * marking as finished");
                $newsletter->state = Newsletter::STATE_FINISHED;
            }

            $newsletter->save();
        }

        $this->line(Carbon::now() . ': Done!');
        return 0;
    }

    private function retrieveNextSending($newsletter)
    {
        // newsletter hasn't been sent yet, include all dates after starts_at (incl.)
        // if has been sent yet, count all dates after last_sent_at (excl.)
        $afterConstraint = $newsletter->last_sent_at ?
            new AfterConstraint($newsletter->last_sent_at, false) :
            new AfterConstraint($newsletter->starts_at, true);

        $recurrenceCollection = $this->transformer->transform($newsletter->rule_object, $afterConstraint);

        $nextSending = null;
        $now = Carbon::now();
        foreach ($recurrenceCollection as $recurrence) {
            if ($recurrence->getStart() >= $now) {
                break;
            }
            $nextSending = Carbon::instance($recurrence->getStart());
        }

        $hasMore = $recurrenceCollection->count() > 1;
        return [$nextSending, $hasMore];
    }

    private function sendNewsletter(Newsletter $newsletter)
    {
        $criterion = new NewsletterCriterion(NewsletterCriterionEnum::from($newsletter->criteria));
        $articles = $newsletter->personalized_content ? [] :
            $criterion->getArticles(
                timespan: $newsletter->timespan,
                articlesCount: $newsletter->articles_count
            );

        if ($articles->count() === 0) {
            $this->line('  <comment>WARNING:</comment> No articles found for selected timespan, nothing is sent');
            return;
        }

        [$htmlContent, $textContent] = $this->generateEmail($newsletter, $articles);

        $templateId = $this->createTemplate($newsletter, $htmlContent, $textContent);

        $this->createJob($newsletter, $templateId);
    }

    private function createJob($newsletter, $templateId)
    {
        $jobId = $this->mailer->createJob($newsletter->segment_code, $newsletter->segment_provider, $templateId);
        $this->line(sprintf('  Mailer job successfully created (id: %s)', $jobId));
    }

    private function createTemplate($newsletter, $htmlContent, $textContent): int
    {
        $extras = null;
        if ($newsletter->personalized_content) {
            $extras = json_encode([
                'generator' => 'beam-unread-articles',
                'parameters' => [
                    'criteria' => [
                        $newsletter->criteria
                    ],
                    'timespan' => $newsletter->timespan,
                    'articles_count' => $newsletter->articles_count
                ]
            ]);
        }

        return $this->mailer->createTemplate(
            $newsletter->name,
            'beam_newsletter',
            'Newsletter generated by Beam',
            $newsletter->email_from,
            $newsletter->email_subject,
            $textContent,
            $htmlContent,
            $newsletter->mail_type_code,
            $extras
        );
    }

    private function generateEmail($newsletter, $articles)
    {
        $params = [];
        if ($newsletter->personalized_content) {
            $params['dynamic'] = true;
            $params['articles_count'] = $newsletter->articles_count;
        } else {
            $params['articles']=  implode("\n", $articles->pluck('url')->toArray());
        }

        $output = $this->mailer->generateEmail($newsletter->mailer_generator_id, $params);
        return [$output['htmlContent'], $output['textContent']];
    }
}
