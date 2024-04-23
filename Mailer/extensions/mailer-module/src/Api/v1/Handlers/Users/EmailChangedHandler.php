<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Models\Users\UserManager;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\PostInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class EmailChangedHandler extends BaseHandler
{
    public function __construct(
        private UserManager $userManager,
    ) {
        parent::__construct();
    }

    public function params(): array
    {
        return [
            (new PostInputParam('original_email'))->setRequired(),
            (new PostInputParam('new_email'))->setRequired(),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $originalEmail = $params['original_email'];
        $newEmail = $params['new_email'];

        $result = $this->userManager->changeEmail($originalEmail, $newEmail);
        if (!$result) {
            return new JsonApiResponse(
                404,
                ['status' => 'error', 'code' => 'no_subscription_found', 'message' => 'No user subscriptions for email: ' . $originalEmail]
            );
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
