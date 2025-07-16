package model

import (
	"context"
	"fmt"
	"math"
	"sort"
	"strconv"
	"strings"
	"time"

	"github.com/olivere/elastic/v7"
	"github.com/pkg/errors"
)

// ElasticDB represents data layer based on ElasticSearch.
type ElasticDB struct {
	Client      *elastic.Client
	Debug       bool
	Context     context.Context
	IndexPrefix string

	fieldsCache map[string]map[string]string // fields cache represents list of all index (map key) fields (map values)
}

// NewElasticDB returns new instance of ElasticSearch DB implementation
func NewElasticDB(ctx context.Context, client *elastic.Client, indexPrefix string, debug bool) *ElasticDB {
	edb := &ElasticDB{
		Client:      client,
		Debug:       debug,
		Context:     ctx,
		IndexPrefix: indexPrefix,
	}
	edb.fieldsCache = make(map[string]map[string]string)
	return edb
}

func (eDB *ElasticDB) resolveIndex(index string) string {
	if !strings.HasPrefix(index, eDB.IndexPrefix) {
		return eDB.IndexPrefix + index
	}

	return index
}

func (eDB *ElasticDB) addSearchFilters(search *elastic.SearchService, index string, o AggregateOptions) (*elastic.SearchService, error) {
	bq, err := eDB.boolQueryFromOptions(index, o)
	if err != nil {
		return nil, err
	}
	return search.Query(bq), nil
}

func (eDB *ElasticDB) addScrollFilters(scroll *elastic.ScrollService, index string, o AggregateOptions) (*elastic.ScrollService, error) {
	bq, err := eDB.boolQueryFromOptions(index, o)
	if err != nil {
		return nil, err
	}
	return scroll.Query(bq), nil
}

func (eDB *ElasticDB) boolQueryFromOptions(index string, o AggregateOptions) (*elastic.BoolQuery, error) {
	bq := elastic.NewBoolQuery()
	for _, f := range o.FilterBy {
		if len(f.Values) == 0 {
			continue
		}

		// cast to interface slice, see https://github.com/golang/go/wiki/InterfaceSlice
		var interfaceSlice = make([]interface{}, len(f.Values))
		for idx, val := range f.Values {
			interfaceSlice[idx] = val
		}

		field, err := eDB.resolveKeyword(index, f.Tag)
		if err != nil {
			return nil, err
		}

		if f.Inverse {
			bq = bq.MustNot(elastic.NewTermsQuery(field, interfaceSlice...))

			// UTM parameters have been renamed to RTM
			// We still want to keep the backwards compatibility by translating UTM conditions to RTM
			// However, filtering by utm_ is deprecated and will be removed in the future
			if strings.HasPrefix(field, "banner_variant") { // full field should be banner_variant.keyword
				bq = bq.MustNot(elastic.NewTermsQuery("rtm_"+field[7:], interfaceSlice...))
			} else if strings.HasPrefix(field, "rtm_variant") {
				bq = bq.MustNot(elastic.NewTermsQuery("banner_"+field[4:], interfaceSlice...))
			} else if strings.HasPrefix(field, "utm_") {
				bq = bq.MustNot(elastic.NewTermsQuery("rtm_"+field[4:], interfaceSlice...))
			} else if strings.HasPrefix(field, "rtm_") {
				bq = bq.MustNot(elastic.NewTermsQuery("utm_"+field[4:], interfaceSlice...))
			}
		} else {
			// UTM parameters have been renamed to RTM
			// We still want to keep the backwards compatibility by translating UTM conditions to RTM (joined by OR operator)
			// However, filtering by utm_ is deprecated and will be removed in the future
			if strings.HasPrefix(field, "banner_variant") {
				bannerVariantTerm := elastic.NewTermsQuery(field, interfaceSlice...)
				rtmTerm := elastic.NewTermsQuery("rtm_"+field[7:], interfaceSlice...)
				bqInner := elastic.NewBoolQuery()
				bqInner.Should(bannerVariantTerm, rtmTerm)
				bq = bq.Must(bqInner)
			} else if strings.HasPrefix(field, "rtm_variant") {
				rtmTerm := elastic.NewTermsQuery(field, interfaceSlice...)
				bannerVariantTerm := elastic.NewTermsQuery("banner_"+field[4:], interfaceSlice...)
				bqInner := elastic.NewBoolQuery()
				bqInner.Should(bannerVariantTerm, rtmTerm)
				bq = bq.Must(bqInner)
			} else if strings.HasPrefix(field, "utm_") {
				utmTerm := elastic.NewTermsQuery(field, interfaceSlice...)
				rtmTerm := elastic.NewTermsQuery("rtm_"+field[4:], interfaceSlice...)
				bqInner := elastic.NewBoolQuery()
				bqInner.Should(utmTerm, rtmTerm) // Should for 2 terms = OR
				bq = bq.Must(bqInner)
			} else if strings.HasPrefix(field, "rtm_") {
				rtmTerm := elastic.NewTermsQuery(field, interfaceSlice...)
				utmTerm := elastic.NewTermsQuery("utm_"+field[4:], interfaceSlice...)
				bqInner := elastic.NewBoolQuery()
				bqInner.Should(utmTerm, rtmTerm)
				bq = bq.Must(bqInner)
			} else {
				bq = bq.Must(elastic.NewTermsQuery(field, interfaceSlice...))
			}
		}
	}

	for _, ex := range o.Exist {
		field, err := eDB.resolveKeyword(index, ex.Tag)
		if err != nil {
			return nil, err
		}

		if ex.Inverse {
			bq = bq.MustNot(elastic.NewExistsQuery(field))
		} else {
			bq = bq.Must(elastic.NewExistsQuery(field))
		}
	}

	if o.Category != "" {
		field, err := eDB.resolveKeyword(index, "category")
		if err != nil {
			return nil, err
		}
		bq = bq.Must(elastic.NewTermQuery(field, o.Category))
	}
	if o.Action != "" {
		field, err := eDB.resolveKeyword(index, "action")
		if err != nil {
			return nil, err
		}
		bq = bq.Must(elastic.NewTermQuery(field, o.Action))
	}
	if o.Step != "" {
		field, err := eDB.resolveKeyword(index, "step")
		if err != nil {
			return nil, err
		}
		bq = bq.Must(elastic.NewTermQuery(field, o.Step))
	}
	if !o.TimeAfter.IsZero() || !o.TimeBefore.IsZero() {
		rq := elastic.NewRangeQuery("time")
		if !o.TimeAfter.IsZero() {
			rq.Gte(o.TimeAfter)
		}
		if !o.TimeBefore.IsZero() {
			rq.Lt(o.TimeBefore)
		}
		bq = bq.Must(rq)
	}
	return bq, nil
}

// addGroupBy creates a standard (wrapped) aggregation. The results are fetchable
// via countRowCollectionFromBuckets or sumRowCollectionFromBuckets.
// If any aggregation is added, function returns true as a second return value
func (eDB *ElasticDB) addGroupBy(search *elastic.SearchService, index string, o AggregateOptions,
	extras map[string]elastic.Aggregation, dateHistogramAgg *elastic.DateHistogramAggregation) (*elastic.SearchService, bool, error) {

	aggregationAdded := false

	if len(o.GroupBy) > 0 || len(extras) > 0 || dateHistogramAgg != nil {
		var err error
		search, _, err = eDB.WrapAggregation(index, o.GroupBy, search, extras, dateHistogramAgg, nil)
		if err != nil {
			return nil, false, err
		}
		aggregationAdded = true
	}
	return search, aggregationAdded, nil
}

// countRowCollectionFromAggregations generates CountRowCollection based on query result aggregations.
func (eDB *ElasticDB) countRowCollectionFromAggregations(result *elastic.SearchResult, options AggregateOptions) (CountRowCollection, bool, error) {
	var crc CountRowCollection
	tags := make(map[string]string)

	err := eDB.UnwrapAggregation(result.Hits.TotalHits.Value, result.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggregations elastic.Aggregations) error {
		crcTags := make(map[string]string)

		var timeHistogram []TimeHistogramItem
		if options.TimeHistogram != nil {
			histogramData, ok := aggregations.DateHistogram("date_time_histogram")
			if !ok {
				errors.New("missing expected histogram aggregation data")
			}

			if histogramData != nil {
				for _, timeHistogramItem := range histogramData.Buckets {
					time, err := time.Parse(time.RFC3339, *timeHistogramItem.KeyAsString)
					if err != nil {
						errors.New("cant parse time from elastic search with RFC3339 layout")
					}

					timeHistogram = append(timeHistogram, TimeHistogramItem{
						Time:  time,
						Value: float64(timeHistogramItem.DocCount),
					})
				}
			}
		}

		var countHistogram []CountHistogramItem
		if options.CountHistogram != nil {
			histogramData, ok := aggregations.Histogram(options.CountHistogram.Field)
			if !ok {
				errors.New("missing expected histogram aggregation data")
			}

			if histogramData != nil {
				for _, countHistogramItem := range histogramData.Buckets {
					countHistogram = append(countHistogram, CountHistogramItem{
						BucketKey: countHistogramItem.Key,
						Value:     int(countHistogramItem.DocCount),
					})
				}
			}
		}

		// copy tags to avoid memory sharing
		for key, val := range tags {
			crcTags[key] = val
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

// sumRowCollectionFromAggregations generates SumRowCollection based on query result aggregations.
func (eDB *ElasticDB) sumRowCollectionFromAggregations(result *elastic.SearchResult, options AggregateOptions, targetAgg string, sumField string) (SumRowCollection, bool, error) {
	var src SumRowCollection
	tags := make(map[string]string)

	err := eDB.UnwrapAggregation(result.Hits.TotalHits.Value, result.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggregations elastic.Aggregations) error {

		var timeHistogram []TimeHistogramItem
		var sumValue float64

		if options.TimeHistogram != nil {
			histogramData, ok := aggregations.DateHistogram("date_time_histogram")
			if !ok {
				return errors.New("missing expected histogram aggregation data")
			}

			if histogramData != nil {
				for _, timeHistogramItem := range histogramData.Buckets {
					sumAggLabel := fmt.Sprintf("%s_sum", sumField)
					agg, ok := timeHistogramItem.Aggregations.Sum(sumAggLabel)
					if !ok {
						return errors.New("cant find timespent_sum sub agg in date histogram agg")
					}

					time := time.Unix(0, int64(timeHistogramItem.Key)*int64(time.Millisecond)).UTC()
					timeHistogram = append(timeHistogram, TimeHistogramItem{
						Time:  time,
						Value: float64(*agg.Value),
					})

					sumValue += float64(*agg.Value)
				}
			}
		} else {
			sumAgg, ok := aggregations.Sum(targetAgg)
			if ok {
				if sumAgg.Value != nil {
					sumValue = *sumAgg.Value
				}
			} else {
				// The aggregation was not resolved based on the all provided srcTags to group by.
				// UnwrapAggregation uses "" as a placeholder value for the unresolved levels to make srcTags complete.
				//
				// Since elastic doesn't have the aggregate value available as a resolved aggregation,
				// it should be save to use docCount available at the level we're currently at.
				sumValue = float64(count)
			}
		}

		srcTags := make(map[string]string)
		// copy tags to avoid memory sharing
		for key, val := range tags {
			srcTags[key] = val
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

	ok := len(src) > 0
	return src, ok, nil
}

// avgRowCollectionFromAggregations generates AvgRowCollection based on query result aggregations.
func (eDB *ElasticDB) avgRowCollectionFromAggregations(result *elastic.SearchResult, options AggregateOptions, targetAgg string, avgField string) (AvgRowCollection, bool, error) {
	var src AvgRowCollection
	tags := make(map[string]string)

	err := eDB.UnwrapAggregation(result.Hits.TotalHits.Value, result.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggregations elastic.Aggregations) error {

		var timeHistogram []TimeHistogramItem
		// in case of histogram, total avg value is 0 since it cannot be simply computed as average of averages
		var avgValue float64

		if options.TimeHistogram != nil {
			histogramData, ok := aggregations.DateHistogram("date_time_histogram")
			if !ok {
				return errors.New("missing expected histogram aggregation data")
			}

			if histogramData != nil {
				for _, timeHistogramItem := range histogramData.Buckets {
					avgAggLabel := fmt.Sprintf("%s_avg", avgField)
					agg, ok := timeHistogramItem.Aggregations.Avg(avgAggLabel)
					if !ok {
						return errors.New("unable to retrieve average value from histogram data")
					}

					time := time.Unix(0, int64(timeHistogramItem.Key)*int64(time.Millisecond)).UTC()
					timeHistogram = append(timeHistogram, TimeHistogramItem{
						Time:  time,
						Value: float64(*agg.Value),
					})
				}
			}
		} else {
			avgAgg, ok := aggregations.Avg(targetAgg)
			if !ok {
				return nil
			}

			if avgAgg.Value != nil {
				avgValue = *avgAgg.Value
			}
		}

		srcTags := make(map[string]string)
		// copy tags to avoid memory sharing
		for key, val := range tags {
			srcTags[key] = val
		}

		src = append(src, AvgRow{
			Tags:          srcTags,
			Avg:           avgValue,
			TimeHistogram: timeHistogram,
		})

		return nil
	})

	if err != nil {
		return nil, false, err
	}

	ok := len(src) > 0
	return src, ok, nil
}

// uniqueRowCollectionFromAggregations generates CountRowCollection based on query result aggregations.
func (eDB *ElasticDB) uniqueRowCollectionFromAggregations(result *elastic.SearchResult, options AggregateOptions, targetAgg string, uniqueField string) (CountRowCollection, bool, error) {
	var src CountRowCollection
	tags := make(map[string]string)

	err := eDB.UnwrapAggregation(result.Hits.TotalHits.Value, result.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggregations elastic.Aggregations) error {

		var timeHistogram []TimeHistogramItem
		// in case of histogram, total count will be 0 since we cannot compute distinc values from histogram buckets
		var countValue float64

		if options.TimeHistogram != nil {
			histogramData, ok := aggregations.DateHistogram("date_time_histogram")
			if !ok {
				return errors.New("missing expected histogram aggregation data")
			}

			if histogramData != nil {
				for _, timeHistogramItem := range histogramData.Buckets {
					uniqueAggLabel := fmt.Sprintf("%s_unique", uniqueField)
					agg, ok := timeHistogramItem.Aggregations.Cardinality(uniqueAggLabel)
					if !ok {
						return errors.New("Unable to retrieve cardinality value from histogram data")
					}

					time := time.Unix(0, int64(timeHistogramItem.Key)*int64(time.Millisecond)).UTC()
					timeHistogram = append(timeHistogram, TimeHistogramItem{
						Time:  time,
						Value: float64(*agg.Value),
					})
				}
			}
		} else {
			avgAgg, ok := aggregations.Cardinality(targetAgg)
			if ok {
				if avgAgg.Value != nil {
					countValue = *avgAgg.Value
				}
			} else {
				// The aggregation was not resolved based on the all provided srcTags to group by.
				// UnwrapAggregation uses "" as a placeholder value for the unresolved levels to make srcTags complete.
				//
				// Since elastic doesn't have the aggregate value available as a resolved aggregation,
				// it should be save to use docCount available at the level we're currently at.
				countValue = float64(count)
			}
		}

		srcTags := make(map[string]string)
		// copy tags to avoid memory sharing
		for key, val := range tags {
			srcTags[key] = val
		}

		src = append(src, CountRow{
			Tags:          srcTags,
			Count:         int(countValue),
			TimeHistogram: timeHistogram,
		})

		return nil
	})

	if err != nil {
		return nil, false, err
	}

	ok := len(src) > 0
	return src, ok, nil
}

// addCompositeGroupBy creates a composite aggregation. The results are fetchable
// via countRowCollectionFromCompositeBuckets or sumRowCollectionFromCompositeBuckets.
func (eDB *ElasticDB) addCompositeGroupBy(search *elastic.SearchService, index string, afterKey map[string]interface{}, o AggregateOptions) (*elastic.SearchService, bool, error) {
	aggregationAdded := false
	compositeAgg := elastic.NewCompositeAggregation()
	compositeAgg.AggregateAfter(afterKey)
	compositeAgg.Size(10000)

	// handle time histogram
	if o.TimeHistogram != nil {
		tz := "UTC"
		if o.TimeHistogram.TimeZone != nil {
			tz = o.TimeHistogram.TimeZone.String()
		}
		compositeAgg = compositeAgg.Sources(
			elastic.NewCompositeAggregationDateHistogramValuesSource("time_histogram").
				Field("time").
				FixedInterval(o.TimeHistogram.Interval).
				Format("date_optional_time").
				TimeZone(tz).
				MissingBucket(true),
		)
		aggregationAdded = true
	}

	// handle count histogram
	if o.CountHistogram != nil {
		compositeAgg = compositeAgg.Sources(
			elastic.NewCompositeAggregationHistogramValuesSource("count_histogram", o.CountHistogram.Interval).
				Field(o.CountHistogram.Field).
				MissingBucket(true),
		)
		aggregationAdded = true
	}

	// handle regular group by
	if len(o.GroupBy) > 0 {
		for _, g := range o.GroupBy {
			f, err := eDB.resolveKeyword(index, g)
			if err != nil {
				return nil, false, err
			}

			compositeAgg = compositeAgg.Sources(
				elastic.NewCompositeAggregationTermsValuesSource(g).Field(f).MissingBucket(true),
			)
			aggregationAdded = true
		}
	}

	if aggregationAdded {
		search = search.Aggregation("buckets", compositeAgg)
	}

	return search, aggregationAdded, nil
}

// countRowCollectionFromCompositeBuckets extracts CountRow data from buckets generated by composite aggregation query.
func (eDB *ElasticDB) countRowCollectionFromCompositeBuckets(buckets []*elastic.AggregationBucketCompositeItem, o AggregateOptions) (CountRowCollection, error) {
	crc := CountRowCollection{}

	if len(buckets) == 0 {
		return crc, nil
	}

	crmap := make(map[string]*CountRow)

	for _, bucket := range buckets {
		var timeHistogramItem TimeHistogramItem
		var countHistogramItem CountHistogramItem
		var timeHistogramTotal, countHistogramTotal int

		// time histogram handler
		if o.TimeHistogram != nil {
			bucketKey, ok := bucket.Key["time_histogram"].(string)
			if !ok {
				return nil, errors.New("missing expected time_histogram aggregation data")
			}

			time, err := time.Parse(time.RFC3339, bucketKey)
			if err != nil {
				errors.New(fmt.Sprintf("cant parse time from elastic search with RFC3339 layout: %s", bucketKey))
			}

			timeHistogramTotal += int(bucket.DocCount)
			timeHistogramItem = TimeHistogramItem{
				Time:  time,
				Value: float64(bucket.DocCount),
			}

			// cleanup so it's not processed by default tag handler below
			delete(bucket.Key, "time_histogram")
		}

		// count histogram handler
		if o.CountHistogram != nil {
			bucketKey, ok := bucket.Key["count_histogram"].(float64)
			if !ok {
				return nil, errors.New("missing expected count_histogram aggregation data")
			}

			countHistogramTotal += int(bucket.DocCount)
			countHistogramItem = CountHistogramItem{
				BucketKey: bucketKey,
				Value:     int(bucket.DocCount),
			}

			// cleanup so it's not processed by default tag handler below
			delete(bucket.Key, "count_histogram")
		}

		// handle group bys
		tags := make(map[string]string)
		var rowKey string

		for key, val := range bucket.Key {
			switch tag := val.(type) {
			case float64:
				tags[key] = strconv.FormatFloat(tag, 'f', 0, 64)
			case string:
				tags[key] = tag
			case bool:
				tags[key] = strconv.FormatBool(tag)
			case nil:
				tags[key] = ""
			default:
				return nil, fmt.Errorf("unexpected type of aggregation bucket count: %T", val)
			}
			rowKey = fmt.Sprintf("%s_%s-%s", rowKey, key, tags[key])
		}

		row, ok := crmap[rowKey]
		if !ok {
			row = &CountRow{
				Tags: tags,
			}
			crmap[rowKey] = row
		}
		if o.TimeHistogram != nil {
			row.TimeHistogram = append(row.TimeHistogram, timeHistogramItem)
			row.Count += timeHistogramTotal
		}
		if o.CountHistogram != nil {
			row.CountHistogram = append(row.CountHistogram, countHistogramItem)
			row.Count += countHistogramTotal
		}
		if row.Count == 0 {
			row.Count = int(bucket.DocCount)
		}
	}

	for _, row := range crmap {
		crc = append(crc, *row)
	}
	return crc, nil
}

// sumRowCollectionFromBuckets extracts SumRow data from buckets generated by composite aggregation query.
func (eDB *ElasticDB) sumRowCollectionFromCompositeBuckets(buckets []*elastic.AggregationBucketKeyItem) (SumRowCollection, bool, error) {
	src := SumRowCollection{}

	for _, bucket := range buckets {
		keys, ok := bucket.Key.(map[string]interface{}) // composite aggregation has map[string]interface{} bucket keys
		if !ok {
			return nil, false, fmt.Errorf("unexpected type of bucket key: %T", bucket.Key)
		}
		tags := make(map[string]string)
		for key, val := range keys {
			switch tag := val.(type) {
			case float64:
				tags[key] = strconv.FormatFloat(tag, 'f', 0, 64)
			case string:
				tags[key] = tag
			default:
				return nil, false, fmt.Errorf("unexpected type of aggregation bucket count: %T", val)
			}
		}
		src = append(src, SumRow{
			Tags: tags,
			Sum:  float64(bucket.DocCount),
		})
	}
	return src, true, nil
}

// resolveKeyword checks, whether the index contains ".keyword" field (for exact indexed search) and uses that if possible.
func (eDB *ElasticDB) resolveKeyword(index, field string) (string, error) {
	fields, ok := eDB.fieldsCache[index]
	var err error

	// populate cache for given index if it's empty
	if !ok {
		fields, err = eDB.cacheFieldMapping(index)
		if err != nil {
			return "", err
		}
	}

	// check if keyword is present among fields
	keyword := fmt.Sprintf("%s.keyword", field)
	_, ok = fields[keyword]
	if !ok {
		return field, nil
	}
	return keyword, nil
}

// resolveKeyword checks, whether the index contains ".keyword" field (for exact indexed search) and uses that if possible.
func (eDB *ElasticDB) resolveZeroValue(index, field string) (interface{}, error) {
	fields, ok := eDB.fieldsCache[index]
	var err error

	// populate cache for given index if it's empty
	if !ok {
		fields, err = eDB.cacheFieldMapping(index)
		if err != nil {
			return "", err
		}
	}

	// check fields data type
	def, ok := fields[field]
	if !ok {
		// no such field present in index, we can return zero value of interface{}
		return nil, nil
	}

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

// cacheFieldMapping downloads and caches field mappings for specified index
func (eDB *ElasticDB) cacheFieldMapping(index string) (map[string]string, error) {
	index = eDB.resolveIndex(index)

	result, err := eDB.Client.GetMapping().Index(index).Type("").Do(eDB.Context)
	if err != nil {
		return nil, errors.Wrap(err, fmt.Sprintf("unable to get field mappings for index: %s", index))
	}

	// prepare mapping map and reading callback
	fields := make(map[string]string)

	readMapping := func(root map[string]interface{}) error {
		mappings, ok := root["mappings"].(map[string]interface{})
		if !ok {
			return fmt.Errorf("\"mappings\" field not present within field mapping response")
		}

		var properties map[string]interface{}

		doc, ok := mappings["_doc"].(map[string]interface{})
		if !ok {
			// elastic v7 removes the types and skips _doc level of nesting; this is temporary to maintain both 6.* and 7.* compatibility
			properties, ok = mappings["properties"].(map[string]interface{})
			if !ok {
				return fmt.Errorf("invalid document provided, no mapping data available for document: _doc")
			}
		}

		if properties == nil {
			// this handles properties extraction out of _doc document type in Elastic 6.*
			properties, ok = doc["properties"].(map[string]interface{})
			if !ok {
				return fmt.Errorf("\"properties\" field not present within field mapping response for document: _doc")
			}
		}

		for f, rawField := range properties {
			if _, ok := fields[f]; ok {
				// field is already mapped
				continue
			}

			field, ok := rawField.(map[string]interface{})
			if !ok {
				return fmt.Errorf("unexpected type of field property, expected map[string]interface{}: %T", rawField)
			}
			typ, ok := field["type"].(string)
			if !ok {
				return fmt.Errorf("non-string field property mapping type received: %T", field["type"])
			}
			fields[f] = typ

			subfields, ok := field["fields"].(map[string]interface{})
			if ok {
				if _, ok := subfields["keyword"]; ok {
					fields[fmt.Sprintf("%s.keyword", f)] = "keyword"
				}
			}
		}

		return nil
	}

	// read mapping info of index
	root, ok := result[index].(map[string]interface{})
	if ok {
		// mapping found directly for requested index
		readMapping(root)
	} else {
		// there's no such index, but there might be an alias, let's try those
		aliases, err := eDB.Client.Aliases().Index(index).Do(eDB.Context)
		if err != nil {
			return nil, errors.Wrap(err, fmt.Sprintf("unable to get aliases for index: %s", index))
		}

		indices := aliases.IndicesByAlias(index)
		if len(indices) == 0 {
			return nil, fmt.Errorf("invalid index provided, no mapping data available: %s", index)
		}

		// sort aliases by name in reverse order and run mappings
		sort.Sort(sort.Reverse(sort.StringSlice(indices)))
		for _, idx := range indices {
			root, ok = result[idx].(map[string]interface{})
			if !ok {
				return nil, fmt.Errorf("invalid alias provided, no mapping data available: %s", idx)
			}
			readMapping(root)
		}
	}

	eDB.fieldsCache[index] = fields
	return fields, nil
}

// WrapAggregation recursivelly wraps aggregations based on provided groupBy fields.
//
// It includes any extra aggregations on the lowest level.
//
// Initially the implementation used ElasticDB::addCompositeGroupBy(), but it was a beta feature
// and elastic didn't allow us to link sum aggregation to the results.
//
// Following is a standard wrapping via SubAggregation() endorsed by official docs.
func (eDB *ElasticDB) WrapAggregation(index string, groupBy []string, search *elastic.SearchService,
	extras map[string]elastic.Aggregation, dateHistogramAgg *elastic.DateHistogramAggregation, agg *elastic.TermsAggregation) (*elastic.SearchService, *elastic.TermsAggregation, error) {

	// if there is no group by - add only extras aggs
	if len(groupBy) == 0 {
		if dateHistogramAgg != nil {
			for label, extraAgg := range extras {
				dateHistogramAgg.SubAggregation(label, extraAgg)
			}

			search = search.Aggregation("date_time_histogram", dateHistogramAgg)
		} else {
			for label, extraAgg := range extras {
				search = search.Aggregation(label, extraAgg)
			}
		}

		return search, nil, nil
	}

	for _, field := range groupBy {
		keyword, err := eDB.resolveKeyword(index, field)
		if err != nil {
			return nil, nil, err
		}
		zeroVal, err := eDB.resolveZeroValue(index, field)
		if err != nil {
			return nil, nil, err
		}

		termsAgg := elastic.NewTermsAggregation().Field(keyword).Size(math.MaxInt32).Missing(zeroVal)

		if len(groupBy) > 1 {
			search, termsAgg, err = eDB.WrapAggregation(index, groupBy[1:], search, extras, dateHistogramAgg, termsAgg)
			if err != nil {
				return nil, nil, err
			}
		} else {
			if dateHistogramAgg != nil {
				for label, extraAgg := range extras {
					dateHistogramAgg = dateHistogramAgg.SubAggregation(label, extraAgg)
				}

				termsAgg = termsAgg.SubAggregation("date_time_histogram", dateHistogramAgg)
			} else {
				// include external aggregation if necessary (e.g. sum)
				for label, extraAgg := range extras {
					termsAgg = termsAgg.SubAggregation(label, extraAgg)
				}
			}
		}

		if agg == nil {
			return search.Aggregation(field, termsAgg), nil, nil
		}

		return search, agg.SubAggregation(field, termsAgg), nil
	}

	return search, nil, nil
}

// UnwrapCallback represents final callback that should be called when all aggregations are unwrapped
// and the final set of tags and count can be provided
type UnwrapCallback func(tags map[string]string, docCount int64, aggregations elastic.Aggregations) error

// UnwrapAggregation traverses through all the aggregations and calls the provided callback on the lowest level
// providing tags of the fields and resulting count.
func (eDB *ElasticDB) UnwrapAggregation(docCount int64, aggregations elastic.Aggregations, groupBy []string, tags map[string]string, cb UnwrapCallback) error {

	if len(groupBy) == 0 {
		if err := cb(tags, docCount, aggregations); err != nil {
			return err
		}
		return nil
	}

	for _, field := range groupBy {
		agg, ok := aggregations.Terms(field)
		if !ok {
			// no result means there was no data for requested aggregation; lets use empty value for remaining tags as a default
			for _, f := range groupBy {
				tags[f] = ""
			}
			return nil
		}

		// zero results before we got to the lowest unwrap level
		if len(groupBy) > 0 && len(agg.Buckets) == 0 {
			// we don't know the actual values for tags that got no records
			// but we still want to return that we checked for that and found nothing, hence empty string
			for _, f := range groupBy {
				tags[f] = ""
			}
			if err := cb(tags, docCount, nil); err != nil {
				return err
			}
		}

		for _, bucket := range agg.Buckets {
			switch tag := bucket.Key.(type) {
			case float64:
				tags[field] = strconv.FormatFloat(tag, 'f', 0, 64)
			case string:
				tags[field] = tag
			default:
				return fmt.Errorf("unexpected type of aggregation bucket count: %T", bucket.Key)
			}

			if len(groupBy) > 1 {
				err := eDB.UnwrapAggregation(bucket.DocCount, bucket.Aggregations, groupBy[1:], tags, cb)
				if err != nil {
					return err
				}
				continue
			}

			if err := cb(tags, bucket.DocCount, bucket.Aggregations); err != nil {
				return err
			}
		}
	}

	return nil
}

func (eDB *ElasticDB) IndexOrAliasExists(ctx context.Context, index string) (bool, error) {
	index = eDB.resolveIndex(index)

	catAliases, err := eDB.Client.CatAliases().Alias(index).Do(ctx)
	if err != nil {
		return false, errors.Wrap(err, fmt.Sprintf("unable check if alias is used: %s", index))
	}
	if len(catAliases) > 0 {
		return true, nil
	}

	exists, err := eDB.Client.IndexExists(index).Do(ctx)
	if err != nil {
		return false, errors.Wrap(err, fmt.Sprintf("unable check if index exists: %s", index))
	}
	return exists, nil
}

func (eDB *ElasticDB) PushMapping(ctx context.Context, index string, mapping string) error {
	index = eDB.resolveIndex(index)

	// this only affects current index, not underlying index template
	result, err := eDB.Client.PutMapping().Index(index).BodyString(mapping).Do(ctx)
	if err != nil {
		return errors.Wrap(err, fmt.Sprintf("unable to update field mappings for index: %s", index))
	}
	if !result.Acknowledged {
		return errors.New(fmt.Sprintf("unable to update field mappings for index: %s, result not acknowledged", index))
	}
	return nil
}
