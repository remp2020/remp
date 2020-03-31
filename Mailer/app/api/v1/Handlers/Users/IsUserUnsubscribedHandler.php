<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Remp\MailerModule\Api\JsonValidationTrait;

class IsUserUnsubscribedHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    use JsonValidationTrait;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST_RAW, 'raw')
        ];
    }


    public function handle($params)
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/is-user-unsubscribed.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

// TODO change later when identification will be based only on user email or id

        $output = $this->userSubscriptionsRepository->getTable()->where([
            'user_id' => $payload['user_id'],
            'email' => $payload['email'],
            'mail_type_id' => $payload['list_id'],
            'subscribed' => false
            ])->count('*') > 0;

        return new JsonApiResponse(200, $output);
    }
}
