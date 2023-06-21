<?php

namespace Remp\BeamModule\Model\Charts;

use Remp\BeamModule\Helpers\Colors;
use Remp\BeamModule\Helpers\Journal\JournalHelpers;
use Illuminate\Support\Collection;
use Remp\Journal\JournalContract;

class ConversionsSankeyDiagram
{
    const NODE_ARTICLES = 'articles';
    const NODE_TITLE = 'homepage + other';
    const NODE_PURCHASE = 'purchase';

    private $conversionSources;
    private $conversionSourceType;
    private $journalHelper;

    public $nodes = [];
    public $links = [];
    public $nodeColors = [];

    public function __construct(JournalContract $journal, Collection $conversionSources, string $conversionSourceType)
    {
        $this->conversionSources = $conversionSources;
        $this->conversionSourceType = $conversionSourceType;
        $this->journalHelper = new JournalHelpers($journal);

        $this->retrieveNodesAndLinks();
        if (!empty($this->nodes)) {
            $nodeNames = array_column($this->nodes, 'name');
            $this->nodeColors = Colors::assignColorsToMediumRefers($nodeNames, true);
            $this->nodeColors[self::NODE_TITLE] = '#e05767';
            $this->nodeColors[self::NODE_ARTICLES] = '#e05767';
            $this->nodeColors[self::NODE_PURCHASE] = '#b71e2d';
        }
    }

    private function retrieveNodesAndLinks()
    {
        $conversionSourcesByType = $this->conversionSources->where('type', $this->conversionSourceType);
        $conversionSourcesByMedium = $conversionSourcesByType->groupBy('referer_medium');
        $conversionsCount = $conversionSourcesByType->count();
        $totalArticlesCount = $totalTitlesCount = 0;

        foreach ($conversionSourcesByMedium as $medium => $conversionSources) {
            $medium = $this->journalHelper->refererMediumLabel($medium);

            $articlesCount = $conversionSources->filter(function ($conversionSource) {
                return $conversionSource->article_id !== null;
            })->count();
            $titlesCount = $conversionSources->count() - $articlesCount;

            $this->addNodesAndLinks($medium, self::NODE_ARTICLES, $articlesCount / $conversionsCount * 100);
            $this->addNodesAndLinks($medium, self::NODE_TITLE, $titlesCount / $conversionsCount * 100);
            $totalArticlesCount += $articlesCount;
            $totalTitlesCount += $titlesCount;
        }

        if ($conversionsCount > 0) {
            if ($totalArticlesCount > 0) {
                $this->addNodesAndLinks(self::NODE_ARTICLES, self::NODE_PURCHASE, $totalArticlesCount / $conversionsCount * 100);
            }
            if ($totalTitlesCount > 0) {
                $this->addNodesAndLinks(self::NODE_TITLE, self::NODE_PURCHASE, $totalTitlesCount / $conversionsCount * 100);
            }
        }
    }

    private function addNodesAndLinks(string $source, string $target, float $connectionValue)
    {
        if (!$connectionValue) {
            return;
        }

        $this->addNode($source);
        $this->addNode($target);

        $this->links[] = [
            'source' => $source,
            'target' => $target,
            'value' => $connectionValue
        ];
    }

    private function addNode(string $nodeName)
    {
        if (!in_array($nodeName, array_column($this->nodes, 'name'))) {
            $this->nodes[] = ['name' => $nodeName];
        }
    }
}
