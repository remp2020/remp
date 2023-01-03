<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api;

use JsonSchema\Validator;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tracy\Debugger;

trait JsonValidationTrait
{
    public $errorResponse = null;

    protected function validateInput(string $request, string $schema)
    {
        if (empty($request)) {
            $this->errorResponse = new JsonApiResponse(IResponse::S400_BadRequest, ['status' => 'error', 'message' => 'Empty request']);
            return false;
        }

        try {
            $payload = Json::decode($request, Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            Debugger::log($e->getMessage());
            $this->errorResponse = new JsonApiResponse(IResponse::S400_BadRequest, ['status' => 'error', 'message' => "Malformed JSON: " . $e->getMessage()]);
            return false;
        }

        $schema = file_get_contents($schema);
        $data = Json::decode($request);
        $validator = new Validator();
        $validator->validate($data, (object) Json::decode($schema));

        if (!$validator->isValid()) {
            $data = ['status' => 'error', 'message' => 'Payload error', 'errors' => []];
            foreach ($validator->getErrors() as $error) {
                $data['errors'][] = "{$error['property']}: {$error['message']}";
            }
            $this->errorResponse = new JsonApiResponse(IResponse::S400_BadRequest, $data);

            return false;
        }
        return $payload;
    }

    public function hasErrorResponse()
    {
        return $this->errorResponse !== null;
    }

    public function getErrorResponse()
    {
        return $this->errorResponse;
    }
}
