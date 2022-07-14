<?php
declare(strict_types=1);

namespace Tests\Unit;

use Nette\DI\Container;
use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\Mailer\EmailAllowList;

class EmailAllowListTest extends TestCase
{
    protected EmailAllowList $emailAllowList;

    public function setUp(): void
    {
        /** @var Container $container */
        $container = $GLOBALS['container'];

        $this->emailAllowList = $container->getByType(EmailAllowList::class);
        $this->emailAllowList->reset();
    }

    public function testEmailAllowListEmptyShouldAllowAllEmailAddresses()
    {
        $this->assertTrue($this->emailAllowList->isAllowed('example1@example.com'));
        $this->assertTrue($this->emailAllowList->isAllowed('example2@example.com'));
        $this->assertTrue($this->emailAllowList->isAllowed('example3@example.com'));
    }

    public function testEmailAllowOnlySpecifiedEmails()
    {
        $this->emailAllowList->allow('example1@example.com');
        $this->emailAllowList->allow('example2@example.com');

        $this->assertTrue($this->emailAllowList->isAllowed('example1@example.com'));
        $this->assertTrue($this->emailAllowList->isAllowed('example2@example.com'));
        $this->assertFalse($this->emailAllowList->isAllowed('example2@example.sk'));
    }

    public function testEmailAllowAllEmailAddressesFromDomain()
    {
        $this->emailAllowList->allow('@example.com');
        $this->assertTrue($this->emailAllowList->isAllowed('example1@example.com'));
        $this->assertTrue($this->emailAllowList->isAllowed('example2@example.com'));
        $this->assertFalse($this->emailAllowList->isAllowed('example2@example.sk'));
    }
}
