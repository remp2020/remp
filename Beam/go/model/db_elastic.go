package model

import (
	"context"
	"fmt"
	"math"
	"strconv"
	"time"

	"github.com/olivere/elastic"
	"github.com/pkg/errors"
)

// ElasticDB represents data layer based on ElasticSearch.
type ElasticDB struct {
	Client  *elastic.Client
	Debug   bool
	Context context.Context

	fieldsCache map[string]map[string]bool // fields cache represents list of all index (map key) fields (map values)
}

// NewElasticDB returns new instance of ElasticSearch DB implementation
func NewElasticDB(ctx context.Context, client *elastic.Client, debug bool) *ElasticDB {
	edb := &ElasticDB{
		Client:  client,
		Debug:   debug,
		Context: ctx,
	}
	edb.fieldsCache = make(map[string]map[string]bool)
	return edb
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
		bq = bq.Must(elastic.NewTermsQuery(field, interfaceSlice...))
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
func (eDB *ElasticDB) addGroupBy(search *elastic.SearchService, index string, o AggregateOptions,
	extras map[string]elastic.Aggregation, dateHistogramAgg *elastic.DateHistogramAggregation) (*elastic.SearchService, error) {

	if len(o.GroupBy) > 0 || len(extras) > 0 || dateHistogramAgg != nil {
		var err error
		search, _, err = eDB.WrapAggregation(index, o.GroupBy, search, extras, dateHistogramAgg, nil)
		if err != nil {
			return nil, err
		}
	}
	return search, nil
}

// countRowCollectionFromAggregations generates CountRowCollection based on query result aggregations.
func (eDB *ElasticDB) countRowCollectionFromAggregations(result *elastic.SearchResult, options AggregateOptions) (CountRowCollection, bool, error) {
	var crc CountRowCollection
	tags := make(map[string]string)

	eDB.UnwrapAggregation(result.Hits.TotalHits, result.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggregations elastic.Aggregations) error {
		crcTags := make(map[string]string)

		var histogram []HistogramItem

		if options.TimeHistogram != nil {
			histogramData, ok := aggregations.DateHistogram("date_time_histogram")

			if !ok {
				errors.New("missing expected histogram aggregation data")
			}

			for _, histogramItem := range histogramData.Buckets {
				time, err := time.Parse(time.RFC3339, *histogramItem.KeyAsString)

				if err != nil {
					errors.New("cant parse time from elastic search with RFC3339 layout")
				}

				histogram = append(histogram, HistogramItem{
					Time:  time,
					Value: int(histogramItem.DocCount),
				})
			}
		}

		// copy tags to avoid memory sharing
		for key, val := range tags {
			crcTags[key] = val
		}

		crc = append(crc, CountRow{
			Tags:      crcTags,
			Count:     int(count),
			Histogram: histogram,
		})

		return nil
	})

	return crc, true, nil
}

// sumRowCollectionFromAggregations generates SumRowCollection based on query result aggregations.
func (eDB *ElasticDB) sumRowCollectionFromAggregations(result *elastic.SearchResult, options AggregateOptions, targetAgg string) (SumRowCollection, bool, error) {
	var src SumRowCollection
	dataPresent := true
	tags := make(map[string]string)

	err := eDB.UnwrapAggregation(result.Hits.TotalHits, result.Aggregations, options.GroupBy, tags, func(tags map[string]string, count int64, aggregations elastic.Aggregations) error {

		var histogram []HistogramItem
		var sumValue float64

		if options.TimeHistogram != nil {
			histogramData, ok := aggregations.DateHistogram("date_time_histogram")

			if !ok {
				return errors.New("missing expected histogram aggregation data")
			}

			for _, histogramItem := range histogramData.Buckets {

				agg, ok := histogramItem.Aggregations.Sum("timespent_sum")
				if !ok {
					errors.New("cant find timespent_sum sub agg in date histogram agg")
				}

				time, err := time.Parse(time.RFC3339, *histogramItem.KeyAsString)
				if err != nil {
					errors.New("cant parse time from elastic search with RFC3339 layout")
				}

				histogram = append(histogram, HistogramItem{
					Time:  time,
					Value: int(*agg.Value),
				})
			}
		} else {
			sumAgg, ok := aggregations.Sum(targetAgg)

			if sumAgg.Value != nil {
				sumValue = *sumAgg.Value
			}

			if !ok {
				dataPresent = false
				return nil
			}
		}

		srcTags := make(map[string]string)
		// copy tags to avoid memory sharing
		for key, val := range tags {
			srcTags[key] = val
		}

		src = append(src, SumRow{
			Tags:      srcTags,
			Sum:       sumValue,
			Histogram: histogram,
		})

		return nil
	})

	if err != nil {
		return nil, false, err
	}

	return src, dataPresent, nil
}

// addCompositeGroupBy creates a composite aggregation. The results are fetchable
// via countRowCollectionFromCompositeBuckets or sumRowCollectionFromCompositeBuckets.
func (eDB *ElasticDB) addCompositeGroupBy(search *elastic.SearchService, index string, o AggregateOptions) (*elastic.SearchService, error) {
	if len(o.GroupBy) > 0 {
		nestedAgg := elastic.NewCompositeAggregation()
		for _, g := range o.GroupBy {
			f, err := eDB.resolveKeyword(index, g)
			if err != nil {
				return nil, err
			}
			agg := elastic.NewCompositeAggregationTermsValuesSource(g).Field(f)
			nestedAgg = nestedAgg.Sources(agg)
		}

		search = search.Aggregation("buckets", nestedAgg)
	}

	return search, nil
}

// countRowCollectionFromCompositeBuckets extracts CountRow data from buckets generated by composite aggregation query.
func (eDB *ElasticDB) countRowCollectionFromCompositeBuckets(buckets []*elastic.AggregationBucketKeyItem) (CountRowCollection, bool, error) {
	crc := CountRowCollection{}

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
		crc = append(crc, CountRow{
			Tags:  tags,
			Count: int(bucket.DocCount),
		})
	}
	return crc, true, nil
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

	// populate cache for given index if it's empty
	if !ok {
		result, err := eDB.Client.GetFieldMapping().Index(index).Type("_doc").Do(eDB.Context)
		if err != nil {
			return "", errors.Wrap(err, "unable to get field mappings for pageviews index")
		}

		root, ok := result[index].(map[string]interface{})
		if !ok {
			return "", fmt.Errorf("invalid index provided, no mapping data available: %s", index)
		}
		mappings, ok := root["mappings"].(map[string]interface{})
		if !ok {
			return "", fmt.Errorf("\"mappings\" field not present within field mapping response")
		}
		doc, ok := mappings["_doc"].(map[string]interface{})
		if !ok {
			return "", fmt.Errorf("invalid document provided, no mapping data available for document: _doc")
		}

		fields = make(map[string]bool)
		for f := range doc {
			fields[f] = true
		}
		eDB.fieldsCache[index] = fields
	}

	// check if keyword is present among fields
	keyword := fmt.Sprintf("%s.keyword", field)
	_, ok = fields[keyword]
	if !ok {
		return field, nil
	}
	return keyword, nil
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

		termsAgg := elastic.NewTermsAggregation().Field(keyword).Size(math.MaxInt32)

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
			return fmt.Errorf("result is missing data for term aggregation: %s", field)
		}

		// zero results before we got to the lowest unwrap level
		if len(groupBy) > 0 && len(agg.Buckets) == 0 {
			// we don't know Fiel the actual values for tags that got no records
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
				eDB.UnwrapAggregation(bucket.DocCount, bucket.Aggregations, groupBy[1:], tags, cb)
				return nil
			}

			if err := cb(tags, bucket.DocCount, bucket.Aggregations); err != nil {
				return err
			}
		}
	}

	return nil
}
