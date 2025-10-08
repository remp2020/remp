<?php

namespace Remp\CampaignModule\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Snippet;
use Remp\CampaignModule\Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    public static function snippetExtractionProvider(): array
    {
        return [
            'noSnippets' => [
                'bannerJs' => 'console.log("Hello World");',
                'snippetsData' => [],
                'expectedSnippets' => [],
            ],
            'singleSnippet' => [
                'bannerJs' => 'var foo = "{{ foobar }}";',
                'snippetsData' => [
                    ['name' => 'foobar', 'value' => 'test value'],
                ],
                'expectedSnippets' => ['foobar'],
            ],
            'multipleSnippets' => [
                'bannerJs' => 'var foo = "{{ foobar }}"; var baz = "{{ bazBar }}";',
                'snippetsData' => [
                    ['name' => 'foobar', 'value' => 'test value'],
                    ['name' => 'bazBar', 'value' => 'another value'],
                ],
                'expectedSnippets' => ['foobar', 'bazBar'],
            ],
            'nestedSnippets' => [
                'bannerJs' => 'var foo = "{{ foobar }}";',
                'snippetsData' => [
                    ['name' => 'foobar', 'value' => 'test value with {{ bazBar }}'],
                    ['name' => 'bazBar', 'value' => 'nested value'],
                ],
                'expectedSnippets' => ['foobar', 'bazBar'],
            ],
            'deeplyNestedSnippets' => [
                'bannerJs' => 'var foo = "{{ level1 }}";',
                'snippetsData' => [
                    ['name' => 'level1', 'value' => 'Level 1 with {{ level2 }}'],
                    ['name' => 'level2', 'value' => 'Level 2 with {{ level3 }}'],
                    ['name' => 'level3', 'value' => 'Level 3 final value'],
                ],
                'expectedSnippets' => ['level1', 'level2', 'level3'],
            ],
            'circularReference' => [
                'bannerJs' => 'var foo = "{{ snippet1 }}";',
                'snippetsData' => [
                    ['name' => 'snippet1', 'value' => 'Value with {{ snippet2 }}'],
                    ['name' => 'snippet2', 'value' => 'Value with {{ snippet1 }}'],
                ],
                'expectedSnippets' => ['snippet1', 'snippet2'],
            ],
            'duplicateReferences' => [
                'bannerJs' => 'var foo = "{{ foobar }}"; var bar = "{{ bazBar }}";',
                'snippetsData' => [
                    ['name' => 'foobar', 'value' => 'Value with {{ shared }}'],
                    ['name' => 'bazBar', 'value' => 'Another value with {{ shared }}'],
                    ['name' => 'shared', 'value' => 'Shared value'],
                ],
                'expectedSnippets' => ['foobar', 'bazBar', 'shared'],
            ],
            'nonExistentSnippet' => [
                'bannerJs' => 'var foo = "{{ existingSnippet }}"; var bar = "{{ nonExistent }}";',
                'snippetsData' => [
                    ['name' => 'existingSnippet', 'value' => 'test value'],
                ],
                'expectedSnippets' => ['existingSnippet', 'nonExistent'],
            ],
            'variousWhitespace' => [
                'bannerJs' => 'var foo = "{{foobar}}"; var bar = "{{ bazBar }}"; var baz = "{{ qux}}";',
                'snippetsData' => [
                    ['name' => 'foobar', 'value' => 'test value'],
                    ['name' => 'bazBar', 'value' => 'another value'],
                    ['name' => 'qux', 'value' => 'third value'],
                ],
                'expectedSnippets' => ['foobar', 'bazBar', 'qux'],
            ],
        ];
    }

    #[DataProvider('snippetExtractionProvider')]
    public function testGetUsedSnippetCodes(string $bannerJs, array $snippetsData, array $expectedSnippets)
    {
        $banner = Banner::factory()->create(['js' => $bannerJs]);

        foreach ($snippetsData as $snippetData) {
            Snippet::create($snippetData);
        }

        $snippets = $banner->getUsedSnippetCodes();

        $this->assertCount(count($expectedSnippets), $snippets);
        foreach ($expectedSnippets as $expectedSnippet) {
            $this->assertContains($expectedSnippet, $snippets);
        }
    }
}
