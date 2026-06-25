package model

import (
	"encoding/json"
	"fmt"
	"log"

	"github.com/elastic/go-elasticsearch/v8/typedapi/core/search"
	"github.com/elastic/go-elasticsearch/v8/typedapi/types"
)

// queryBinding represents information about where and how the data should be fetched.
type elasticsearchQueryBinding struct {
	Index string
	Field string
}

// PageviewElasticsearch is official ElasticsearchDB implementation of PageviewStorage.
type PageviewElasticsearch struct {
	DB            *ElasticsearchDB
	actionsCached map[string][]string
}

// Count returns number of Pageviews matching the filter defined by PageviewOptions.
func (pDB *PageviewElasticsearch) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	// pageview events are stored in multiple measurements which need to be resolved
	binding, err := pDB.resolveQueryBindings(options.Action)
	if err != nil {
		return nil, false, err
	}

	index := pDB.DB.resolveIndex(binding.Index)

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	options.Action = ""

	query, err := pDB.DB.BuildBoolQuery(index, options)
	if err != nil {
		return nil, false, err
	}

	var afterKey types.CompositeAggregateKey
	var crc CountRowCollection

	for {
		aggs, aggregationAdded, err := pDB.DB.BuildCompositeAggregation(index, afterKey, options)
		if err != nil {
			return nil, false, err
		}

		size := 0
		req := &search.Request{
			Query: query,
			Size:  &size,
		}

		if !aggregationAdded {
			// allow to compute more than 10000 hits (default value) in case there is no aggregation
			req.TrackTotalHits = true
		} else {
			req.Aggregations = aggs
		}

		res, err := pDB.DB.SearchTyped(index, req)
		if err != nil {
			return nil, false, err
		}

		if !aggregationAdded {
			var count int
			if res.Hits.Total != nil {
				count = int(res.Hits.Total.Value)
			}
			return CountRowCollection{CountRow{Count: count}}, true, nil
		}

		// Get composite aggregate from response
		rawAgg, exists := res.Aggregations["buckets"]
		if !exists {
			return nil, false, fmt.Errorf("unexpected aggregation response: missing 'buckets' aggregation")
		}
		compositeAgg, ok := rawAgg.(*types.CompositeAggregate)
		if !ok {
			return nil, false, fmt.Errorf("unexpected aggregation response: expected CompositeAggregate, got %T", rawAgg)
		}

		crcPage, err := pDB.DB.countRowCollectionFromTypedCompositeBuckets(compositeAgg, options)
		if err != nil {
			return nil, false, err
		}
		crc = append(crc, crcPage...)

		// More pages — use AfterKey for next request; nil AfterKey means last page (mirrors old implementation).
		afterKey = compositeAgg.AfterKey
		if len(afterKey) == 0 {
			break
		}
	}

	return crc, true, nil
}

// Sum returns number of Pageviews matching the filter defined by AggregateOptions.
func (pDB *PageviewElasticsearch) Sum(options AggregateOptions) (SumRowCollection, bool, error) {
	// pageview events are stored in multiple measurements which need to be resolved
	binding, err := pDB.resolveQueryBindings(options.Action)
	if err != nil {
		return nil, false, err
	}

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	options.Action = ""

	index := pDB.DB.resolveIndex(binding.Index)
	sumField := binding.Field
	targetAgg := fmt.Sprintf("%s_sum", sumField)

	query, err := pDB.DB.BuildBoolQuery(index, options)
	if err != nil {
		return nil, false, err
	}

	extras := map[string]types.Aggregations{
		targetAgg: {
			Sum: &types.SumAggregation{
				Field: &sumField,
			},
		},
	}

	var dateHistAgg *types.DateHistogramAggregation
	if options.TimeHistogram != nil {
		tz := "UTC"
		if options.TimeHistogram.TimeZone != nil {
			tz = options.TimeHistogram.TimeZone.String()
		}
		field := "time"
		interval := options.TimeHistogram.Interval
		dateHistAgg = &types.DateHistogramAggregation{
			Field:         &field,
			FixedInterval: interval,
			TimeZone:      &tz,
		}
	}

	aggs, aggregationAdded, err := pDB.DB.BuildGroupByAggregation(index, options, extras, dateHistAgg)
	if err != nil {
		return nil, false, err
	}

	size := 0
	req := &search.Request{
		Query: query,
		Size:  &size,
	}
	if aggregationAdded {
		req.Aggregations = aggs
	} else {
		req.TrackTotalHits = true
	}

	res, err := pDB.DB.SearchTyped(index, req)
	if err != nil {
		return nil, false, err
	}

	return pDB.DB.sumRowCollectionFromTypedResponse(res, options, targetAgg, sumField)
}

// Avg returns average count of Pageviews/Timespent records matching the filter defined by AggregateOptions.
func (pDB *PageviewElasticsearch) Avg(options AggregateOptions) (AvgRowCollection, bool, error) {
	// pageview events are stored in multiple measurements which need to be resolved
	binding, err := pDB.resolveQueryBindings(options.Action)
	if err != nil {
		return nil, false, err
	}

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	options.Action = ""

	index := pDB.DB.resolveIndex(binding.Index)
	avgField := binding.Field
	targetAgg := fmt.Sprintf("%s_avg", avgField)

	query, err := pDB.DB.BuildBoolQuery(index, options)
	if err != nil {
		return nil, false, err
	}

	extras := map[string]types.Aggregations{
		targetAgg: {
			Avg: &types.AverageAggregation{
				Field: &avgField,
			},
		},
	}

	var dateHistAgg *types.DateHistogramAggregation
	if options.TimeHistogram != nil {
		tz := "UTC"
		if options.TimeHistogram.TimeZone != nil {
			tz = options.TimeHistogram.TimeZone.String()
		}
		field := "time"
		interval := options.TimeHistogram.Interval
		dateHistAgg = &types.DateHistogramAggregation{
			Field:         &field,
			FixedInterval: interval,
			TimeZone:      &tz,
		}
	}

	aggs, aggregationAdded, err := pDB.DB.BuildGroupByAggregation(index, options, extras, dateHistAgg)
	if err != nil {
		return nil, false, err
	}

	size := 0
	req := &search.Request{
		Query: query,
		Size:  &size,
	}
	if aggregationAdded {
		req.Aggregations = aggs
	} else {
		req.TrackTotalHits = true
	}

	res, err := pDB.DB.SearchTyped(index, req)
	if err != nil {
		return nil, false, err
	}

	return pDB.DB.avgRowCollectionFromTypedResponse(res, options, targetAgg, avgField)
}

// Unique returns unique count of Pageviews records matching the filter defined by AggregateOptions.
func (pDB *PageviewElasticsearch) Unique(options AggregateOptions, item string) (CountRowCollection, bool, error) {
	var binding elasticsearchQueryBinding
	switch item {
	case UniqueCountBrowsers:
		binding = elasticsearchQueryBinding{
			Index: TablePageviews,
			Field: "browser_id.keyword",
		}
	case UniqueCountUsers:
		binding = elasticsearchQueryBinding{
			Index: TablePageviews,
			Field: "user_id.keyword",
		}
	default:
		return nil, false, fmt.Errorf("unable to count uniques for item [%s] ", item)
	}

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	options.Action = ""

	index := pDB.DB.resolveIndex(binding.Index)
	uniqueField := binding.Field
	targetAgg := fmt.Sprintf("%s_unique", uniqueField)

	query, err := pDB.DB.BuildBoolQuery(index, options)
	if err != nil {
		return nil, false, err
	}

	extras := map[string]types.Aggregations{
		targetAgg: {
			Cardinality: &types.CardinalityAggregation{
				Field: &uniqueField,
			},
		},
	}

	var dateHistAgg *types.DateHistogramAggregation
	if options.TimeHistogram != nil {
		tz := "UTC"
		if options.TimeHistogram.TimeZone != nil {
			tz = options.TimeHistogram.TimeZone.String()
		}
		field := "time"
		interval := options.TimeHistogram.Interval
		dateHistAgg = &types.DateHistogramAggregation{
			Field:         &field,
			FixedInterval: interval,
			TimeZone:      &tz,
		}
	}

	aggs, aggregationAdded, err := pDB.DB.BuildGroupByAggregation(index, options, extras, dateHistAgg)
	if err != nil {
		return nil, false, err
	}

	size := 0
	req := &search.Request{
		Query: query,
		Size:  &size,
	}
	if aggregationAdded {
		req.Aggregations = aggs
	} else {
		req.TrackTotalHits = true
	}

	res, err := pDB.DB.SearchTyped(index, req)
	if err != nil {
		return nil, false, err
	}

	return pDB.DB.uniqueRowCollectionFromTypedResponse(res, options, targetAgg, uniqueField)
}

// List returns list of all Pageviews based on given PageviewOptions.
func (pDB *PageviewElasticsearch) List(options ListPageviewsOptions) (PageviewRowCollection, error) {
	var prc PageviewRowCollection

	index := pDB.DB.resolveIndex("pageviews")

	// prepare PageviewRow buckets
	prBuckets := make(map[string]*PageviewRow)
	pageviewIDs := []string{}

	// Use TypedAPI for search_after pagination with PIT for consistency
	batchSize := pDB.DB.BatchSize

	// Build query using TypedAPI
	query, err := pDB.DB.BuildBoolQuery(index, options.AggregateOptions)
	if err != nil {
		return nil, err
	}

	pitID, err := pDB.DB.OpenPIT(index, "5m")
	if err != nil {
		return nil, err
	}
	defer func() {
		if cerr := pDB.DB.ClosePIT(pitID); cerr != nil {
			log.Printf("warn: failed to close PIT: %v", cerr)
		}
	}()

	type pageviewBatchResult struct {
		response *search.Response
		err      error
	}
	batchCh := make(chan *pageviewBatchResult, 1)

	go func() {
		defer close(batchCh)
		var searchAfter []types.FieldValue
		curPitID := pitID
		for {
			searchResponse, ferr := pDB.DB.SearchAfterTypedPIT(curPitID, "5m", query, batchSize, searchAfter, options.SelectFields)
			if ferr != nil {
				batchCh <- &pageviewBatchResult{nil, ferr}
				return
			}
			if searchResponse.PitId != nil {
				curPitID = *searchResponse.PitId
			}
			hits := searchResponse.Hits.Hits
			if len(hits) == 0 {
				return
			}
			batchCh <- &pageviewBatchResult{searchResponse, nil}
			searchAfter = hits[len(hits)-1].Sort
		}
	}()

	for batchResult := range batchCh {
		if batchResult.err != nil {
			return nil, batchResult.err
		}
		hits := batchResult.response.Hits.Hits
		for _, hit := range hits {
			// Single MarshalJSON call; reuse bytes for both unmarshal operations
			sourceBytes, err := hit.Source_.MarshalJSON()
			if err != nil {
				return nil, err
			}

			pv := &Pageview{}
			if err = json.Unmarshal(sourceBytes, pv); err != nil {
				return nil, err
			}

			if hit.Id_ != nil {
				pv.ID = *hit.Id_
			}

			var sourceMap map[string]interface{}
			if err = json.Unmarshal(sourceBytes, &sourceMap); err != nil {
				return nil, err
			}

			tags, key, err := pDB.DB.buildTagsFromSource(sourceMap, options.GroupBy, "pageview listing")
			if err != nil {
				return nil, err
			}

			pr, ok := prBuckets[key]
			if !ok {
				pr = &PageviewRow{
					Tags: tags,
				}
				prBuckets[key] = pr
			}

			if pv.ID != "" {
				pageviewIDs = append(pageviewIDs, pv.ID)
			}

			pr.Pageviews = append(pr.Pageviews, pv)
		}
	}

	// Load timespent
	timespentForPageviews := make(map[string]int)
	if len(pageviewIDs) > 0 && options.LoadTimespent {
		timespentForPageviews, err = pDB.loadTimespent(pageviewIDs)
		if err != nil {
			return nil, err
		}
	}

	// Load page and article progress
	articleProgressForPageviews := make(map[string]float32)
	pageProgressForPageviews := make(map[string]float32)
	if len(pageviewIDs) > 0 && options.LoadProgress {
		pageProgressForPageviews, articleProgressForPageviews, err = pDB.loadProgress(pageviewIDs)
		if err != nil {
			return nil, err
		}
	}

	for _, pr := range prBuckets {
		for _, pv := range pr.Pageviews {
			if timespent, ok := timespentForPageviews[pv.ID]; ok {
				pv.Timespent = timespent
			}
			if articleProgress, ok := articleProgressForPageviews[pv.ID]; ok {
				pv.ArticleProgress = articleProgress
			}
			if pageProgress, ok := pageProgressForPageviews[pv.ID]; ok {
				pv.PageProgress = pageProgress
			}
		}

		prc = append(prc, pr)
	}

	return prc, nil
}

func (pDB *PageviewElasticsearch) loadTimespent(pageviewIDs []string) (map[string]int, error) {
	index := pDB.DB.resolveIndex("pageviews_time_spent")

	ao := AggregateOptions{
		FilterBy: []*FilterBy{
			{
				Tag:     "remp_pageview_id",
				Values:  pageviewIDs,
				Inverse: false,
			},
		},
	}

	query, err := pDB.DB.BuildBoolQuery(index, ao)
	if err != nil {
		return nil, err
	}

	timespentForPageviews := make(map[string]int)
	var searchAfter []types.FieldValue
	batchSize := pDB.DB.BatchSize

	pitID, err := pDB.DB.OpenPIT(index, "5m")
	if err != nil {
		return nil, err
	}
	defer func() {
		if cerr := pDB.DB.ClosePIT(pitID); cerr != nil {
			log.Printf("warn: failed to close PIT: %v", cerr)
		}
	}()

	for {
		res, err := pDB.DB.SearchAfterTypedPIT(pitID, "5m", query, batchSize, searchAfter, []string{"timespent", "remp_pageview_id"})
		if err != nil {
			return nil, err
		}

		if res.PitId != nil {
			pitID = *res.PitId
		}

		if len(res.Hits.Hits) == 0 {
			break
		}

		for _, hit := range res.Hits.Hits {
			pv := &Pageview{}
			if err := UnmarshalHitSource(hit, pv); err != nil {
				return nil, err
			}

			if hit.Id_ != nil {
				timespentForPageviews[*hit.Id_] = pv.Timespent
			}

			searchAfter = hit.Sort
		}
	}

	return timespentForPageviews, nil
}

func (pDB *PageviewElasticsearch) loadProgress(pageviewIDs []string) (map[string]float32, map[string]float32, error) {
	index := pDB.DB.resolveIndex("pageviews_progress")

	ao := AggregateOptions{
		FilterBy: []*FilterBy{
			{
				Tag:     "remp_pageview_id",
				Values:  pageviewIDs,
				Inverse: false,
			},
		},
	}

	query, err := pDB.DB.BuildBoolQuery(index, ao)
	if err != nil {
		return nil, nil, err
	}

	pageProgressForPageviews := make(map[string]float32)
	articleProgressForPageviews := make(map[string]float32)
	var searchAfter []types.FieldValue
	batchSize := pDB.DB.BatchSize

	pitID, err := pDB.DB.OpenPIT(index, "5m")
	if err != nil {
		return nil, nil, err
	}
	defer func() {
		if cerr := pDB.DB.ClosePIT(pitID); cerr != nil {
			log.Printf("warn: failed to close PIT: %v", cerr)
		}
	}()

	for {
		res, err := pDB.DB.SearchAfterTypedPIT(pitID, "5m", query, batchSize, searchAfter, []string{"article_progress", "page_progress", "remp_pageview_id"})
		if err != nil {
			return nil, nil, err
		}

		if res.PitId != nil {
			pitID = *res.PitId
		}

		if len(res.Hits.Hits) == 0 {
			break
		}

		for _, hit := range res.Hits.Hits {
			pv := &Pageview{}
			if err := UnmarshalHitSource(hit, pv); err != nil {
				return nil, nil, err
			}

			if hit.Id_ != nil {
				pageProgressForPageviews[*hit.Id_] = pv.PageProgress
				articleProgressForPageviews[*hit.Id_] = pv.ArticleProgress
			}

			searchAfter = hit.Sort
		}
	}

	return pageProgressForPageviews, articleProgressForPageviews, nil
}

// Categories lists all tracked categories.
func (pDB *PageviewElasticsearch) Categories() ([]string, error) {
	return []string{
		CategoryPageview,
	}, nil
}

// Flags lists all available flags.
func (pDB *PageviewElasticsearch) Flags() []string {
	return []string{
		FlagArticle,
	}
}

// Actions lists all tracked actions under the given category.
func (pDB *PageviewElasticsearch) Actions(category string) ([]string, error) {
	switch category {
	case CategoryPageview:
		return []string{
			ActionPageviewLoad,
		}, nil
	}
	return nil, fmt.Errorf("unknown pageview category: %s", category)
}

// Users lists all tracked users.
func (pDB *PageviewElasticsearch) Users() ([]string, error) {
	field := "user_id.keyword"
	termsSize := 10000
	size := 0
	req := &search.Request{
		Size: &size,
		Aggregations: map[string]types.Aggregations{
			"buckets": {
				Terms: &types.TermsAggregation{
					Field: &field,
					Size:  &termsSize,
				},
			},
		},
	}

	res, err := pDB.DB.SearchTyped(pDB.DB.resolveIndex("pageviews"), req)
	if err != nil {
		return nil, err
	}

	users := []string{}
	rawAgg, exists := res.Aggregations["buckets"]
	if !exists {
		return nil, fmt.Errorf("unexpected type of aggregation response: missing 'buckets' aggregation")
	}
	termsAgg, ok := rawAgg.(*types.StringTermsAggregate)
	if !ok {
		return nil, fmt.Errorf("unexpected type of aggregation response: expected StringTermsAggregate, got %T", rawAgg)
	}
	buckets, ok := termsAgg.Buckets.([]types.StringTermsBucket)
	if !ok {
		return nil, fmt.Errorf("unexpected type of bucket list: expected []StringTermsBucket, got %T", termsAgg.Buckets)
	}
	for _, bucket := range buckets {
		key, ok := bucket.Key.(string)
		if !ok {
			return nil, fmt.Errorf("unexpected type of bucket key: expected string, got %T", bucket.Key)
		}
		users = append(users, key)
	}

	return users, nil
}

// resolveQueryBindings returns name of the table and field used within the aggregate function
// based on the provided action.
func (pDB *PageviewElasticsearch) resolveQueryBindings(action string) (elasticsearchQueryBinding, error) {
	switch action {
	case ActionPageviewLoad:
		return elasticsearchQueryBinding{
			Index: TablePageviews,
			Field: "token",
		}, nil
	case ActionPageviewTimespent:
		return elasticsearchQueryBinding{
			Index: TableTimespent,
			Field: "timespent",
		}, nil
	case ActionPageviewProgress:
		return elasticsearchQueryBinding{
			Index: TableProgress,
			Field: "page_progress",
		}, nil
	}

	return elasticsearchQueryBinding{}, fmt.Errorf("unable to resolve query bindings: action [%s] unknown", action)
}
