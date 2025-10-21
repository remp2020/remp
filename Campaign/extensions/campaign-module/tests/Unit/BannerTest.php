<?php

namespace Remp\CampaignModule\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\CampaignModule\Banner;
use Remp\CampaignModule\HtmlTemplate;
use Remp\CampaignModule\BarTemplate;
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

    public function testGetUsedSnippetCodesFromHtmlTemplateText()
    {
        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_HTML,
            'js' => null,
        ]);

        // Delete factory's default template
        HtmlTemplate::where('banner_id', $banner->id)->delete();

        $template = new HtmlTemplate([
            'text' => '<p>Hello {{ userName }}, welcome to {{ siteName }}!</p>',
            'css' => '.banner { color: red; }',
            'dimensions' => 'medium',
            'text_align' => 'left',
            'text_color' => '#000',
            'font_size' => '14',
            'background_color' => '#fff',
        ]);
        $template->banner_id = $banner->id;
        $template->save();

        Snippet::create(['name' => 'userName', 'value' => 'John']);
        Snippet::create(['name' => 'siteName', 'value' => 'Example.com']);

        $snippets = $banner->fresh()->getUsedSnippetCodes();

        $this->assertCount(2, $snippets);
        $this->assertContains('userName', $snippets);
        $this->assertContains('siteName', $snippets);
    }

    public function testGetUsedSnippetCodesFromHtmlTemplateCss()
    {
        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_HTML,
            'js' => null,
        ]);

        // Delete factory's default template
        HtmlTemplate::where('banner_id', $banner->id)->delete();

        $template = new HtmlTemplate([
            'text' => '<p>Simple text</p>',
            'css' => '.banner { background-image: url("{{ backgroundUrl }}"); color: {{ textColor }}; }',
            'dimensions' => 'medium',
            'text_align' => 'left',
            'text_color' => '#000',
            'font_size' => '14',
            'background_color' => '#fff',
        ]);
        $template->banner_id = $banner->id;
        $template->save();

        Snippet::create(['name' => 'backgroundUrl', 'value' => 'https://example.com/bg.jpg']);
        Snippet::create(['name' => 'textColor', 'value' => '#333']);

        $snippets = $banner->fresh()->getUsedSnippetCodes();

        $this->assertCount(2, $snippets);
        $this->assertContains('backgroundUrl', $snippets);
        $this->assertContains('textColor', $snippets);
    }

    public function testGetUsedSnippetCodesFromBarTemplate()
    {
        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_BAR,
            'js' => null,
        ]);

        $template = new BarTemplate([
            'main_text' => 'Check out {{ offerName }}!',
            'button_text' => '{{ ctaText }}',
        ]);
        $template->banner_id = $banner->id;
        $template->save();

        Snippet::create(['name' => 'offerName', 'value' => 'Special Offer']);
        Snippet::create(['name' => 'ctaText', 'value' => 'Click Here']);

        $snippets = $banner->fresh()->getUsedSnippetCodes();

        $this->assertCount(2, $snippets);
        $this->assertContains('offerName', $snippets);
        $this->assertContains('ctaText', $snippets);
    }

    public function testGetUsedSnippetCodesFromTemplateWithNestedSnippets()
    {
        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_HTML,
            'js' => null,
        ]);

        // Delete factory's default template
        HtmlTemplate::where('banner_id', $banner->id)->delete();

        $template = new HtmlTemplate([
            'text' => '<p>{{ greeting }}</p>',
            'css' => '.banner { color: {{ primaryColor }}; }',
            'dimensions' => 'medium',
            'text_align' => 'left',
            'text_color' => '#000',
            'font_size' => '14',
            'background_color' => '#fff',
        ]);
        $template->banner_id = $banner->id;
        $template->save();

        Snippet::create(['name' => 'greeting', 'value' => 'Hello {{ userName }}']);
        Snippet::create(['name' => 'userName', 'value' => 'John']);
        Snippet::create(['name' => 'primaryColor', 'value' => '{{ brandColor }}']);
        Snippet::create(['name' => 'brandColor', 'value' => '#ff0000']);

        $snippets = $banner->fresh()->getUsedSnippetCodes();

        $this->assertCount(4, $snippets);
        $this->assertContains('greeting', $snippets);
        $this->assertContains('userName', $snippets);
        $this->assertContains('primaryColor', $snippets);
        $this->assertContains('brandColor', $snippets);
    }

    public function testGetUsedSnippetCodesFromBothJsAndTemplate()
    {
        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_HTML,
            'js' => 'console.log("{{ jsSnippet }}");',
        ]);

        // Delete factory's default template
        HtmlTemplate::where('banner_id', $banner->id)->delete();

        $template = new HtmlTemplate([
            'text' => '<p>{{ textSnippet }}</p>',
            'css' => '.banner { color: {{ cssSnippet }}; }',
            'dimensions' => 'medium',
            'text_align' => 'left',
            'text_color' => '#000',
            'font_size' => '14',
            'background_color' => '#fff',
        ]);
        $template->banner_id = $banner->id;
        $template->save();

        Snippet::create(['name' => 'jsSnippet', 'value' => 'JS Value']);
        Snippet::create(['name' => 'textSnippet', 'value' => 'Text Value']);
        Snippet::create(['name' => 'cssSnippet', 'value' => 'red']);

        $snippets = $banner->fresh()->getUsedSnippetCodes();

        $this->assertCount(3, $snippets);
        $this->assertContains('jsSnippet', $snippets);
        $this->assertContains('textSnippet', $snippets);
        $this->assertContains('cssSnippet', $snippets);
    }

    public function testGetUsedSnippetCodesFromJsIncludes()
    {
        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_HTML,
            'js_includes' => ['{{ cdn_domain }}/main.js', '{{ cdn_domain }}{{ theme }}.js'],
        ]);

        Snippet::create(['name' => 'cdn_domain', 'value' => '{{ protocol}}://remp.press']);
        Snippet::create(['name' => 'theme', 'value' => 'dark']);
        Snippet::create(['name' => 'protocol', 'value' => 'https']);

        $snippets = $banner->fresh()->getUsedSnippetCodes();

        $this->assertCount(3, $snippets);
        $this->assertContains('cdn_domain', $snippets);
        $this->assertContains('theme', $snippets);
        $this->assertContains('protocol', $snippets);
    }

    public function testGetUsedSnippetCodesFromCssIncludes()
    {
        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_HTML,
            'css_includes' => ['{{ cdn_domain }}/main.css', '{{ cdn_domain }}{{ theme }}.css'],
        ]);

        Snippet::create(['name' => 'cdn_domain', 'value' => '{{ protocol}}://remp.press']);
        Snippet::create(['name' => 'theme', 'value' => 'dark']);
        Snippet::create(['name' => 'protocol', 'value' => 'https']);

        $snippets = $banner->fresh()->getUsedSnippetCodes();

        $this->assertCount(3, $snippets);
        $this->assertContains('cdn_domain', $snippets);
        $this->assertContains('theme', $snippets);
        $this->assertContains('protocol', $snippets);
    }
}
