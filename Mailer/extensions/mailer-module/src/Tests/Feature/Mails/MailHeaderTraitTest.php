<?php
declare(strict_types=1);

namespace Feature\Mails;

use Nette\Mail\Message;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Models\Mailer\MailHeaderTrait;
use Tests\Feature\BaseFeatureTestCase;

class MailHeaderTraitTest extends BaseFeatureTestCase
{
    use MailHeaderTrait;

    public static function dataProvider()
    {
        return [
            'TestRegularHeader_ShouldReturnFilename' => [
                'headerValue' => 'Content-Disposition: attachment; filename="invoice-2024-09-24.pdf"',
                'parameter' => 'filename',
                'result' => 'invoice-2024-09-24.pdf',
            ],
            'TestRegularHeader_NotRealParameter' => [
                'headerValue' => 'Content-Disposition: attachment; filename="invoice-2024-09-24.pdf"',
                'parameter' => 'dummy',
                'result' => null,
            ],
            'TestRegularHeader_AdditionalParameters' => [
                'headerValue' => 'Content-Disposition: attachment; filename="invoice-2024-09-24.pdf"; foo="bar"',
                'parameter' => 'filename',
                'result' => 'invoice-2024-09-24.pdf',
            ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testMailHeaderTrait(
        string $headerValue,
        string $parameter,
        ?string $result,
    ) {
        $parameter = $this->getHeaderParameter($headerValue, $parameter);
        $this->assertSame($result, $parameter);
    }

    /**
     * Nette Mailer adds attachments to email as `Content-Disposition` headers with format:
     *
     *    Content-Disposition: attachment; filename="invoice-2024-09-24.pdf"
     *
     * Mailgun requires attachments as API parameters, so in MailgunMailer we need to load attachment
     * and parse filename from header. This is handled by preg_match within `MailHeaderTrait`.
     *
     * This is simple unit test which checks:
     * - If regex which loads attachment's filename from mail header is correct.
     * - If Nette didn't change how attachments (filenames) are attached to message.
     *   E.g. using different approach or attaching filename without quotes (allowed by specification).
     */
    public function testMessageMimePartHeaderForContentDisposition()
    {
        $filename = 'invoice-2024-09-24.pdf';

        // create attachment as Nette Mailer creates it
        $message = new Message();
        $message->addAttachment($filename, 'dummy content', 'text/plain');
        $attachments = $message->getAttachments();
        $attachment = reset($attachments);

        // parse header created by Nette with our Trait
        $attachmentName = $this->getHeaderParameter($attachment->getHeader('Content-Disposition'), 'filename');
        $this->assertEquals($filename, $attachmentName);
    }
}
