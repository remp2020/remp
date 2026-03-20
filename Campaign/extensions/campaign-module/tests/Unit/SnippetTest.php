<?php

namespace Remp\CampaignModule\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\CampaignModule\Banner;
use Remp\CampaignModule\HtmlTemplate;
use Remp\CampaignModule\Models\Snippet\SnippetUsages;
use Remp\CampaignModule\Snippet;
use Remp\CampaignModule\Tests\TestCase;

class SnippetTest extends TestCase
{
    use RefreshDatabase;

    public function testNoUsagesReturnsEmptyCollections()
    {
        $snippet = Snippet::create(['name' => 'unused', 'value' => 'some value']);

        $this->assertCount(0, SnippetUsages::for($snippet)->inBanners());
        $this->assertCount(0, SnippetUsages::for($snippet)->inSnippets());
    }

    public function testSnippetUsedInBannerJs()
    {
        $snippet = Snippet::create(['name' => 'mySnippet', 'value' => 'hello']);

        $banner = Banner::factory()->create(['js' => 'var x = "{{ mySnippet }}";']);

        $banners = SnippetUsages::for($snippet)->inBanners();

        $this->assertCount(1, $banners);
        $this->assertEquals($banner->id, $banners->first()->id);
    }

    public function testSnippetUsedInBannerJsIncludes()
    {
        $snippet = Snippet::create(['name' => 'cdn_url', 'value' => 'https://cdn.example.com']);

        $banner = Banner::factory()->create([
            'js_includes' => ['{{ cdn_url }}/main.js'],
        ]);

        $banners = SnippetUsages::for($snippet)->inBanners();

        $this->assertCount(1, $banners);
        $this->assertEquals($banner->id, $banners->first()->id);
    }

    public function testSnippetUsedInHtmlTemplateText()
    {
        $snippet = Snippet::create(['name' => 'greeting', 'value' => 'Hello world']);

        $banner = Banner::factory()->create([
            'template' => Banner::TEMPLATE_HTML,
            'js' => null,
        ]);

        HtmlTemplate::where('banner_id', $banner->id)->delete();

        $template = new HtmlTemplate([
            'text' => '<p>{{ greeting }}</p>',
            'css' => '',
            'dimensions' => 'medium',
            'text_align' => 'left',
            'text_color' => '#000',
            'font_size' => '14',
            'background_color' => '#fff',
        ]);
        $template->banner_id = $banner->id;
        $template->save();

        $banners = SnippetUsages::for($snippet)->inBanners();

        $this->assertCount(1, $banners);
        $this->assertEquals($banner->id, $banners->first()->id);
    }

    public function testSnippetUsedInAnotherSnippet()
    {
        $inner = Snippet::create(['name' => 'inner', 'value' => 'inner value']);
        Snippet::create(['name' => 'outer', 'value' => 'uses {{ inner }} here']);

        $this->assertCount(0, SnippetUsages::for($inner)->inBanners());
        $this->assertCount(1, SnippetUsages::for($inner)->inSnippets());
        $this->assertEquals('outer', SnippetUsages::for($inner)->inSnippets()->first()->name);
    }

    public function testVariousWhitespacePatterns()
    {
        $snippet = Snippet::create(['name' => 'test', 'value' => 'value']);

        Banner::factory()->create(['js' => '{{test}}']);
        Banner::factory()->create(['js' => '{{ test }}']);
        Banner::factory()->create(['js' => '{{test }}']);
        Banner::factory()->create(['js' => '{{  test  }}']);
        Banner::factory()->create(['js' => '{{   test   }}']);

        $this->assertCount(5, SnippetUsages::for($snippet)->inBanners());
    }

    public function testSelfReferenceExcluded()
    {
        $snippet = Snippet::create(['name' => 'self', 'value' => 'uses {{ self }}']);

        $this->assertCount(0, SnippetUsages::for($snippet)->inSnippets());
    }

    public function testNoFalsePositiveForSimilarNames()
    {
        $snippet = Snippet::create(['name' => 'price', 'value' => '100']);

        Banner::factory()->create(['js' => '{{ price_annual }}']);
        Snippet::create(['name' => 'price_annual', 'value' => '1200']);

        $this->assertCount(0, SnippetUsages::for($snippet)->inBanners());
        $this->assertCount(0, SnippetUsages::for($snippet)->inSnippets());
    }

    public function testBannersIncludeCampaigns()
    {
        $snippet = Snippet::create(['name' => 'test', 'value' => 'value']);

        Banner::factory()->create(['js' => '{{ test }}']);

        $this->assertTrue(SnippetUsages::for($snippet)->inBanners()->first()->relationLoaded('campaigns'));
    }

    public function testTransitiveBannerDiscovery()
    {
        // cdn_domain → analytics_init → banner
        $cdnDomain = Snippet::create(['name' => 'cdn_domain', 'value' => 'https://cdn.example.com']);
        Snippet::create(['name' => 'analytics_init', 'value' => '<script src="{{ cdn_domain }}/analytics.js"></script>']);

        $banner = Banner::factory()->create(['js' => '{{ analytics_init }}']);

        $banners = SnippetUsages::for($cdnDomain)->inBanners();

        $this->assertCount(1, $banners);
        $this->assertEquals($banner->id, $banners->first()->id);
    }

    public function testTransitiveThreeLevelChain()
    {
        // level1 → level2 → level3 → banner
        $level1 = Snippet::create(['name' => 'level1', 'value' => 'base value']);
        Snippet::create(['name' => 'level2', 'value' => 'uses {{ level1 }}']);
        Snippet::create(['name' => 'level3', 'value' => 'uses {{ level2 }}']);

        $banner = Banner::factory()->create(['js' => '{{ level3 }}']);

        $banners = SnippetUsages::for($level1)->inBanners();

        $this->assertCount(1, $banners);
        $this->assertEquals($banner->id, $banners->first()->id);
    }

    public function testCircularReferenceProtection()
    {
        // Create circular: a → b → a
        Snippet::create(['name' => 'snippet_a', 'value' => 'uses {{ snippet_b }}']);
        $snippetB = Snippet::create(['name' => 'snippet_b', 'value' => 'uses {{ snippet_a }}']);

        // Should not infinite loop
        $banners = SnippetUsages::for($snippetB)->inBanners();

        $this->assertCount(0, $banners);
    }

    public function testTransitiveAndDirectBannerUsage()
    {
        // inner is used directly in banner1 AND transitively via outer in banner2
        $inner = Snippet::create(['name' => 'inner', 'value' => 'value']);
        Snippet::create(['name' => 'outer', 'value' => '{{ inner }}']);

        $banner1 = Banner::factory()->create(['js' => '{{ inner }}']);
        $banner2 = Banner::factory()->create(['js' => '{{ outer }}']);

        $banners = SnippetUsages::for($inner)->inBanners();

        $this->assertCount(2, $banners);
        $this->assertEqualsCanonicalizing(
            [$banner1->id, $banner2->id],
            $banners->pluck('id')->all()
        );
    }

    public function testDirectVsTransitiveBannerClassification()
    {
        $inner = Snippet::create(['name' => 'inner', 'value' => 'value']);
        Snippet::create(['name' => 'outer', 'value' => '{{ inner }}']);

        $directBanner = Banner::factory()->create(['js' => '{{ inner }}']);
        $transitiveBanner = Banner::factory()->create(['js' => '{{ outer }}']);

        $banners = SnippetUsages::for($inner)->inBanners()->keyBy('id');

        $this->assertTrue($banners[$directBanner->id]->usageDirect);
        $this->assertNull($banners[$directBanner->id]->usageVia);

        $this->assertFalse($banners[$transitiveBanner->id]->usageDirect);
        $this->assertInstanceOf(Snippet::class, $banners[$transitiveBanner->id]->usageVia);
        $this->assertEquals('outer', $banners[$transitiveBanner->id]->usageVia->name);
    }
}
