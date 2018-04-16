package model

import (
	"fmt"
	"strconv"

	"github.com/olivere/elastic"
)

// ElasticDB represents data layer based on ElasticSearch.
type ElasticDB struct {
	Client *elastic.Client
	Debug  bool
}

func (eDB *ElasticDB) addFilters(search *elastic.SearchService, o AggregateOptions) *elastic.SearchService {
	filters := elastic.NewBoolQuery()
	for _, f := range o.FilterBy {
		if len(f.Values) == 0 {
			continue
		}
		filters = filters.Must(elastic.NewTermQuery(f.Tag, f.Values))
	}

	if o.Category != "" {
		filters = filters.Must(elastic.NewTermQuery("category", o.Category))
	}
	if o.Action != "" {
		filters = filters.Must(elastic.NewTermQuery("action", o.Action))
	}
	if o.Step != "" {
		filters = filters.Must(elastic.NewTermQuery("step", o.Step))
	}
	if !o.TimeAfter.IsZero() || !o.TimeBefore.IsZero() {
		rq := elastic.NewRangeQuery("time")
		if !o.TimeAfter.IsZero() {
			rq.Gte(o.TimeAfter)
		}
		if !o.TimeBefore.IsZero() {
			rq.Lt(o.TimeBefore)
		}
		filters = filters.Must(rq)
	}
	search = search.Query(filters)
	return search
}

func (eDB *ElasticDB) countRowCollectionFromBuckets(buckets []*elastic.AggregationBucketKeyItem) (CountRowCollection, bool, error) {
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

func (eDB *ElasticDB) sumRowCollectionFromBuckets(buckets []*elastic.AggregationBucketKeyItem) (SumRowCollection, bool, error) {
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
