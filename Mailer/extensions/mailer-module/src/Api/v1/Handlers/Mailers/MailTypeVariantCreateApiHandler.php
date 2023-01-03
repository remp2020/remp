<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Http\IResponse;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\MailTypesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MailTypeVariantCreateApiHandler extends BaseHandler
{
    use JsonValidationTrait;

    private $mailTypesRepository;

    private $listVariantsRepository;

    public function __construct(
        MailTypesRepository $mailTypesRepository,
        ListVariantsRepository $listVariantsRepository
    ) {
        parent::__construct();
        $this->mailTypesRepository = $mailTypesRepository;
        $this->listVariantsRepository = $listVariantsRepository;
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw')
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/mail-type-variant.schema.json');
        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        $mailType = $this->mailTypesRepository->findBy('code', $payload['mail_type_code']);
        if (!$mailType) {
            return new JsonApiResponse(IResponse::S404_NotFound, [
                'status' => 'error',
                'message' => 'No such mail type with code: ' . $payload['mail_type_code'],
            ]);
        }

        $mailTypeVariant = $this->listVariantsRepository->findBy('code', $payload['code']);
        if ($mailTypeVariant) {
            return new JsonApiResponse(IResponse::S400_BadRequest, [
                'status' => 'error',
                'code' => 'already_exists',
                'message' => 'Mail type variant with code: ' . $payload['code'] . ' already exists.',
            ]);
        }

        $sorting = $payload['sorting'] ?? null;
        $mailTypeVariant = $this->listVariantsRepository->add($mailType, $payload['title'], $payload['code'], $sorting);
        return new JsonApiResponse(200, [
            'status' => 'ok',
            'id' => $mailTypeVariant->id,
            'mail_type_code' => $mailTypeVariant->mail_type->code,
            'title' => $mailTypeVariant->title,
            'code' => $mailTypeVariant->code,
            'sorting' => $mailTypeVariant->sorting
        ]);
    }
}
