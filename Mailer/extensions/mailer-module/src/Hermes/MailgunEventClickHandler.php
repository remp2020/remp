<?php

namespace Remp\MailerModule\Hermes;

use Exception;
use Nette\Http\Url;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\ContentGenerator\Replace\RtmClickReplace;
use Remp\MailerModule\Repositories\MailTemplateLinkClicksRepository;
use Remp\MailerModule\Repositories\MailTemplateLinksRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class MailgunEventClickHandler implements HandlerInterface
{
    public function __construct(
        private MailTemplateLinksRepository $mailTemplateLinksRepository,
        private MailTemplateLinkClicksRepository $mailTemplateLinkClicksRepository,
    ) {
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if ($payload['event'] !== 'clicked') {
            return true;
        }
        if (!isset($payload['mail_sender_id'])) {
            // email sent via mailgun and not sent via mailer (e.g. CMS)
            return true;
        }
        if (!isset($payload['timestamp'])) {
            throw new HermesException('unable to handle event: timestamp is missing');
        }
        if (!isset($payload['event'])) {
            throw new HermesException('unable to handle event: event is missing');
        }
        if (!isset($payload['url'])) {
            throw new HermesException('unable to handle event: url is missing');
        }

        $clickedUrl = new Url($payload['url']);
        $clickedUrlParams = $clickedUrl->getQueryParameters();

        if (!isset($clickedUrlParams[RtmClickReplace::HASH_PARAM])) {
            return true;
        }

        $mailTemplateLink = $this->mailTemplateLinksRepository->findLinkByHash($clickedUrlParams[RtmClickReplace::HASH_PARAM]);
        if (!isset($mailTemplateLink)) {
            throw new Exception("Mail template link missing, url: [{$clickedUrl}]");
        }

        $eventTimestamp = explode('.', (string) $payload['timestamp'])[0];
        $clickedAt = DateTime::from($eventTimestamp);

        $this->mailTemplateLinkClicksRepository->add($mailTemplateLink, $clickedAt);
        $this->mailTemplateLinksRepository->incrementClickCount($mailTemplateLink);

        return true;
    }
}
