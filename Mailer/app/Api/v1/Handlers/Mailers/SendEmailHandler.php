<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use JsonSchema\Validator;
use Nette\Http\Response;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Hermes\RedisDriver;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\Hermes\Dispatcher;
use Tomaj\Hermes\Emitter;
use Tomaj\Hermes\Message;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;
use Tracy\Debugger;

class SendEmailHandler extends BaseHandler
{
    use JsonValidationTrait;

    private $templatesRepository;

    private $userSubscriptionsRepository;

    private $logsRepository;

    private $hermesEmitter;

    public function __construct(
        TemplatesRepository $templatesRepository,
        UserSubscriptionsRepository $userSubscriptionsRepository,
        LogsRepository $logsRepository,
        Emitter $hermesEmitter
    ) {
        parent::__construct();
        $this->templatesRepository = $templatesRepository;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->logsRepository = $logsRepository;
        $this->hermesEmitter = $hermesEmitter;
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw')
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/email.schema.json');
        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        $mailTemplate = $this->templatesRepository->getByCode($payload['mail_template_code']);
        if (!$mailTemplate) {
            return new JsonApiResponse(Response::S404_NOT_FOUND, ['status' => 'error', 'message' => "Template with code [{$payload['mail_template_code']}] doesn't exist."]);
        }
        $isUnsubscribed = $this->userSubscriptionsRepository->isEmailUnsubscribed($payload['email'], $mailTemplate->mail_type_id);
        if ($isUnsubscribed) {
            return new JsonApiResponse(Response::S200_OK, ['status' => 'ok', 'message' => "Email was not sent, user is unsubscribed from the mail type."]);
        }
        if (isset($payload['context'])) {
            $alreadySent = $this->logsRepository->alreadySentContext($payload['context']);
            if ($alreadySent) {
                return new JsonApiResponse(Response::S200_OK, ['status' => 'ok', 'message' => "Email was not sent, provided context was already sent before."]);
            }
        }
        foreach ($payload['attachments'] ?? [] as $i => $attachment) {
            if (!isset($attachment['content'])) {
                $content = @file_get_contents($attachment['file']); // @ is escalated to exception
                if ($content === false) {
                    return new JsonApiResponse(Response::S400_BAD_REQUEST, ['status' => 'error', 'message' => "Attachment file [{$attachment['file']}] can't be read and content was not provided."]);
                }
                $payload['attachments'][$i]['content'] = base64_encode($content);
            }
        }

        if (isset($payload['schedule_at'])) {
            $executeAt = strtotime($payload['schedule_at']);
        } else {
            $executeAt = null;
        }

        $this->hermesEmitter->emit(new Message('send-email', [
            'mail_template_code' => $mailTemplate->code,
            'email' => $payload['email'],
            'params' => $payload['params'] ?? [],
            'context' => $payload['context'] ?? null,
            'attachments' => $payload['attachments'] ?? [],
        ], null, null, $executeAt), RedisDriver::PRIORITY_HIGH);

        return new JsonApiResponse(Response::S202_ACCEPTED, ['status' => 'ok', 'message' => "Email was scheduled to be sent."]);
    }
}
