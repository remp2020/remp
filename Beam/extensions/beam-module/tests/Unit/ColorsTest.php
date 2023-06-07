<?php

namespace Remp\BeamModule\Tests\Unit;

use Remp\BeamModule\Helpers\Colors;
use Remp\BeamModule\Tests\TestCase;

class ColorsTest extends TestCase
{
    public function testOrdering()
    {
        $tags = [
            'internal' => 1200,
            'email' => 10,
            'unknown_3' => 10000,
            'direct/IM' => 1500,
            'unknown_1' => 4000,
            'unknown_2' => 8000,
        ];

        $orderedTags = Colors::orderRefererMediumTags($tags, true);
        $this->assertEquals(['internal', 'direct/IM', 'email', 'unknown_3', 'unknown_2', 'unknown_1'], $orderedTags);

        $tagWithDifferentCounts = [
            'unknown_1' => 10,
            'internal' => 1,
            'unknown_3' => 2,
        ];

        $orderedTags = Colors::orderRefererMediumTags($tagWithDifferentCounts, true);
        // order will be influenced by previously cached results
        $this->assertEquals(['internal', 'unknown_3', 'unknown_1'], $orderedTags);

        $tagWithDifferentCounts2 = [
            'unknown_1' => 10,
            'internal' => 1,
            'unknown_3' => 2,
            'previously_unknown' => 5,
        ];

        $orderedTags = Colors::orderRefererMediumTags($tagWithDifferentCounts2, true);
        // order will not be influenced by previously cached results (because 'previously_unknown' tag was introduced)
        $this->assertEquals(['internal', 'unknown_1', 'previously_unknown', 'unknown_3'], $orderedTags);
    }
}
