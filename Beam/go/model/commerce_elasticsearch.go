package model

import (
	"encoding/json"
	"fmt"
	"log"
	"time"

	"github.com/elastic/go-elasticsearch/v8/typedapi/core/search"
	"github.com/elastic/go-elasticsearch/v8/typedapi/types"
)

// CommerceElasticsearch is official ElasticsearchDB implementation of CommerceStorage.
type CommerceElasticsearch struct {
	DB *ElasticsearchDB
}

func (cDB *CommerceElasticsearch) getIndex() string {
	return cDB.DB.resolveIndex("commerce")
}

// Count returns count of events based on the provided filter options.
func (cDB *CommerceElasticsearch) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	query, err := cDB.DB.BuildBoolQuery(cDB.getIndex(), options)
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

	aggs, aggregationAdded, err := cDB.DB.BuildGroupByAggregation(cDB.getIndex(), options, nil, dateHistAgg)
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
		// allow to compute more than 10000 hits (default value) in case there is no aggregation
		req.TrackTotalHits = true
	}

	res, err := cDB.DB.SearchTyped(cDB.getIndex(), req)
	if err != nil {
		return nil, false, err
	}

	if len(options.GroupBy) == 0 && options.TimeHistogram == nil {
		// No aggregation — return raw hit count (matches old CommerceElastic.Count behavior)
		var count int
		if res.Hits.Total != nil {
			count = int(res.Hits.Total.Value)
		}
		return CountRowCollection{CountRow{Count: count}}, true, nil
	}

	return cDB.DB.countRowCollectionFromTypedResponse(res, options)
}

// Sum returns sum of events based on the provided filter options.
func (cDB *CommerceElasticsearch) Sum(options AggregateOptions) (SumRowCollection, bool, error) {
	sumField := "revenue"
	targetAgg := "revenue_sum"

	query, err := cDB.DB.BuildBoolQuery(cDB.getIndex(), options)
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
		// Sum does NOT use MinDocCount=0 or ExtendedBounds — only Count does (matches old CommerceElastic.Sum behavior).
		dateHistAgg = &types.DateHistogramAggregation{
			Field:         &field,
			FixedInterval: interval,
			TimeZone:      &tz,
		}
	}

	aggs, aggregationAdded, err := cDB.DB.BuildGroupByAggregation(cDB.getIndex(), options, extras, dateHistAgg)
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

	res, err := cDB.DB.SearchTyped(cDB.getIndex(), req)
	if err != nil {
		return nil, false, err
	}

	return cDB.DB.sumRowCollectionFromTypedResponse(res, options, targetAgg, sumField)
}

// List returns list of all events based on given CommerceOptions.
func (cDB *CommerceElasticsearch) List(options ListOptions) (CommerceRowCollection, error) {
	var crc CommerceRowCollection

	index := cDB.getIndex()

	// prepare CommerceRow buckets
	crBuckets := make(map[string]*CommerceRow)

	// Use TypedAPI for search_after pagination with PIT for consistency
	var searchAfter []types.FieldValue
	batchSize := cDB.DB.BatchSize

	// Build query using TypedAPI
	query, err := cDB.DB.BuildBoolQuery(index, options.AggregateOptions)
	if err != nil {
		return nil, err
	}

	pitID, err := cDB.DB.OpenPIT(index, "5m")
	if err != nil {
		return nil, err
	}
	defer func() {
		if cerr := cDB.DB.ClosePIT(pitID); cerr != nil {
			log.Printf("warn: failed to close PIT: %v", cerr)
		}
	}()

	for {
		res, err := cDB.DB.SearchAfterTypedPIT(pitID, "5m", query, batchSize, searchAfter, options.SelectFields)
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
			// Unmarshal to Commerce struct
			c := &Commerce{}
			if err := UnmarshalHitSource(hit, c); err != nil {
				return nil, err
			}

			if hit.Id_ != nil {
				c.ID = *hit.Id_
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
			tags, key, err := cDB.DB.buildTagsFromSource(sourceMap, options.GroupBy, "commerce listing")
			if err != nil {
				return nil, err
			}

			// place Commerce instance into proper CommerceRow based on tags (key)
			cr, ok := crBuckets[key]
			if !ok {
				cr = &CommerceRow{
					Tags: tags,
				}
				crBuckets[key] = cr
			}

			cr.Commerces = append(cr.Commerces, c)

			// Extract sort values for next iteration - type-safe!
			searchAfter = hit.Sort
		}
	}

	for _, cr := range crBuckets {
		crc = append(crc, cr)
	}

	return crc, nil
}

// Categories lists all available categories.
func (cDB *CommerceElasticsearch) Categories() ([]string, error) {
	return []string{
		CategoryCommerce,
	}, nil
}

// Flags lists all available flags.
func (cDB *CommerceElasticsearch) Flags() []string {
	// Commerce doesn't have flags like pageviews
	return []string{}
}

// Actions lists all available actions under the given category.
func (cDB *CommerceElasticsearch) Actions(category string) ([]string, error) {
	switch category {
	case CategoryCommerce:
		return []string{
			"checkout",
			"payment",
			"purchase",
			"refund",
		}, nil
	}
	return nil, fmt.Errorf("unknown commerce category: %s", category)
}
