<?php

namespace Tests\Feature\Api;

use App\Model\ExternalSegmentGroup;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentBrowser;
use Remp\BeamModule\Model\SegmentGroup;
use Remp\BeamModule\Tests\TestCase;

class ReplaceSegmentBrowsersApiTest extends TestCase
{
    use RefreshDatabase;

    private Segment $segment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            Authenticate::class,
        ]);

        $this->segment = Segment::create([
            'name' => 'Winback browsers',
            'code' => 'winback-browsers',
            'active' => true,
            'segment_group_id' => SegmentGroup::getByCode(ExternalSegmentGroup::CODE)->id,
        ]);
    }

    public function testReplacesBrowsers(): void
    {
        $url = route('api.segments.browsers.replace', 'winback-browsers');

        $this->putJson($url, ['browser_ids' => ['b1', 'b2', 'b2']])
            ->assertOk()
            ->assertExactJson([
                'code' => 'winback-browsers',
                'name' => 'Winback browsers',
                'active' => true,
                'browsers_count' => 2,
            ]);

        $this->putJson($url, ['browser_ids' => ['b2', 'b3', 'b4']])
            ->assertOk()
            ->assertJson(['browsers_count' => 3]);

        $this->assertEqualsCanonicalizing(
            ['b2', 'b3', 'b4'],
            $this->segment->browsers()->pluck('browser_id')->all()
        );
    }

    public function testEmptyListClearsSegment(): void
    {
        SegmentBrowser::forceCreate(['segment_id' => $this->segment->id, 'browser_id' => 'b0']);

        $this->putJson(route('api.segments.browsers.replace', 'winback-browsers'), ['browser_ids' => []])
            ->assertOk()
            ->assertJson(['browsers_count' => 0]);

        $this->assertSame(0, $this->segment->browsers()->count());
    }
}
