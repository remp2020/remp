<?php

namespace Remp\MailerModule\Hermes;

use Nette\Http\Url;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Tracker\EventOptions;
use Remp\MailerModule\Models\Tracker\ITracker;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class TrackNewsletterArticlesHandler implements HandlerInterface
{
    private BatchTemplatesRepository $batchTemplatesRepository;
    private ITracker $tracker;
    private BatchesRepository $batchesRepository;
    private array $allowedHosts = ['*'];

    public function __construct(
        BatchTemplatesRepository $batchTemplatesRepository,
        BatchesRepository $batchesRepository,
        ITracker $tracker
    ) {
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->batchesRepository = $batchesRepository;
        $this->tracker = $tracker;
    }

    public function setAllowedHosts(array $allowedHosts): void
    {
        $this->allowedHosts = $allowedHosts;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['mail_job_batch_id'])) {
            throw new HermesException('unable to handle event: mail_job_batch_id is missing');
        }
        if (!isset($payload['time'])) {
            throw new HermesException('unable to handle event: time is missing');
        }
        if (!isset($payload['status'])) {
            throw new HermesException('unable to handle event: status is missing');
        }

        $batch = $this->batchesRepository->find($payload['mail_job_batch_id']);
        if (!$batch) {
            throw new HermesException('unable to handle event: mail job batch not found');
        }
        if ($payload['status'] !== BatchesRepository::STATUS_SENDING) {
            return true;
        }

        $batchTemplates = $this->batchTemplatesRepository->findByBatchId($batch->id);
        foreach ($batchTemplates as $batchTemplate) {
            preg_match_all('/<a.*?href="([^"]*?)".*?>/i', $batchTemplate->mail_template->mail_body_html, $matches);
            $matches = array_unique($matches[1]);

            $trackArticleOptions = [];
            foreach ($matches as $hrefUrl) {
                $url = new Url($hrefUrl);
                if (!$this->isAllowedHost($url->getHost())) {
                    continue;
                }

                $pathParts = explode('/', $url->getPath());
                if (count($pathParts) > 1 && is_numeric($pathParts[1])) {
                    $articleId = (int)$pathParts[1];

                    $options = new EventOptions();
                    $options->setFields([
                        'article_id' => $articleId,
                        'mail_template_id' => $batchTemplate->mail_template_id,
                        'mail_job_batch_id' => $payload['mail_job_batch_id']
                    ]);
                    $trackArticleOptions[$articleId] = $options;
                }
            }

            foreach ($trackArticleOptions as $trackArticleOption) {
                $this->tracker->trackEvent(
                    DateTime::from($payload['time']),
                    'newsletter',
                    $batchTemplate->mail_template->mail_type->code,
                    $trackArticleOption
                );
            }
        }
        return true;
    }

    private function isAllowedHost($host): bool
    {
        // Allow all hosts
        if (count($this->allowedHosts) === 1 && reset($this->allowedHosts) === '*') {
            return true;
        }

        return in_array($host, $this->allowedHosts, true);
    }
}
