<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Models\Users\IUser;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class BulkUserRegisteredHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    private $userProvider;

    private $listsRepository;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository,
        ListsRepository $listsRepository,
        IUser $userProvider
    ) {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->userProvider = $userProvider;
        $this->listsRepository = $listsRepository;
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        try {
            $data = Json::decode($params['raw'], Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Input data was not valid JSON.']);
        }

        if (!isset($data['users'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => "Input data missing 'users' array."]);
        }

        $errors = [];
        $iteration = -1;
        $users = [];

        foreach ($data['users'] as $item) {
            $iteration++;

            // process email
            if (!isset($item['email'])) {
                $errors = array_merge($errors, ["element_" . $iteration => 'Required field missing: email.']);
                continue;
            }
            if (!empty($this->userSubscriptionsRepository->findByEmail($item['email']))) {
                continue;
            }

            // process user_id
            if (!isset($item['user_id'])) {
                $errors = array_merge($errors, ["element_" . $iteration => 'Required field missing: user_id.']);
                continue;
            }
            $userID = filter_var($item['user_id'], FILTER_VALIDATE_INT);
            if ($userID === false) {
                $errors = array_merge($errors, [
                    "element_" . $iteration => "Invalid field: 'user_id' must be integer. Got [{$item['user_id']}]."
                ]);
                continue;
            }

            $users[] = [
                'email' => $item['email'],
                'user_id' => $userID,
            ];
        }

        if (!empty($errors)) {
            return new JsonApiResponse(400, [
                'status' => 'error',
                'message' => 'Input data contains errors. See included list of errors.',
                'errors' => $errors,
            ]);
        }

        $lists = $this->listsRepository->all();
        foreach ($users as $user) {
            /** @var ActiveRow $list */
            foreach ($lists as $list) {
                if ($list->auto_subscribe) {
                    $this->userSubscriptionsRepository->subscribeUser($list, $user['user_id'], $user['email']);
                } else {
                    $this->userSubscriptionsRepository->unsubscribeUser($list, $user['user_id'], $user['email']);
                }
            }
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
