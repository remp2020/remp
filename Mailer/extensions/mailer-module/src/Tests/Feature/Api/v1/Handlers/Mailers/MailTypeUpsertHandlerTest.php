<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Mailers;

use Nette\Utils\Json;
use Remp\MailerModule\Api\v1\Handlers\Mailers\MailTypeUpsertHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailTypeUpsertHandlerTest extends BaseApiHandlerTestCase
{
    protected $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = $this->getHandler(MailTypeUpsertHandler::class);
    }

    public function testEmptyUpsert()
    {
        $response = $this->request([]);
        $this->assertEquals(400, $response->getCode());
        $this->assertEquals('invalid_input', $response->getPayload()['code']);
    }

    public function testNewMailType()
    {
        $layout = $this->createMailLayout();
        $systemMailType = $this->createMailTypeWithCategory('system', 'system');
        $testedMailType = $this->createMailTypeWithCategory('newsletters', 'editorial');
        $welcomeEmail1 = $this->createTemplate($layout, $systemMailType);
        $welcomeEmail2 = $this->createTemplate($layout, $systemMailType);

        // assign "subscribe" email
        $response = $this->request([
            'code' => $testedMailType->code,
            'subscribe_mail_template_code' => $welcomeEmail1->code,
        ]);
        $this->assertEquals(200, $response->getCode());
        $testedMailType = $this->listsRepository->find($testedMailType->id);
        $this->assertEquals($welcomeEmail1->id, $testedMailType->subscribe_mail_template_id);

        // change "subscribe" email
        $response = $this->request([
            'code' => $testedMailType->code,
            'subscribe_mail_template_code' => $welcomeEmail2->code,
        ]);
        $this->assertEquals(200, $response->getCode());
        $testedMailType = $this->listsRepository->find($testedMailType->id);
        $this->assertEquals($welcomeEmail2->id, $testedMailType->subscribe_mail_template_id);

        // try to change "subscribe" email to nonexisting mail
        $response = $this->request([
            'code' => $testedMailType->code,
            'subscribe_mail_template_code' => 'foo',
        ]);
        $this->assertEquals(404, $response->getCode());
        $this->assertEquals('subscribe_template_not_found', $response->getPayload()['code']);

        // unassign subscribe email
        $response = $this->request([
            'code' => $testedMailType->code,
            'subscribe_mail_template_code' => null,
        ]);
        $this->assertEquals(200, $response->getCode());
        $testedMailType = $this->listsRepository->find($testedMailType->id);
        $this->assertNull($testedMailType->subscribe_mail_template_id);
    }

    public function testMailTypeSubscribeTemplateManipulation()
    {
        $layout = $this->createMailLayout();
        $mailType = $this->createMailTypeWithCategory('system', 'system');
        $welcomeEmail = $this->createTemplate($layout, $mailType);

        $response = $this->request([
            'code' => 'test_mail_type',
            'mail_type_category_id' => $mailType->mail_type_category->id,
            'priority' => 100,
            'title' => 'TEST mail type',
            'description' => 'TEST description',
        ]);
        $this->assertEquals(200, $response->getCode());
    }

    private function request(array $params): JsonApiResponse
    {
        $response = $this->handler->handle([
            'raw' => Json::encode($params),
        ]);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        return $response;
    }
}
