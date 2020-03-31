<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\DateTime;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\Repository\UserSubscriptionVariantsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class UserPreferencesHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    private $userSubscriptionVariantsRepository;

    use JsonValidationTrait;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository,
        UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository
    ) {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->userSubscriptionVariantsRepository = $userSubscriptionVariantsRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST_RAW, 'raw')
        ];
    }


    public function handle($params)
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/user-preferences.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

//        $rows = $this->userSubscriptionsRepository->findByEmail($payload['email']); TODO use this once mailer uses email as a primary identifier

        $rows = $this->userSubscriptionsRepository
            ->getTable()
            ->where(['user_id' => $payload['user_id'], 'user_email' => $payload['email']]);


        if (isset($payload['subscribed'])) {
            $rows->where('subscribed', $payload['subscribed']);
        }

        $rows = $rows->fetchAll();

        $output = [];

        $subscribedVariants = [];

        foreach ($this->userSubscriptionVariantsRepository->multiSubscribedVariants($rows) as $variant) {
            $subscribedVariants[$variant->mail_user_subscription_id][] = $variant;
        }

        foreach ($rows as $row) {
            $variants = [];
            foreach ($subscribedVariants[$row->id] ?? [] as $variant) {
                $variants[] = [
                    'id' => $variant->mail_type_variant_id,
                    'code' => $variant->code,
                    'title' => $variant->title,
                ];
            }

            $output[$row->mail_type_id] = [
                'id' => $row->mail_type_id,
                'code' => $row->mail_type->code,
                'title' => $row->mail_type->title,
                'is_subscribed' => $row->subscribed,
                'variants' => $variants,
                'updated_at' => $row->updated_at->format(DateTime::RFC3339),
            ];
        }

        return new JsonApiResponse(200, $output);
    }
}
