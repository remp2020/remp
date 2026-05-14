package model

import (
	"encoding/json"
	"fmt"
	"log"
	"time"

	"github.com/elastic/go-elasticsearch/v8/typedapi/core/search"
	"github.com/elastic/go-elasticsearch/v8/typedapi/types"
)

// EventElasticsearch is official ElasticsearchDB implementation of EventStorage.
type EventElasticsearch struct {
	DB               *ElasticsearchDB
	categoriesCached []string
	actionsCached    map[string][]string
}

func (eDB *EventElasticsearch) getIndex() string {
	return eDB.DB.resolveIndex("events")
}

// Count returns number of events matching the filter defined by EventOptions.
func (eDB *EventElasticsearch) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	index := eDB.getIndex()

	query, err := eDB.DB.BuildBoolQuery(index, options)
	if err != nil {
		return nil, false, err
	}

	var dateHistAgg *types.DateHistogramAggregation
	if options.TimeHistogram != nil {
		tz := "UTC"
		if options.TimeHistogram.TimeZone != nil {
			tz = options.TimeHistogram.TimeZone.String()
		}
		field := "time"
		interval := options.TimeHistogram.Interval
		minDocCount := 0
		dateHistAgg = &types.DateHistogramAggregation{
			Field:         &field,
			FixedInterval: interval,
			TimeZone:      &tz,
			MinDocCount:   &minDocCount,
			ExtendedBounds: &types.ExtendedBoundsFieldDateMath{
				Min: options.TimeAfter.UTC().Format(time.RFC3339),
				Max: options.TimeBefore.UTC().Format(time.RFC3339),
			},
		}
	}

	// Count histogram is passed as an extra nested aggregation so it is correctly placed
	// inside any group-by terms (mirrors the WrapAggregation extras pattern in the old implementation).
	var extras map[string]types.Aggregations
	if options.CountHistogram != nil {
		interval := types.Float64(options.CountHistogram.Interval)
		field := options.CountHistogram.Field
		extras = map[string]types.Aggregations{
			field: {
				Histogram: &types.HistogramAggregation{
					Field:    &field,
					Interval: &interval,
				},
			},
		}
	}

	topLevelAggs, aggregationAdded, err := eDB.DB.BuildGroupByAggregation(index, options, extras, dateHistAgg)
	if err != nil {
		return nil, false, err
	}

	size := 0
	req := &search.Request{
		Query: query,
		Size:  &size,
	}

	if aggregationAdded {
		req.Aggregations = topLevelAggs
	} else {
		// allow to compute more than 10000 hits (default value) in case there is no aggregation
		req.TrackTotalHits = true
	}

	res, err := eDB.DB.SearchTyped(index, req)
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

	return eDB.DB.countRowCollectionFromTypedResponse(res, options)
}

// List returns list of all events based on given EventOptions.
func (eDB *EventElasticsearch) List(options ListOptions) (EventRowCollection, error) {
	var erc EventRowCollection

	index := eDB.getIndex()

	// prepare EventRow buckets
	erBuckets := make(map[string]*EventRow)

	// Use TypedAPI for search_after pagination with PIT for consistency
	var searchAfter []types.FieldValue
	batchSize := eDB.DB.BatchSize

	// Build query using TypedAPI
	query, err := eDB.DB.BuildBoolQuery(index, options.AggregateOptions)
	if err != nil {
		return nil, err
	}

	pitID, err := eDB.DB.OpenPIT(index, "5m")
	if err != nil {
		return nil, err
	}
	defer func() {
		if cerr := eDB.DB.ClosePIT(pitID); cerr != nil {
			log.Printf("warn: failed to close PIT: %v", cerr)
		}
	}()

	for {
		res, err := eDB.DB.SearchAfterTypedPIT(pitID, "5m", query, batchSize, searchAfter, options.SelectFields)
		if err != nil {
			return nil, err
		}

		if res.PitId != nil {
			pitID = *res.PitId
		}

		// Check if we have hits
		if len(res.Hits.Hits) == 0 {
			break
		}

		// Process hits - type-safe!
		for _, hit := range res.Hits.Hits {
			// Unmarshal to Event struct
			e := &Event{}
			if err2 := UnmarshalHitSource(hit, e); err2 != nil {
				return nil, err2
			}

			if hit.Id_ != nil {
				e.ID = *hit.Id_
			}

			// Convert Source_ to map for buildTagsFromSource helper
			var sourceMap map[string]interface{}
			sourceBytes, err2 := hit.Source_.MarshalJSON()
			if err2 != nil {
				return nil, err2
			}
			if err2 = json.Unmarshal(sourceBytes, &sourceMap); err2 != nil {
				return nil, err2
			}

			// Build tags from source using helper function
			tags, key, err2 := eDB.DB.buildTagsFromSource(sourceMap, options.GroupBy, "event listing")
			if err2 != nil {
				return nil, err2
			}

			// place Event instance into proper EventRow based on tags (key)
			er, ok := erBuckets[key]
			if !ok {
				er = &EventRow{
					Tags: tags,
				}
				erBuckets[key] = er
			}

			er.Events = append(er.Events, e)

			// Extract sort values for next iteration - type-safe!
			searchAfter = hit.Sort
		}
	}

	for _, er := range erBuckets {
		erc = append(erc, er)
	}

	return erc, nil
}

// Categories lists all tracked categories.
func (eDB *EventElasticsearch) Categories() ([]string, error) {
	if len(eDB.categoriesCached) > 0 {
		return eDB.categoriesCached, nil
	}

	field := "category.keyword"
	termsSize := 1000
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

	res, err := eDB.DB.SearchTyped(eDB.getIndex(), req)
	if err != nil {
		return nil, err
	}

	categories := []string{}
	rawAgg, exists := res.Aggregations["buckets"]
	if !exists {
		return nil, fmt.Errorf("unexpected aggregation response: missing 'buckets' aggregation")
	}
	termsAgg, ok := rawAgg.(*types.StringTermsAggregate)
	if !ok {
		return nil, fmt.Errorf("unexpected aggregation response: expected StringTermsAggregate, got %T", rawAgg)
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
		categories = append(categories, key)
	}

	return categories, nil
}

// Flags lists all available flags.
func (eDB *EventElasticsearch) Flags() []string {
	// Events don't have flags like pageviews
	return []string{}
}

// Actions lists all tracked actions under the given category.
func (eDB *EventElasticsearch) Actions(category string) ([]string, error) {
	if ac, ok := eDB.actionsCached[category]; ok {
		return ac, nil
	}

	categoryField := "category.keyword"
	actionField := "action.keyword"
	termsSize := 1000
	size := 0
	req := &search.Request{
		Size: &size,
		Query: &types.Query{
			Bool: &types.BoolQuery{
				Must: []types.Query{
					{
						Term: map[string]types.TermQuery{
							categoryField: {Value: category},
						},
					},
				},
			},
		},
		Aggregations: map[string]types.Aggregations{
			"buckets": {
				Terms: &types.TermsAggregation{
					Field: &actionField,
					Size:  &termsSize,
				},
			},
		},
	}

	res, err := eDB.DB.SearchTyped(eDB.getIndex(), req)
	if err != nil {
		return nil, err
	}

	actions := []string{}
	rawAgg, exists := res.Aggregations["buckets"]
	if !exists {
		return nil, fmt.Errorf("unexpected aggregation response: missing 'buckets' aggregation")
	}
	termsAgg, ok := rawAgg.(*types.StringTermsAggregate)
	if !ok {
		return nil, fmt.Errorf("unexpected aggregation response: expected StringTermsAggregate, got %T", rawAgg)
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
		actions = append(actions, key)
	}

	return actions, nil
}

// Users lists all tracked users.
func (eDB *EventElasticsearch) Users() ([]string, error) {
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

	res, err := eDB.DB.SearchTyped(eDB.getIndex(), req)
	if err != nil {
		return nil, err
	}

	users := []string{}
	rawAgg, exists := res.Aggregations["buckets"]
	if !exists {
		return nil, fmt.Errorf("unexpected aggregation response: missing 'buckets' aggregation")
	}
	termsAgg, ok := rawAgg.(*types.StringTermsAggregate)
	if !ok {
		return nil, fmt.Errorf("unexpected aggregation response: expected StringTermsAggregate, got %T", rawAgg)
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

// Cache creates internal cache of available categories and actions so they're not polled repeatedly.
func (eDB *EventElasticsearch) Cache() error {
	// Reset cache first so Categories() and Actions() fetch fresh data from ES,
	// not the previously cached values. Mirrors old EventElastic.Cache() behavior.
	eDB.categoriesCached = []string{}
	categories, err := eDB.Categories()
	if err != nil {
		return err
	}
	eDB.categoriesCached = categories

	eDB.actionsCached = make(map[string][]string)
	for _, category := range categories {
		actions, err := eDB.Actions(category)
		if err != nil {
			return err
		}
		eDB.actionsCached[category] = actions
	}

	return nil
}
