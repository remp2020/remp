<?php

namespace App\Model\Pageviews;

use DB;
use Illuminate\Database\Query\Builder;

class TopSearch
{
    public function topArticles(
        $timeFrom,
        $limit,
        string $sectionValueType = null,
        ?array $sectionValues = null,
        string $authorValueType = null,
        ?array $authorValues = null,
        ?string $contentType = null,
        string $tagValueType = null,
        ?array $tagValues = null
    ) {
        $pageviewsQuery = DB::table('article_pageviews')
            ->where('article_pageviews.time_from', '>=', $timeFrom)
            ->groupBy('article_pageviews.article_id')
            ->select(
                'article_pageviews.article_id',
                DB::raw('CAST(SUM(article_pageviews.sum) AS UNSIGNED) as pageviews')
            )
            ->orderBy('pageviews', 'DESC')
            ->limit($limit);

        if ($sectionValueType && $sectionValues) {
            $this->addSectionsCondition($pageviewsQuery, $sectionValueType, $sectionValues);
        }
        if ($authorValueType && $authorValues) {
            $this->addAuthorsCondition($pageviewsQuery, $authorValueType, $authorValues);
        }
        if ($contentType) {
            $this->addContentTypeCondition($pageviewsQuery, $contentType);
        }
        if ($tagValueType && $tagValues) {
            $this->addTagsCondition($pageviewsQuery, $tagValueType, $tagValues);
        }

        $data = DB::table('articles')
            ->joinSub($pageviewsQuery, 'top_articles_by_pageviews', function ($join) {
                $join->on('articles.id', '=', 'top_articles_by_pageviews.article_id');
            })
            ->select('articles.id', 'articles.external_id', 'top_articles_by_pageviews.pageviews');

        return $data->get();
    }

    public function topAuthors(
        $timeFrom,
        $limit,
        string $sectionValueType = null,
        ?array $sectionValues = null,
        ?string $contentType = null,
        string $tagValueType = null,
        ?array $tagValues = null
    ) {
        $topAuthorsQuery = DB::table('article_pageviews')
            ->where('article_pageviews.time_from', '>=', $timeFrom)
            ->join('article_author', 'article_pageviews.article_id', '=', 'article_author.article_id')
            ->groupBy(['article_author.author_id'])
            ->select('article_author.author_id', DB::raw('CAST(SUM(article_pageviews.sum) AS UNSIGNED) as pageviews'))
            ->orderBy('pageviews', 'DESC')
            ->limit($limit);

        if ($sectionValueType && $sectionValues) {
            $this->addSectionsCondition($topAuthorsQuery, $sectionValueType, $sectionValues);
        }
        if ($contentType) {
            $this->addContentTypeCondition($topAuthorsQuery, $contentType);
        }
        if ($tagValueType && $tagValues) {
            $this->addTagsCondition($topAuthorsQuery, $tagValueType, $tagValues);
        }

        $data = DB::table('authors')
            ->joinSub($topAuthorsQuery, 'top_authors', function ($join) {
                $join->on('authors.id', '=', 'top_authors.author_id');
            })
            ->select('authors.external_id', 'authors.name', 'top_authors.pageviews');

        return $data->get();
    }

    public function topPostTags(
        $timeFrom,
        $limit,
        string $sectionValueType = null,
        ?array $sectionValues = null,
        string $authorValueType = null,
        ?array $authorValues = null,
        ?string $contentType = null
    ) {
        $topTagsQuery = DB::table('article_pageviews')
            ->where('article_pageviews.time_from', '>=', $timeFrom)
            ->join('article_tag', 'article_pageviews.article_id', '=', 'article_tag.article_id')
            ->groupBy(['article_tag.tag_id'])
            ->select('article_tag.tag_id', DB::raw('CAST(SUM(article_pageviews.sum) AS UNSIGNED) as pageviews'))
            ->orderBy('pageviews', 'DESC')
            ->limit($limit);

        if ($sectionValueType && $sectionValues) {
            $this->addSectionsCondition($topTagsQuery, $sectionValueType, $sectionValues);
        }
        if ($authorValueType && $authorValues) {
            $this->addAuthorsCondition($topTagsQuery, $authorValueType, $authorValues);
        }
        if ($contentType) {
            $this->addContentTypeCondition($topTagsQuery, $contentType);
        }

        $data = DB::table('tags')
            ->joinSub($topTagsQuery, 'top_tags', function ($join) {
                $join->on('tags.id', '=', 'top_tags.tag_id');
            })
            ->select('tags.id', 'tags.name', 'tags.external_id', 'top_tags.pageviews');

        return $data->get();
    }

    private function addSectionsCondition(Builder $articlePageviewsQuery, string $type, array $values): void
    {
        if (!in_array($type, ['external_id', 'name'], true)) {
            throw new \Exception("type '$type' is not one of 'external_id' or 'name' values");
        }

        $articlePageviewsQuery->join('article_section', 'article_pageviews.article_id', '=', 'article_section.article_id')
            ->join('sections', 'article_section.section_id', '=', 'sections.id')
            ->whereIn('sections.' . $type, $values);
    }

    private function addAuthorsCondition(Builder $articlePageviewsQuery, string $type, array $values): void
    {
        if (!in_array($type, ['external_id', 'name'], true)) {
            throw new \Exception("type '$type' is not one of 'external_id' or 'name' values");
        }

        $articlePageviewsQuery->join('article_author', 'article_pageviews.article_id', '=', 'article_author.article_id')
            ->join('authors', 'article_author.author_id', '=', 'authors.id')
            ->whereIn('authors.' . $type, $values);
    }

    private function addContentTypeCondition(Builder $articlePageviewsQuery, string $contentType): void
    {
        $articlePageviewsQuery->join('articles', 'article_pageviews.article_id', '=', 'articles.id')
            ->where('articles.content_type', '=', $contentType);
    }

    private function addTagsCondition(Builder $articlePageviewsQuery, string $type, array $values): void
    {
        $articlePageviewsQuery->join('article_tag', 'article_pageviews.article_id', '=', 'article_tag.article_id')
            ->join('tags', 'article_tag.tag_id', '=', 'tags.id')
            ->whereIn('tags.' . $type, $values);
    }
}
