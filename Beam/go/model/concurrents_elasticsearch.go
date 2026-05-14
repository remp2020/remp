package model

import (
	"github.com/elastic/go-elasticsearch/v8/typedapi/core/search"
)

// ConcurrentElasticsearch is official ElasticsearchDB implementation of ConcurrentsStorage.
type ConcurrentElasticsearch struct {
	DB *ElasticsearchDB
}

func (pDB *ConcurrentElasticsearch) getIndex() string {
	return pDB.DB.resolveIndex(TableConcurrents)
}

// Count returns number of Concurrents matching the filter defined by AggregateOptions.
func (pDB *ConcurrentElasticsearch) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	query, err := pDB.DB.BuildBoolQuery(pDB.getIndex(), options)
	if err != nil {
		return nil, false, err
	}

	aggs, aggregationAdded, err := pDB.DB.BuildGroupByAggregation(pDB.getIndex(), options, nil, nil)
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

	res, err := pDB.DB.SearchTyped(pDB.getIndex(), req)
	if err != nil {
		return nil, false, err
	}

	if len(options.GroupBy) == 0 {
		// extract simplified results (no aggregation)
		var count int
		if res.Hits.Total != nil {
			count = int(res.Hits.Total.Value)
		}
		return CountRowCollection{CountRow{Count: count}}, true, nil
	}

	return pDB.DB.countRowCollectionFromTypedResponse(res, options)
}
