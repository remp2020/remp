<?php
declare(strict_types=1);

namespace Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\Generators\EuobserverArticleLocker;

class EuobserverArticleLockerTest extends TestCase
{
    private EuobserverArticleLocker $locker;

    protected function setUp(): void
    {
        $this->locker = new EuobserverArticleLocker();
    }

    public function testGetLockedPostCutsAtLockMarker(): void
    {
        self::assertSame(
            '<p>before</p>',
            $this->locker->getLockedPost('<p>before</p><!-- wp:eo/lock --><p>after</p>'),
        );
    }

    public function testGetLockedPostWithoutMarkerReturnsFullContent(): void
    {
        self::assertSame(
            '<p>before</p><p>after</p>',
            $this->locker->getLockedPost('<p>before</p><p>after</p>'),
        );
    }

    public function testInjectLockedMessageAppendsSnippetInclude(): void
    {
        self::assertStringContainsString(
            "<p>content</p>\n\n{{ include('eo-button-red'",
            $this->locker->injectLockedMessage('<p>content</p>'),
        );
    }
}
