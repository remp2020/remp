<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Mailers;

use Mockery;
use Nette\Utils\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Api\v1\Handlers\Mailers\SendEmailHandler;
use Remp\MailerModule\Repositories\LogsRepository;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\Hermes\Dispatcher;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\NetteApi\Response\JsonApiResponse;

class SendEmailHandlerTest extends BaseApiHandlerTestCase
{
    /** @var SendEmailHandler */
    private $handler;

    /** @var Dispatcher */
    private $dispatcher;

    private $template;

    public function setUp(): void
    {
        parent::setUp();

        $layout = $this->createMailLayout();
        $mailType = $this->createMailTypeWithCategory('test_category', 'test_mail_type');
        $this->template = $this->createTemplate($layout, $mailType, 'test_template');

        $this->handler = $this->getHandler(SendEmailHandler::class);
        $this->dispatcher = $this->inject(Dispatcher::class);
    }

    public static function dataProvider()
    {
        return [
            'ValidParams_ShouldEmitSendEmail' => [
                'params' => [
                    'email' => 'admin@example.com',
                    'mail_template_code' => 'test_template',
                    'context' => 'test_context',
                ],
                'emitted' => 1,
                'httpCode' => 202,
            ],
            'InvalidMailTemplate_ShouldReturnNotFound' => [
                'params' => [
                    'email' => 'admin@example.com',
                    'mail_template_code' => 'foo',
                    'context' => 'test_context',
                ],
                'emitted' => 0,
                'httpCode' => 404,
            ],
            'AlreadySentContext_SameEmail_ShouldNotSendEmail' => [
                'params' => [
                    'email' => 'admin@example.com',
                    'mail_template_code' => 'test_template',
                    'context' => 'test_context',
                ],
                'emitted' => 0,
                'httpCode' => 200,
                'beforeTest' => function ($self) {
                    $self->writeMailLog('admin@example.com', 'test_context');
                },
            ],
            'AlreadySentContext_DifferentEmail_ShouldEmitSendEmail' => [
                'params' => [
                    'email' => 'admin@example.com',
                    'mail_template_code' => 'test_template',
                    'context' => 'test_context',
                ],
                'emitted' => 1,
                'httpCode' => 202,
                'beforeTest' => function ($self) {
                    $self->writeMailLog('user@example.com', 'test_context');
                },
            ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testSendEmailHandler(array $params, int $emitted, int $httpCode, callable $beforeTest = null)
    {
        if ($beforeTest) {
            $beforeTest($this);
        }

        /** @var HandlerInterface $mockListener */
        $mockListener = Mockery::mock(HandlerInterface::class)
            ->shouldReceive('handle')
            ->times($emitted)
            ->getMock();
        $this->dispatcher->registerHandler('send-email', $mockListener);

        $response = $this->request($params);
        $this->assertEquals($httpCode, $response->getCode());

        $this->dispatcher->handle();
    }

    private function request(array $params): JsonApiResponse
    {
        $response = $this->handler->handle([
            'raw' => Json::encode($params),
        ]);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        return $response;
    }

    private function writeMailLog($email, $context)
    {
        /** @var LogsRepository $mailLogs */
        $mailLogs = $this->inject(LogsRepository::class);
        $mailLogs->add($email, 'test_subject', $this->template->id, null, null, null, null, $context);
    }
}
