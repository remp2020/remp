<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

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

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }


    public function handle(array $params): ResponseInterface
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/user-preferences.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        $rows = $this->userSubscriptionsRepository->getTable()
            ->where([
                'user_id' => $payload['user_id'],
                'user_email' => $payload['email'],
                'mail_type.deleted_at IS NULL',
            ]);


        if (isset($payload['subscribed'])) {
            $rows->where('subscribed', $payload['subscribed']);
        }

        $rows = $rows->fetchAll();
        if (!count($rows)) {
            return new JsonApiResponse(IResponse::S404_NotFound, [
                'status' => 'error',
                'message' => 'User with given ID/email has no subscription.',
            ]);
        }

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

            $output[] = [
                'id' => $row->mail_type_id,
                'code' => $row->mail_type->code,
                'title' => $row->mail_type->title,
                'is_subscribed' => (bool) $row->subscribed,
                'variants' => $variants,
                'created_at' => $row->created_at->format(DateTime::RFC3339),
                'updated_at' => $row->updated_at->format(DateTime::RFC3339),
            ];
        }

        return new JsonApiResponse(IResponse::S200_OK, $output);
    }
}
