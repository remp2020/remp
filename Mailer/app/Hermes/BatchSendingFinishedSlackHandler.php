<?php
declare(strict_types=1);

namespace Remp\Mailer\Hermes;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Nette\Application\LinkGenerator;
use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repositories\BatchesRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\Handler\RetryTrait;
use Tomaj\Hermes\MessageInterface;
use Tracy\Debugger;

class BatchSendingFinishedSlackHandler implements HandlerInterface
{
    use RetryTrait;

    private $ignoredCategories = [];

    public function __construct(
        protected readonly string $mailerBaseUrl,
        protected readonly string $environment,
        protected readonly string $slackWebhookUrl,
        protected readonly BatchesRepository $batchesRepository,
        protected LinkGenerator $linkGenerator,
    ) {
        $this->linkGenerator = $this->linkGenerator->withReferenceUrl($this->mailerBaseUrl);
    }

    public function ignoreMailTypeCategory(string $categoryCode): void
    {
        $this->ignoredCategories[$categoryCode] = true;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();

        $status = $payload['status'];
        if ($status !== BatchesRepository::STATUS_DONE) {
            return true;
        }

        $batchId = $payload['mail_job_batch_id'];
        $jobBatch = $this->batchesRepository->find($batchId);
        if (!$jobBatch) {
            return false;
        }

        $mailTemplate = $jobBatch->related('mail_job_batch_templates')->limit(1)->fetch();
        $mailType = $mailTemplate->mail_template->mail_type;
        if (array_key_exists($mailType->mail_type_category->code, $this->ignoredCategories)) {
            return true;
        }

        $this->sendSlackNotification($jobBatch, $mailType);
        return true;
    }

    protected function sendSlackNotification(ActiveRow $jobBatch, ActiveRow $mailType): void
    {
        $targetUrl = $this->linkGenerator->link(':Mailer:Job:Show', ['id' => $jobBatch->mail_job_id]);
        $durationDiff = $jobBatch->last_email_sent_at->diff($jobBatch->first_email_sent_at);

        $duration = "{$durationDiff->s}s";
        if ($durationDiff->i) {
            $duration = "{$durationDiff->i}m $duration";
        }
        if ($durationDiff->h) {
            $duration = "{$durationDiff->h}h $duration";
        }

        // https://docs.slack.dev/block-kit/formatting-with-rich-text
        $payload = [
            'blocks' => [
                [
                    'type' => 'rich_text',
                    'elements' => [
                        [
                            'type' => 'rich_text_section',
                            'elements' => [
                                [
                                    'type' => 'text',
                                    'text' => $this->environment,
                                    'style' => [
                                        'bold' => true,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text_section',
                            'elements' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Newsletter ',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $mailType->title,
                                    'style' => [
                                        'italic' => true,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => " was just sent in {$duration} (",
                                ],
                                [
                                    'type' => 'link',
                                    'text' => "{$jobBatch->sent_emails} emails sent",
                                    'url' => $targetUrl,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => ").",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $client = new Client();
            $client->post($this->slackWebhookUrl, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (ClientException $e) {
            Debugger::log('Unable to post Slack notification: ' . $e->getResponse()->getBody()->getContents(), Debugger::ERROR);
        }
    }
}
