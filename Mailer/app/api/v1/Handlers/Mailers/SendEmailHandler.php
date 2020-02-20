<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use JsonSchema\Validator;
use Nette\Http\Response;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\Hermes\Emitter;
use Tomaj\Hermes\Message;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tracy\Debugger;

class SendEmailHandler extends BaseHandler
{
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

    public function params()
    {
        return [];
    }

    public function handle($payload)
    {
        $request = file_get_contents("php://input");
        if (empty($request)) {
            $response = new JsonApiResponse(Response::S400_BAD_REQUEST, ['status' => 'error', 'message' => 'Empty request']);
            return $response;
        }
        
        try {
            $payload = Json::decode($request, true);
        } catch (JsonException $e) {
            $response = new JsonApiResponse(Response::S400_BAD_REQUEST, ['status' => 'error', 'message' => "Malformed JSON: " . $e->getMessage()]);
            return $response;
        }

        $validator = $this->validateInput($request);
        if (!$validator->isValid()) {
            $data = ['status' => 'error', 'message' => 'Payload error', 'errors' => []];
            foreach ($validator->getErrors() as $error) {
                $data['errors'][] = "{$error['property']}: {$error['message']}";
            }
            Debugger::log("Cannot parse paywall content data - " . Json::encode($payload) . ' -> ' . implode(', ', $data['errors']));
            $response = new JsonApiResponse(Response::S400_BAD_REQUEST, $data);
            return $response;
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
        ], null, null, $executeAt));

        return new JsonApiResponse(Response::S202_ACCEPTED, ['status' => 'ok', 'message' => "Email was scheduled to be sent."]);
    }

    private function validateInput(string $input): Validator
    {
        $schema = Json::decode(file_get_contents(__DIR__ . '/email.schema.json'));
        $data = Json::decode($input);
        $validator = new Validator();
        $validator->validate($data, (object) $schema);
        return $validator;
    }
}
