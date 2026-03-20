<?php

namespace Remp\CampaignModule\Models\Snippet;

use Illuminate\Support\Collection;
use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Snippet;

class SnippetUsages
{
    private Snippet $snippet;
    private Collection $snippets;

    public function __construct(Snippet $snippet)
    {
        $this->snippet = $snippet;
        $this->snippets = Snippet::where('id', '!=', $snippet->id)->get();
    }

    public static function for(Snippet $snippet): self
    {
        return new self($snippet);
    }

    /**
     * @return string[]
     */
    public static function extractSnippetCodes(string $content): array
    {
        $matched = preg_match_all('/{{\s*(.*?)\s*}}/', $content, $matches, PREG_PATTERN_ORDER);
        return is_int($matched) && $matched > 0 ? $matches[1] : [];
    }

    /**
     * @return Collection<int, Snippet>
     */
    public function inSnippets(): Collection
    {
        return $this->snippets->filter(
            fn(Snippet $s) => in_array($this->snippet->name, self::extractSnippetCodes($s->value ?? ''), true)
        )->values();
    }

    /**
     * Each banner in the returned collection has usageDirect (bool) and usageVia (?Snippet) attributes set.
     * usageDirect is true when the banner directly references this snippet by name.
     * usageVia is the intermediate Snippet model for transitive references.
     *
     * @return Collection<int, Banner>
     */
    public function inBanners(): Collection
    {
        $allNames = $this->resolveTransitiveNames($this->snippet, $this->snippets);
        $transitiveNames = array_slice($allNames, 1);
        $snippetsByName = $this->snippets->keyBy('name');

        $allBanners = Banner::all();

        foreach ($allBanners->groupBy('template') as $bannerGroup) {
            $bannerGroup->load($bannerGroup->first()->getTemplateRelationName());
        }

        $matchingBanners = $allBanners->filter(function (Banner $banner) use ($allNames, $transitiveNames, $snippetsByName) {
            $codes = $banner->getDirectSnippetCodes();
            if (empty(array_intersect($codes, $allNames))) {
                return false;
            }

            if (in_array($this->snippet->name, $codes, true)) {
                $banner->setAttribute('usageDirect', true);
                $banner->setAttribute('usageVia', null);
            } else {
                $transitiveMatches = array_values(array_intersect($codes, $transitiveNames));
                $viaName = $transitiveMatches[0] ?? null;
                $banner->setAttribute('usageDirect', false);
                $banner->setAttribute('usageVia', $snippetsByName[$viaName] ?? null);
            }

            return true;
        });

        $matchingBanners->load('campaigns');
        return $matchingBanners->values();
    }

    /**
     * @return string[]
     */
    private function resolveTransitiveNames(Snippet $snippet, Collection $snippets): array
    {
        $usedBy = [];
        foreach ($snippets as $s) {
            foreach (self::extractSnippetCodes($s->value ?? '') as $referencedName) {
                $usedBy[$referencedName][] = $s;
            }
        }

        $allNames = [$snippet->name];
        $visited = [$snippet->name => true];
        $queue = [$snippet->name];

        while (!empty($queue)) {
            $current = array_shift($queue);
            foreach ($usedBy[$current] ?? [] as $parent) {
                if (!isset($visited[$parent->name])) {
                    $visited[$parent->name] = true;
                    $allNames[] = $parent->name;
                    $queue[] = $parent->name;
                }
            }
        }

        return $allNames;
    }
}
