<?php
declare(strict_types=1);

namespace Tests\Unit\Hermes;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Nette\Database\Table\Selection;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\Mailer\Hermes\ConfirmCrmUserHandler;
use Remp\MailerModule\Hermes\HermesException;
use Remp\MailerModule\Models\Crm\Client;
use Remp\MailerModule\Models\Crm\UserNotFoundException;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\LogsRepository;
use Tomaj\Hermes\Message;

class ConfirmCrmUserHandlerTest extends MockeryTestCase
{
    private const EMAIL = 'user@example.com';
    private const MAIL_SENDER_ID = 'sender-id-123';
    private const EVENT_TIMESTAMP = 1724572800;

    private Client&MockInterface $crmClient;

    private LogsRepository&MockInterface $logsRepository;

    private ConfirmCrmUserHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crmClient = Mockery::mock(Client::class);
        $this->logsRepository = Mockery::mock(LogsRepository::class);
        $this->handler = new ConfirmCrmUserHandler($this->logsRepository, $this->crmClient);
    }

    public function testClickedEventConfirmsCrmUser(): void
    {
        $lookupWindowStart = self::EVENT_TIMESTAMP - LogsRepository::SENDER_ID_LOOKUP_WINDOW_DAYS * 24 * 60 * 60;
        $this->logsRepository->shouldReceive('findBySenderId')
            ->with(self::MAIL_SENDER_ID, Mockery::on(fn (\DateTimeInterface $since) => $since->getTimestamp() === $lookupWindowStart))
            ->andReturn($this->mailLog())
            ->once();
        $this->crmClient->shouldReceive('confirmUser')
            ->with(self::EMAIL)
            ->once();

        $this->assertTrue($this->handler->handle($this->message()));
    }

    #[DataProvider('ignoredEventsProvider')]
    public function testOtherEventsAreIgnored(string $event): void
    {
        $this->crmClient->shouldNotReceive('confirmUser');

        $this->assertTrue($this->handler->handle($this->message(['event' => $event])));
    }

    public static function ignoredEventsProvider(): array
    {
        return [
            'delivered' => ['delivered'],
            'opened' => ['opened'],
            'failed' => ['failed'],
        ];
    }

    public function testMissingMailSenderIdIsIgnored(): void
    {
        $this->crmClient->shouldNotReceive('confirmUser');

        $this->assertTrue($this->handler->handle($this->message(['mail_sender_id' => null])));
    }

    public function testMissingTimestampThrowsException(): void
    {
        $this->expectException(HermesException::class);

        $this->handler->handle($this->message(['timestamp' => null]));
    }

    public function testMissingEventThrowsException(): void
    {
        $this->expectException(HermesException::class);

        $this->handler->handle($this->message(['event' => null]));
    }

    public function testMissingMailLogFails(): void
    {
        $this->logsRepository->shouldReceive('findBySenderId')
            ->andReturnNull();
        $this->crmClient->shouldNotReceive('confirmUser');

        $this->assertFalse($this->handler->handle($this->message()));
    }

    public function testUserMissingInCrmFailsWithoutRetry(): void
    {
        $this->logsRepository->shouldReceive('findBySenderId')
            ->andReturn($this->mailLog());
        $this->crmClient->shouldReceive('confirmUser')
            ->andThrow(new UserNotFoundException());

        $this->assertFalse($this->handler->handle($this->message()));
    }

    private function message(array $payload = []): Message
    {
        $payload = array_filter([
            'mail_sender_id' => self::MAIL_SENDER_ID,
            'timestamp' => self::EVENT_TIMESTAMP + 0.123456,
            'event' => 'clicked',
            'email' => self::EMAIL,
            ...$payload,
        ], fn($value) => $value !== null);

        return new Message('mailgun-event', $payload);
    }

    private function mailLog(): ActiveRow
    {
        return new ActiveRow(['email' => self::EMAIL], $this->createStub(Selection::class));
    }
}
