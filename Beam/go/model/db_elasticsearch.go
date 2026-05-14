package model

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"math"
	"sort"
	"strconv"
	"strings"
	"time"

	"github.com/elastic/go-elasticsearch/v8"
	"github.com/elastic/go-elasticsearch/v8/typedapi"
	"github.com/elastic/go-elasticsearch/v8/typedapi/core/closepointintime"
	"github.com/elastic/go-elasticsearch/v8/typedapi/core/search"
	"github.com/elastic/go-elasticsearch/v8/typedapi/types"
	"github.com/elastic/go-elasticsearch/v8/typedapi/types/enums/fieldtype"
	"github.com/elastic/go-elasticsearch/v8/typedapi/types/enums/sortorder"
	"github.com/pkg/errors"
)

const (
	// DefaultSearchAfterBatchSize is the default batch size for search_after pagination
	DefaultSearchAfterBatchSize = 5000
	// MaxSearchAfterBatchSize is the maximum allowed batch size
	MaxSearchAfterBatchSize = 10000
)

// defaultTypedSort defines the default sort order using TypedAPI
var defaultTypedSort = []types.SortCombinations{
	types.SortOptions{
		SortOptions: map[string]types.FieldSort{
			"time": {Order: &sortorder.Asc, UnmappedType: &fieldtype.Date},
		},
	},
	types.SortOptions{
		SortOptions: map[string]types.FieldSort{
			"remp_pageview_id.keyword": {Order: &sortorder.Asc, UnmappedType: &fieldtype.Keyword},
		},
	},
}

// pitTypedSort defines the sort order for PIT-based search_after pagination.
// Uses _shard_doc as a tie-breaker, which is only available when a PIT is active.
// _shard_doc is a cheap internal lucene doc ID — no field lookup required.
var pitTypedSort = []types.SortCombinations{
	types.SortOptions{
		SortOptions: map[string]types.FieldSort{
			"time": {Order: &sortorder.Asc, UnmappedType: &fieldtype.Date},
		},
	},
	types.SortOptions{
		SortOptions: map[string]types.FieldSort{
			"_shard_doc": {Order: &sortorder.Asc},
		},
	},
}

// ElasticsearchDB represents data layer based on official Elasticsearch client using TypedAPI.
type ElasticsearchDB struct {
	Client      *elasticsearch.Client // Legacy client for backward compatibility
	TypedClient *typedapi.API         // Modern TypedAPI client (primary)
	Debug       bool
	Context     context.Context
	IndexPrefix string
	BatchSize   int // Configurable batch size for search_after operations

	fieldsCache map[string]map[string]string // fields cache represents list of all index (map key) fields (map values)
}

// NewElasticsearchDB returns new instance of Elasticsearch DB using TypedAPI
func NewElasticsearchDB(ctx context.Context, client *elasticsearch.Client, indexPrefix string, debug bool) *ElasticsearchDB {
	edb := &ElasticsearchDB{
		Client:      client,
		TypedClient: typedapi.New(client),
		Debug:       debug,
		Context:     ctx,
		IndexPrefix: indexPrefix,
		BatchSize:   DefaultSearchAfterBatchSize,
	}
	edb.fieldsCache = make(map[string]map[string]string)
	return edb
}

func (eDB *ElasticsearchDB) resolveIndex(index string) string {
	if !strings.HasPrefix(index, eDB.IndexPrefix) {
		return eDB.IndexPrefix + index
	}
	return index
}

// cacheFieldMappingTyped downloads and caches field mappings for specified index using TypedAPI
func (eDB *ElasticsearchDB) cacheFieldMappingTyped(index string) (map[string]string, error) {
	index = eDB.resolveIndex(index)

	res, err := eDB.TypedClient.Indices.GetMapping().Index(index).Do(eDB.Context)
	if err != nil {
		return nil, errors.Wrap(err, fmt.Sprintf("unable to get field mappings for index: %s", index))
	}

	fields := make(map[string]string)

	// Sort index names in reverse alphabetical order so the newest index
	// (with the most up-to-date mappings) is processed first.
	// This matches the old cacheFieldMapping behavior which sorts aliases
	// via sort.Reverse(sort.StringSlice(indices)).
	indexNames := make([]string, 0, len(res))
	for name := range res {
		indexNames = append(indexNames, name)
	}
	sort.Sort(sort.Reverse(sort.StringSlice(indexNames)))

	for _, idxName := range indexNames {
		indexData := res[idxName]
		// Marshal properties to JSON and process manually
		data, err := json.Marshal(indexData.Mappings.Properties)
		if err != nil {
			return nil, errors.Wrap(err, "error marshaling mapping properties")
		}

		var rawProperties map[string]map[string]interface{}
		if err := json.Unmarshal(data, &rawProperties); err != nil {
			return nil, errors.Wrap(err, "error unmarshaling mapping properties")
		}

		for fieldName, fieldData := range rawProperties {
			if _, exists := fields[fieldName]; exists {
				continue
			}
			if t, ok := fieldData["type"].(string); ok {
				fields[fieldName] = t
			}
			// Check for .keyword sub-field
			if subfields, ok := fieldData["fields"].(map[string]interface{}); ok {
				if _, hasKeyword := subfields["keyword"]; hasKeyword {
					fields[fieldName+".keyword"] = "keyword"
				}
			}
		}
	}
	eDB.fieldsCache[index] = fields
	return fields, nil
}

// resolveKeyword resolves field name to keyword field if available
func (eDB *ElasticsearchDB) resolveKeyword(index, field string) (string, error) {
	fieldsMap, ok := eDB.fieldsCache[index]
	if !ok {
		var err error
		fieldsMap, err = eDB.cacheFieldMappingTyped(index)
		if err != nil {
			return "", err
		}
		eDB.fieldsCache[index] = fieldsMap
	}

	// Check if keyword field exists (same behavior as old ElasticDB)
	keywordField := field + ".keyword"
	if _, hasKeyword := fieldsMap[keywordField]; hasKeyword {
		return keywordField, nil
	}

	return field, nil
}

// resolveZeroValue returns zero value for a field based on its type (for missing values in aggregations)
func (eDB *ElasticsearchDB) resolveZeroValue(index, field string) (interface{}, error) {
	fields, ok := eDB.fieldsCache[index]
	var err error

	// populate cache for given index if it's empty
	if !ok {
		fields, err = eDB.cacheFieldMappingTyped(index)
		if err != nil {
			return "", err
		}
		eDB.fieldsCache[index] = fields
	}

	// check field's data type
	def, ok := fields[field]
	if !ok {
		// no such field present in index, return nil
		return nil, nil
	}

	// Return appropriate zero value based on field type
	switch def {
	case "text":
		return "", nil
	case "boolean":
		return false, nil
	case "date":
		return nil, nil
	case "long":
		return 0, nil
	}

	return nil, nil
}

// SearchTyped executes a typed search request
func (eDB *ElasticsearchDB) SearchTyped(index string, req *search.Request) (*search.Response, error) {
	return eDB.TypedClient.Search().
		Index(eDB.resolveIndex(index)).
		Request(req).
		Do(eDB.Context)
}

// BuildCompositeAggregation builds a typed composite aggregation for pagination support
func (eDB *ElasticsearchDB) BuildCompositeAggregation(index string, afterKey types.CompositeAggregateKey, options AggregateOptions) (map[string]types.Aggregations, bool, error) {
	aggregationAdded := false
	var sources []map[string]types.CompositeAggregationSource
	missingBucket := true

	// Add TimeHistogram as composite source if specified (must be first, before CountHistogram and GroupBy)
	if options.TimeHistogram != nil {
		tz := "UTC"
		if options.TimeHistogram.TimeZone != nil {
			tz = options.TimeHistogram.TimeZone.String()
		}
		field := "time"
		format := "date_optional_time"
		interval := options.TimeHistogram.Interval
		source := map[string]types.CompositeAggregationSource{
			"time_histogram": {
				DateHistogram: &types.CompositeDateHistogramAggregation{
					Field:         &field,
					FixedInterval: &interval,
					TimeZone:      &tz,
					Format:        &format,
					MissingBucket: &missingBucket,
				},
			},
		}
		sources = append(sources, source)
		aggregationAdded = true
	}

	// Add CountHistogram as composite source if specified
	if options.CountHistogram != nil {
		interval := types.Float64(options.CountHistogram.Interval)
		field := options.CountHistogram.Field
		source := map[string]types.CompositeAggregationSource{
			"count_histogram": {
				Histogram: &types.CompositeHistogramAggregation{
					Field:         &field,
					Interval:      interval,
					MissingBucket: &missingBucket,
				},
			},
		}
		sources = append(sources, source)
		aggregationAdded = true
	}

	// Add GroupBy fields as composite sources
	for _, field := range options.GroupBy {
		resolvedField, err := eDB.resolveKeyword(index, field)
		if err != nil {
			return nil, false, err
		}
		source := map[string]types.CompositeAggregationSource{
			field: {
				Terms: &types.CompositeTermsAggregation{
					Field:         &resolvedField,
					MissingBucket: &missingBucket,
				},
			},
		}
		sources = append(sources, source)
		aggregationAdded = true
	}

	if !aggregationAdded {
		return nil, false, nil
	}

	size := 10000
	compositeAgg := &types.CompositeAggregation{
		Sources: sources,
		Size:    &size,
	}

	if len(afterKey) > 0 {
		compositeAgg.After = afterKey
	}

	return map[string]types.Aggregations{
		"buckets": {Composite: compositeAgg},
	}, true, nil
}

// BuildGroupByAggregation builds typed aggregations with optional group-by nesting
func (eDB *ElasticsearchDB) BuildGroupByAggregation(index string, options AggregateOptions, extras map[string]types.Aggregations, dateHistAgg *types.DateHistogramAggregation) (map[string]types.Aggregations, bool, error) {
	if len(options.GroupBy) == 0 && len(extras) == 0 && dateHistAgg == nil {
		return nil, false, nil
	}

	// Build the innermost aggregations (extras + optional date histogram)
	innermostAggs := make(map[string]types.Aggregations)

	if dateHistAgg != nil {
		dhAgg := types.Aggregations{DateHistogram: dateHistAgg}
		if len(extras) > 0 {
			dhAgg.Aggregations = extras
		}
		innermostAggs["date_time_histogram"] = dhAgg
	} else {
		for label, agg := range extras {
			innermostAggs[label] = agg
		}
	}

	if len(options.GroupBy) == 0 {
		return innermostAggs, true, nil
	}

	// Build nested terms aggregations from innermost to outermost (iterate in reverse)
	currentAggs := innermostAggs
	for i := len(options.GroupBy) - 1; i >= 0; i-- {
		field := options.GroupBy[i]
		resolvedField, err := eDB.resolveKeyword(index, field)
		if err != nil {
			return nil, false, err
		}

		zeroVal, err := eDB.resolveZeroValue(index, field)
		if err != nil {
			return nil, false, err
		}

		size := math.MaxInt32
		termsConfig := types.TermsAggregation{
			Field: &resolvedField,
			Size:  &size,
		}

		if zeroVal != nil {
			termsConfig.Missing = zeroVal
		}

		agg := types.Aggregations{Terms: &termsConfig}
		if len(currentAggs) > 0 {
			agg.Aggregations = currentAggs
		}

		currentAggs = map[string]types.Aggregations{
			field: agg,
		}
	}

	return currentAggs, true, nil
}

// typedBucket is an internal helper for iterating over typed terms buckets
type typedBucket struct {
	key   string
	count int64
	aggs  map[string]types.Aggregate
}

// extractTermsBuckets extracts bucket info from various typed terms aggregates
func extractTermsBuckets(agg types.Aggregate) ([]typedBucket, bool) {
	switch a := agg.(type) {
	case *types.StringTermsAggregate:
		if a == nil {
			return nil, false
		}
		switch b := a.Buckets.(type) {
		case []types.StringTermsBucket:
			result := make([]typedBucket, 0, len(b))
			for _, bucket := range b {
				result = append(result, typedBucket{
					key:   fmt.Sprintf("%v", bucket.Key),
					count: bucket.DocCount,
					aggs:  bucket.Aggregations,
				})
			}
			return result, len(result) > 0
		case map[string]types.StringTermsBucket:
			result := make([]typedBucket, 0, len(b))
			for k, bucket := range b {
				result = append(result, typedBucket{
					key:   k,
					count: bucket.DocCount,
					aggs:  bucket.Aggregations,
				})
			}
			return result, len(result) > 0
		}
	case *types.LongTermsAggregate:
		if a == nil {
			return nil, false
		}
		switch b := a.Buckets.(type) {
		case []types.LongTermsBucket:
			result := make([]typedBucket, 0, len(b))
			for _, bucket := range b {
				keyStr := strconv.FormatInt(bucket.Key, 10)
				if bucket.KeyAsString != nil {
					keyStr = *bucket.KeyAsString
				}
				result = append(result, typedBucket{
					key:   keyStr,
					count: bucket.DocCount,
					aggs:  bucket.Aggregations,
				})
			}
			return result, len(result) > 0
		case map[string]types.LongTermsBucket:
			result := make([]typedBucket, 0, len(b))
			for k, bucket := range b {
				result = append(result, typedBucket{
					key:   k,
					count: bucket.DocCount,
					aggs:  bucket.Aggregations,
				})
			}
			return result, len(result) > 0
		}
	case *types.DoubleTermsAggregate:
		if a == nil {
			return nil, false
		}
		switch b := a.Buckets.(type) {
		case []types.DoubleTermsBucket:
			result := make([]typedBucket, 0, len(b))
			for _, bucket := range b {
				keyStr := strconv.FormatFloat(float64(bucket.Key), 'f', -1, 64)
				if bucket.KeyAsString != nil {
					keyStr = *bucket.KeyAsString
				}
				result = append(result, typedBucket{
					key:   keyStr,
					count: bucket.DocCount,
					aggs:  bucket.Aggregations,
				})
			}
			return result, len(result) > 0
		}
	}
	return nil, false
}

// typedUnwrapCallback is the callback function for unwrapping typed aggregations
type typedUnwrapCallback func(tags map[string]string, count int64, aggs map[string]types.Aggregate) error

// unwrapTypedAggregation recursively traverses typed nested aggregations
func (eDB *ElasticsearchDB) unwrapTypedAggregation(docCount int64, aggregations map[string]types.Aggregate, groupBy []string, tags map[string]string, cb typedUnwrapCallback) error {
	if len(groupBy) == 0 {
		return cb(tags, docCount, aggregations)
	}

	field := groupBy[0]
	remaining := groupBy[1:]

	agg, ok := aggregations[field]
	if !ok {
		// No aggregation found in response — match old UnwrapAggregation behavior:
		// set empty values for remaining tags but do NOT call cb (return 0 rows)
		for _, f := range groupBy {
			tags[f] = ""
		}
		return nil
	}

	buckets, hasBuckets := extractTermsBuckets(agg)
	if !hasBuckets {
		// Aggregation exists but has 0 buckets — match old UnwrapAggregation behavior:
		// synthesize one row with empty tags and the parent docCount
		for _, f := range groupBy {
			tags[f] = ""
		}
		return cb(tags, docCount, map[string]types.Aggregate{})
	}

	for _, b := range buckets {
		newTags := make(map[string]string, len(tags)+1)
		for k, v := range tags {
			newTags[k] = v
		}
		newTags[field] = b.key

		if err := eDB.unwrapTypedAggregation(b.count, b.aggs, remaining, newTags, cb); err != nil {
			return err
		}
	}

	return nil
}

// countRowCollectionFromTypedCompositeBuckets generates CountRowCollection from composite aggregation
func (eDB *ElasticsearchDB) countRowCollectionFromTypedCompositeBuckets(agg *types.CompositeAggregate, options AggregateOptions) (CountRowCollection, error) {
	rowMap := make(map[string]*CountRow)

	buckets, ok := agg.Buckets.([]types.CompositeBucket)
	if !ok {
		return nil, nil
	}

	for _, bucket := range buckets {
		docCount := bucket.DocCount
		key := make(types.CompositeAggregateKey, len(bucket.Key))
		for k, v := range bucket.Key {
			key[k] = v
		}

		// Extract TimeHistogram value from composite key if present
		var timeHistogramItem *TimeHistogramItem
		var timeHistogramTotal int
		if options.TimeHistogram != nil {
			if timeVal, ok := key["time_histogram"]; ok {
				if timeKey, ok := timeVal.(string); ok {
					t, err := time.Parse(time.RFC3339, timeKey)
					if err == nil {
						timeHistogramTotal = int(docCount)
						timeHistogramItem = &TimeHistogramItem{
							Time:  t,
							Value: float64(docCount),
						}
						delete(key, "time_histogram")
					}
				}
			}
		}

		// Extract CountHistogram value if present.
		// Use constant key "count_histogram" matching the composite source name
		// (not the dynamic field name) to avoid collisions with GroupBy fields.
		var countHistogramItem *CountHistogramItem
		var countHistogramTotal int
		if options.CountHistogram != nil {
			if bucketKeyVal, ok := key["count_histogram"]; ok {
				switch bk := bucketKeyVal.(type) {
				case float64:
					countHistogramTotal = int(docCount)
					countHistogramItem = &CountHistogramItem{
						BucketKey: bk,
						Value:     int(docCount),
					}
					delete(key, "count_histogram")
				case types.Float64:
					countHistogramTotal = int(docCount)
					countHistogramItem = &CountHistogramItem{
						BucketKey: float64(bk),
						Value:     int(docCount),
					}
					delete(key, "count_histogram")
				}
			}
		}

		// Build tags from remaining keys (excluding dummy key).
		// Sort fields to ensure deterministic rowKey across map iterations.
		tags := make(map[string]string)
		fields := make([]string, 0, len(key))
		for field := range key {
			if field != "_dummy" {
				fields = append(fields, field)
			}
		}
		sort.Strings(fields)

		var rowKey string
		for _, field := range fields {
			value := key[field]
			var tagValue string
			switch v := value.(type) {
			case float64:
				tagValue = strconv.FormatFloat(v, 'f', 0, 64)
			case bool:
				tagValue = strconv.FormatBool(v)
			case nil:
				tagValue = ""
			default:
				tagValue = fmt.Sprintf("%v", v)
			}
			tags[field] = tagValue
			rowKey = fmt.Sprintf("%s_%s-%s", rowKey, field, tagValue)
		}

		row, ok := rowMap[rowKey]
		if !ok {
			row = &CountRow{Tags: tags}
			rowMap[rowKey] = row
		}

		if timeHistogramItem != nil {
			row.TimeHistogram = append(row.TimeHistogram, *timeHistogramItem)
			row.Count += timeHistogramTotal
		}

		if countHistogramItem != nil {
			row.CountHistogram = append(row.CountHistogram, *countHistogramItem)
			row.Count += countHistogramTotal
		}

		if row.Count == 0 {
			row.Count = int(docCount)
		}
	}

	var crc CountRowCollection
	for _, row := range rowMap {
		crc = append(crc, *row)
	}

	return crc, nil
}

// countRowCollectionFromTypedResponse generates CountRowCollection from typed search response
func (eDB *ElasticsearchDB) countRowCollectionFromTypedResponse(res *search.Response, options AggregateOptions) (CountRowCollection, bool, error) {
	var crc CountRowCollection
	tags := make(map[string]string)

	var totalHits int64
	if res.Hits.Total != nil {
		totalHits = res.Hits.Total.Value
	}

	err := eDB.unwrapTypedAggregation(totalHits, res.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggs map[string]types.Aggregate) error {
		crcTags := make(map[string]string, len(tags))
		for k, v := range tags {
			crcTags[k] = v
		}

		var timeHistogram []TimeHistogramItem
		if options.TimeHistogram != nil {
			if dhAgg, ok := aggs["date_time_histogram"].(*types.DateHistogramAggregate); ok {
				if buckets, ok := dhAgg.Buckets.([]types.DateHistogramBucket); ok {
					for _, bucket := range buckets {
						var t time.Time
						if bucket.KeyAsString != nil {
							t, _ = time.Parse(time.RFC3339, *bucket.KeyAsString)
						} else {
							t = time.Unix(0, bucket.Key*int64(time.Millisecond)).UTC()
						}
						timeHistogram = append(timeHistogram, TimeHistogramItem{
							Time:  t,
							Value: float64(bucket.DocCount),
						})
					}
				}
			}
		}

		var countHistogram []CountHistogramItem
		if options.CountHistogram != nil {
			if histAgg, ok := aggs[options.CountHistogram.Field].(*types.HistogramAggregate); ok {
				if buckets, ok := histAgg.Buckets.([]types.HistogramBucket); ok {
					for _, bucket := range buckets {
						countHistogram = append(countHistogram, CountHistogramItem{
							BucketKey: float64(bucket.Key),
							Value:     int(bucket.DocCount),
						})
					}
				}
			}
		}

		crc = append(crc, CountRow{
			Tags:           crcTags,
			Count:          int(count),
			TimeHistogram:  timeHistogram,
			CountHistogram: countHistogram,
		})

		return nil
	})

	if err != nil {
		return nil, false, err
	}

	return crc, true, nil
}

// sumRowCollectionFromTypedResponse generates SumRowCollection from typed search response
func (eDB *ElasticsearchDB) sumRowCollectionFromTypedResponse(res *search.Response, options AggregateOptions, targetAgg, sumField string) (SumRowCollection, bool, error) {
	var src SumRowCollection
	tags := make(map[string]string)

	var totalHits int64
	if res.Hits.Total != nil {
		totalHits = res.Hits.Total.Value
	}

	err := eDB.unwrapTypedAggregation(totalHits, res.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggs map[string]types.Aggregate) error {
		var timeHistogram []TimeHistogramItem
		var sumValue float64

		if options.TimeHistogram != nil {
			if dhAgg, ok := aggs["date_time_histogram"].(*types.DateHistogramAggregate); ok {
				if buckets, ok := dhAgg.Buckets.([]types.DateHistogramBucket); ok {
					for _, bucket := range buckets {
						sumAggName := fmt.Sprintf("%s_sum", sumField)
						if sumAgg, ok := bucket.Aggregations[sumAggName].(*types.SumAggregate); ok && sumAgg.Value != nil {
							t := time.Unix(0, bucket.Key*int64(time.Millisecond)).UTC()
							val := float64(*sumAgg.Value)
							timeHistogram = append(timeHistogram, TimeHistogramItem{
								Time:  t,
								Value: val,
							})
							sumValue += val
						}
					}
				}
			}
		} else {
			sumAgg, ok := aggs[targetAgg].(*types.SumAggregate)
			if !ok {
				// Match old sumRowCollectionFromAggregations behavior: when sum agg is not found
				// (e.g. GroupBy field had no buckets), fall back to docCount.
				log.Printf("warn: sum agg %q not found in response; falling back to docCount=%d", targetAgg, count)
				sumValue = float64(count)
			} else if sumAgg.Value != nil {
				sumValue = float64(*sumAgg.Value)
			}
		}

		srcTags := make(map[string]string, len(tags))
		for k, v := range tags {
			srcTags[k] = v
		}

		src = append(src, SumRow{
			Tags:          srcTags,
			Sum:           sumValue,
			TimeHistogram: timeHistogram,
		})

		return nil
	})

	if err != nil {
		return nil, false, err
	}

	return src, len(src) > 0, nil
}

// avgRowCollectionFromTypedResponse generates AvgRowCollection from typed search response
func (eDB *ElasticsearchDB) avgRowCollectionFromTypedResponse(res *search.Response, options AggregateOptions, targetAgg, avgField string) (AvgRowCollection, bool, error) {
	var arc AvgRowCollection
	tags := make(map[string]string)

	var totalHits int64
	if res.Hits.Total != nil {
		totalHits = res.Hits.Total.Value
	}

	err := eDB.unwrapTypedAggregation(totalHits, res.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggs map[string]types.Aggregate) error {
		var timeHistogram []TimeHistogramItem
		var avgValue float64

		if options.TimeHistogram != nil {
			// avgValue stays 0 — average of per-bucket averages is not meaningful (matches old behavior)
			if dhAgg, ok := aggs["date_time_histogram"].(*types.DateHistogramAggregate); ok {
				if buckets, ok := dhAgg.Buckets.([]types.DateHistogramBucket); ok {
					for _, bucket := range buckets {
						avgAggName := fmt.Sprintf("%s_avg", avgField)
						if avgAgg, ok := bucket.Aggregations[avgAggName].(*types.AvgAggregate); ok && avgAgg.Value != nil {
							val := float64(*avgAgg.Value)
							if !math.IsNaN(val) {
								t := time.Unix(0, bucket.Key*int64(time.Millisecond)).UTC()
								timeHistogram = append(timeHistogram, TimeHistogramItem{
									Time:  t,
									Value: val,
								})
							}
						}
					}
				}
			}
		} else {
			avgAgg, ok := aggs[targetAgg].(*types.AvgAggregate)
			if !ok {
				// Mirror old behavior: skip row if avg aggregation not found (e.g., no matching docs or field missing)
				return nil
			}
			if avgAgg.Value != nil {
				val := float64(*avgAgg.Value)
				if !math.IsNaN(val) {
					avgValue = val
				}
			}
		}

		arcTags := make(map[string]string, len(tags))
		for k, v := range tags {
			arcTags[k] = v
		}

		arc = append(arc, AvgRow{
			Tags:          arcTags,
			Avg:           avgValue,
			TimeHistogram: timeHistogram,
		})

		return nil
	})

	if err != nil {
		return nil, false, err
	}

	return arc, len(arc) > 0, nil
}

// uniqueRowCollectionFromTypedResponse generates CountRowCollection for unique count aggregations
func (eDB *ElasticsearchDB) uniqueRowCollectionFromTypedResponse(res *search.Response, options AggregateOptions, targetAgg, field string) (CountRowCollection, bool, error) {
	var crc CountRowCollection
	tags := make(map[string]string)

	var totalHits int64
	if res.Hits.Total != nil {
		totalHits = res.Hits.Total.Value
	}

	err := eDB.unwrapTypedAggregation(totalHits, res.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggs map[string]types.Aggregate) error {
		// in case of histogram, total count is 0 since distinct values cannot be summed across buckets
		var uniqueCount int
		var timeHistogram []TimeHistogramItem

		if options.TimeHistogram != nil {
			if dhAgg, ok := aggs["date_time_histogram"].(*types.DateHistogramAggregate); ok {
				if buckets, ok := dhAgg.Buckets.([]types.DateHistogramBucket); ok {
					for _, bucket := range buckets {
						if cardAgg, ok := bucket.Aggregations[targetAgg].(*types.CardinalityAggregate); ok {
							t := time.Unix(0, bucket.Key*int64(time.Millisecond)).UTC()
							timeHistogram = append(timeHistogram, TimeHistogramItem{
								Time:  t,
								Value: float64(cardAgg.Value),
							})
						}
					}
				}
			}
		} else {
			cardAgg, ok := aggs[targetAgg].(*types.CardinalityAggregate)
			if !ok {
				// Match old uniqueRowCollectionFromAggregations behavior: when cardinality agg is not found
				// (e.g. GroupBy field had no buckets), fall back to docCount.
				uniqueCount = int(count)
			} else {
				uniqueCount = int(cardAgg.Value)
			}
		}

		crcTags := make(map[string]string, len(tags))
		for k, v := range tags {
			crcTags[k] = v
		}

		crc = append(crc, CountRow{
			Tags:          crcTags,
			Count:         uniqueCount,
			TimeHistogram: timeHistogram,
		})

		return nil
	})

	if err != nil {
		return nil, false, err
	}

	return crc, len(crc) > 0, nil
}

// extractTagValue converts Elasticsearch field value to string representation
func extractTagValue(val interface{}, fieldName string, context string) (string, error) {
	switch v := val.(type) {
	case nil:
		return "", nil
	case bool:
		if v {
			return "1", nil
		}
		return "0", nil
	case string:
		return v, nil
	case float64:
		return strconv.FormatFloat(v, 'f', 0, 64), nil
	case int64:
		return strconv.FormatInt(v, 10), nil
	default:
		return "", fmt.Errorf("unhandled tag type in %s: field=%s, type=%T", context, fieldName, val)
	}
}

// buildTagsFromSource creates tags map and key from source fields
func (eDB *ElasticsearchDB) buildTagsFromSource(
	source map[string]interface{},
	groupByFields []string,
	context string,
) (tags map[string]string, key string, err error) {
	tags = make(map[string]string)
	key = ""

	for _, field := range groupByFields {
		tagVal, err := extractTagValue(source[field], field, context)
		if err != nil {
			return nil, "", err
		}

		tags[field] = tagVal
		key = fmt.Sprintf("%s%s=%s_", key, field, tagVal)
	}

	return tags, key, nil
}

// BuildBoolQuery creates a type-safe bool query using TypedAPI
func (eDB *ElasticsearchDB) BuildBoolQuery(index string, options AggregateOptions) (*types.Query, error) {
	must := make([]types.Query, 0)
	mustNot := make([]types.Query, 0)
	filter := make([]types.Query, 0)

	// Time range filter
	if !options.TimeAfter.IsZero() || !options.TimeBefore.IsZero() {
		rangeQuery := types.DateRangeQuery{}
		if !options.TimeAfter.IsZero() {
			timeAfterStr := options.TimeAfter.Format(time.RFC3339)
			rangeQuery.Gte = &timeAfterStr
		}
		if !options.TimeBefore.IsZero() {
			timeBeforeStr := options.TimeBefore.Format(time.RFC3339)
			rangeQuery.Lt = &timeBeforeStr
		}

		filter = append(filter, types.Query{
			Range: map[string]types.RangeQuery{
				"time": rangeQuery,
			},
		})
	}

	// Category filter
	if options.Category != "" {
		field, err := eDB.resolveKeyword(index, "category")
		if err != nil {
			return nil, err
		}
		must = append(must, types.Query{
			Term: map[string]types.TermQuery{
				field: {Value: options.Category},
			},
		})
	}

	// Action filter
	if options.Action != "" {
		field, err := eDB.resolveKeyword(index, "action")
		if err != nil {
			return nil, err
		}
		must = append(must, types.Query{
			Term: map[string]types.TermQuery{
				field: {Value: options.Action},
			},
		})
	}

	// Step filter
	if options.Step != "" {
		field, err := eDB.resolveKeyword(index, "step")
		if err != nil {
			return nil, err
		}
		must = append(must, types.Query{
			Term: map[string]types.TermQuery{
				field: {Value: options.Step},
			},
		})
	}

	// FilterBy conditions
	for _, f := range options.FilterBy {
		if len(f.Values) == 0 {
			continue
		}

		field, err := eDB.resolveKeyword(index, f.Tag)
		if err != nil {
			return nil, err
		}

		queries := eDB.buildTermsQueriesTyped(field, f.Values, f.Inverse)

		if f.Inverse {
			mustNot = append(mustNot, queries...)
		} else {
			must = append(must, queries...)
		}
	}

	// Exists filters
	for _, f := range options.Exist {
		field, err := eDB.resolveKeyword(index, f.Tag)
		if err != nil {
			return nil, err
		}

		existsQuery := types.Query{
			Exists: &types.ExistsQuery{
				Field: field,
			},
		}

		if f.Inverse {
			mustNot = append(mustNot, existsQuery)
		} else {
			filter = append(filter, existsQuery)
		}
	}

	return &types.Query{
		Bool: &types.BoolQuery{
			Must:    must,
			MustNot: mustNot,
			Filter:  filter,
		},
	}, nil
}

// buildTermsQueriesTyped creates type-safe terms queries with UTM/RTM compatibility
func (eDB *ElasticsearchDB) buildTermsQueriesTyped(field string, values []string, inverse bool) []types.Query {
	fieldValues := make([]types.FieldValue, len(values))
	for i, v := range values {
		fieldValues[i] = v
	}

	primaryQuery := types.Query{
		Terms: &types.TermsQuery{
			TermsQuery: map[string]types.TermsQueryField{
				field: fieldValues,
			},
		},
	}

	fieldMap := getCompatibleFields(field)
	if fieldMap == nil {
		return []types.Query{primaryQuery}
	}

	secondaryValues := make([]types.FieldValue, len(values))
	copy(secondaryValues, fieldValues)

	secondaryQuery := types.Query{
		Terms: &types.TermsQuery{
			TermsQuery: map[string]types.TermsQueryField{
				fieldMap.secondary: secondaryValues,
			},
		},
	}

	if inverse {
		return []types.Query{primaryQuery, secondaryQuery}
	}

	return []types.Query{
		{
			Bool: &types.BoolQuery{
				Should: []types.Query{primaryQuery, secondaryQuery},
			},
		},
	}
}

// SearchAfterTyped executes a search with search_after using TypedAPI
func (eDB *ElasticsearchDB) SearchAfterTyped(
	index string,
	query *types.Query,
	size int,
	searchAfter []types.FieldValue,
	selectFields []string,
) (*search.Response, error) {

	sizePtr := size
	req := &search.Request{
		Query:          query,
		Size:           &sizePtr,
		Sort:           defaultTypedSort,
		TrackTotalHits: false,
	}

	if len(searchAfter) > 0 {
		req.SearchAfter = searchAfter
	}

	if len(selectFields) > 0 {
		req.Source_ = types.SourceFilter{
			Includes: selectFields,
		}
	}

	res, err := eDB.TypedClient.Search().
		Index(eDB.resolveIndex(index)).
		Request(req).
		Do(eDB.Context)

	if err != nil {
		// Fresh rollover index with no documents may have no field mappings yet.
		// ES returns "No mapping found for [field] in order to sort on" in that case.
		// Treat it as an empty result instead of a hard error.
		if strings.Contains(err.Error(), "No mapping found for") {
			log.Printf("warn: SearchAfterTyped: %v (returning empty result)", err)
			return &search.Response{}, nil
		}
		return nil, err
	}

	return res, nil
}

// OpenPIT opens a Point In Time for the given index and returns the pit_id.
// The caller must call ClosePIT when done to release server-side resources.
func (eDB *ElasticsearchDB) OpenPIT(index string, keepAlive string) (string, error) {
	res, err := eDB.TypedClient.OpenPointInTime(eDB.resolveIndex(index)).
		KeepAlive(keepAlive).
		Do(eDB.Context)
	if err != nil {
		return "", err
	}
	return res.Id, nil
}

// ClosePIT releases a Point In Time by id. Should be deferred after OpenPIT.
func (eDB *ElasticsearchDB) ClosePIT(pitID string) error {
	_, err := eDB.TypedClient.ClosePointInTime().
		Request(&closepointintime.Request{Id: pitID}).
		Do(eDB.Context)
	return err
}

// SearchAfterTypedPIT executes a search_after page using an active PIT.
// The index must not be specified — it is embedded in the PIT.
// ES may rotate the pit_id; callers must update their copy from res.PitId after each call.
func (eDB *ElasticsearchDB) SearchAfterTypedPIT(
	pitID string,
	keepAlive string,
	query *types.Query,
	size int,
	searchAfter []types.FieldValue,
	selectFields []string,
) (*search.Response, error) {
	sizePtr := size
	req := &search.Request{
		Query:          query,
		Size:           &sizePtr,
		Sort:           pitTypedSort,
		TrackTotalHits: false,
		Pit: &types.PointInTimeReference{
			Id:        pitID,
			KeepAlive: keepAlive,
		},
	}

	if len(searchAfter) > 0 {
		req.SearchAfter = searchAfter
	}

	if len(selectFields) > 0 {
		req.Source_ = types.SourceFilter{
			Includes: selectFields,
		}
	}

	// When using PIT, do NOT call .Index() — it is embedded in the PIT.
	res, err := eDB.TypedClient.Search().
		Request(req).
		Do(eDB.Context)

	if err != nil {
		if strings.Contains(err.Error(), "No mapping found for") {
			log.Printf("warn: SearchAfterTypedPIT: %v (returning empty result)", err)
			return &search.Response{}, nil
		}
		return nil, err
	}

	return res, nil
}

// UnmarshalHitSource unmarshals a TypedAPI hit source to a target struct
func UnmarshalHitSource(hit types.Hit, target interface{}) error {
	sourceBytes, err := hit.Source_.MarshalJSON()
	if err != nil {
		return err
	}
	return json.Unmarshal(sourceBytes, target)
}

// IndexOrAliasExists checks whether an index or alias exists using go-elasticsearch/v8.
func (eDB *ElasticsearchDB) IndexOrAliasExists(ctx context.Context, index string) (bool, error) {
	resolvedIndex := eDB.resolveIndex(index)

	// Check alias first
	aliasRes, err := eDB.Client.Cat.Aliases(
		eDB.Client.Cat.Aliases.WithName(resolvedIndex),
		eDB.Client.Cat.Aliases.WithContext(ctx),
	)
	if err != nil {
		return false, errors.Wrap(err, fmt.Sprintf("unable to check if alias is used: %s", resolvedIndex))
	}
	defer aliasRes.Body.Close()

	if !aliasRes.IsError() {
		// Cat.Aliases returns tab-separated text lines; a non-empty trimmed body means aliases exist.
		body, _ := io.ReadAll(aliasRes.Body)
		trimmed := strings.TrimSpace(string(body))
		if len(trimmed) > 0 {
			return true, nil
		}
	}

	// Check if index exists
	indexRes, err := eDB.Client.Indices.Exists(
		[]string{resolvedIndex},
		eDB.Client.Indices.Exists.WithContext(ctx),
	)
	if err != nil {
		return false, errors.Wrap(err, fmt.Sprintf("unable to check if index exists: %s", resolvedIndex))
	}
	defer indexRes.Body.Close()

	return indexRes.StatusCode == 200, nil
}

// PushMapping pushes explicit field mapping to an existing index using go-elasticsearch/v8.
func (eDB *ElasticsearchDB) PushMapping(ctx context.Context, index string, mapping string) error {
	resolvedIndex := eDB.resolveIndex(index)

	res, err := eDB.Client.Indices.PutMapping(
		[]string{resolvedIndex},
		strings.NewReader(mapping),
		eDB.Client.Indices.PutMapping.WithContext(ctx),
	)
	if err != nil {
		return errors.Wrap(err, fmt.Sprintf("unable to update field mappings for index: %s", resolvedIndex))
	}
	defer res.Body.Close()

	if res.IsError() {
		return fmt.Errorf("unable to update field mappings for index: %s, status: %s", resolvedIndex, res.Status())
	}

	// Parse response to check acknowledged field — ES can return HTTP 200 with acknowledged=false
	var result struct {
		Acknowledged bool `json:"acknowledged"`
	}
	if err := json.NewDecoder(res.Body).Decode(&result); err != nil {
		return errors.Wrap(err, fmt.Sprintf("unable to parse mapping response for index: %s", resolvedIndex))
	}
	if !result.Acknowledged {
		return fmt.Errorf("mapping update not acknowledged for index: %s", resolvedIndex)
	}

	return nil
}

// fieldMapping represents UTM/RTM field compatibility
type fieldMapping struct {
	primary   string
	secondary string
}

// getCompatibleFields returns compatible field names for UTM/RTM/banner fields
func getCompatibleFields(field string) *fieldMapping {
	if strings.HasPrefix(field, "banner_variant") {
		return &fieldMapping{
			primary:   field,
			secondary: "rtm_" + field[7:],
		}
	}
	if strings.HasPrefix(field, "rtm_variant") {
		return &fieldMapping{
			primary:   field,
			secondary: "banner_" + field[4:],
		}
	}
	if strings.HasPrefix(field, "utm_") {
		return &fieldMapping{
			primary:   field,
			secondary: "rtm_" + field[4:],
		}
	}
	if strings.HasPrefix(field, "rtm_") {
		return &fieldMapping{
			primary:   field,
			secondary: "utm_" + field[4:],
		}
	}
	return nil
}
