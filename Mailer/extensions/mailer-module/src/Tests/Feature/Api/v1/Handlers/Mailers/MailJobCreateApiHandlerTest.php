<?php

namespace Tests\Feature\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Api\v1\Handlers\Mailers\MailJobCreateApiHandler;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Repositories\ActiveRow;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailJobCreateApiHandlerTest extends BaseApiHandlerTestCase
{
    /** @var MailJobCreateApiHandler */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = $this->getHandler(MailJobCreateApiHandler::class);
    }

    public function testValidRequiredParamsShouldCreateNewJob()
    {
        $params = $this->getDefaultParams();
        unset($params['context'], $params['mail_type_variant_code']);

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $payload = $response->getPayload();

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('ok', $payload['status']);
        $this->assertIsNumeric($payload['id']);

        $mailJob = $this->jobsRepository->find($payload['id']);
        $jobSegmentsManager = new JobSegmentsManager($mailJob);
        $this->assertEquals($params['segment_code'], $jobSegmentsManager->getIncludeSegments()[0]['code']);
        $this->assertEquals($params['segment_provider'], $jobSegmentsManager->getIncludeSegments()[0]['provider']);
        $this->assertNull($mailJob->context);
        $this->assertNull($mailJob->mail_type_variant_id);
    }

    public function testValidAllParamsShouldCreateNewJob()
    {
        $params = $this->getDefaultParams();

        $tomorrow = new \DateTime('tomorrow 15:00');
        $params['start_at'] = $tomorrow->format(DATE_RFC3339);

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $payload = $response->getPayload();

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('ok', $payload['status']);
        $this->assertIsNumeric($payload['id']);

        /** @var ActiveRow $mailJob */
        $mailJob = $this->jobsRepository->find($payload['id']);
        $jobSegmentsManager = new JobSegmentsManager($mailJob);
        $this->assertEquals($params['segment_code'], $jobSegmentsManager->getIncludeSegments()[0]['code']);
        $this->assertEquals($params['segment_provider'], $jobSegmentsManager->getIncludeSegments()[0]['provider']);
        $this->assertEquals($params['context'], $mailJob->context);
        $this->assertEquals($params['mail_type_variant_code'], $mailJob->mail_type_variant->code);

        $mailJobBatch = $mailJob->related('mail_job_batch')->fetch();
        $this->assertEquals($tomorrow, $mailJobBatch->start_at);
    }

    public function testInvalidTemplate()
    {
        $params = $this->getDefaultParams();
        $params['template_id']++;

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(404, $response->getCode());
        $this->assertEquals('error', $response->getPayload()['status']);
    }

    public function testInvalidSegmentCode()
    {
        $params = $this->getDefaultParams();
        $params['segment_code'] = 'invalid_segment';

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(404, $response->getCode());
        $this->assertEquals('error', $response->getPayload()['status']);
    }

    public function testInvalidSegmentProvider()
    {
        $params = $this->getDefaultParams();
        $params['segment_provider'] = 'invalid_segment';

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(404, $response->getCode());
        $this->assertEquals('error', $response->getPayload()['status']);
    }

    public function testInvalidMailTypeVariant()
    {
        $params = $this->getDefaultParams();
        $params['mail_type_variant_code'] = 'invalid-variant';

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(404, $response->getCode());
        $this->assertEquals('error', $response->getPayload()['status']);
    }

    public function testDeletedMailTypeVariant()
    {
        $params = $this->getDefaultParams();

        $mailTypeVariantCode = $params['mail_type_variant_code'];
        $mailTypeVariant = $this->listVariantsRepository->findByCode($mailTypeVariantCode);

        $this->listVariantsRepository->softDelete($mailTypeVariant);

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(404, $response->getCode());
        $this->assertEquals('error', $response->getPayload()['status']);
    }

    private function getDefaultParams()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $mailLayout = $this->createMailLayout();
        $mailTemplate = $this->createTemplate($mailLayout, $mailType);
        $mailTypeVariant = $this->createMailTypeVariant($mailType);

        return [
            'segment_code' => 'dummy-segment',
            'segment_provider' => 'dummy-segment',
            'template_id' => $mailTemplate->id,
            'mail_type_variant_code' => $mailTypeVariant->code,
            'context' => 'context'
        ];
    }
}
