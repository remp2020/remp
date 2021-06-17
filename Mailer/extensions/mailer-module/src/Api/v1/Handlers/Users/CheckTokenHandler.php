<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Models\Auth\AutoLogin;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class CheckTokenHandler extends BaseHandler
{
    /** @var AutoLogin */
    private $autoLogin;

    public function __construct(AutoLogin $autoLogin)
    {
        parent::__construct();

        $this->autoLogin = $autoLogin;
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
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Wrong format.']);
        }

        if (!isset($data['token'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Missing parameters.']);
        }

        $token = $this->autoLogin->getToken($data['token']);
        if (!$token) {
            return new JsonApiResponse(404, ['status' => 'error', 'message' => 'Token not found.']);
        }

        $now = new DateTime();
        if ($now < $token->valid_from || $now > $token->valid_to || $token->used_count >= $token->max_count) {
            return new JsonApiResponse(403, ['status' => 'error', 'message' => 'Token not valid.']);
        }

        $this->autoLogin->useToken($token);

        return new JsonApiResponse(200, ['status' => 'ok', 'email' => $token->email]);
    }
}
