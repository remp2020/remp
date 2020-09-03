package model

import (
	"github.com/olivere/elastic/v7"
)

// ConcurrentElastic is ElasticDB implementation of ConcurrentStorage.
type ConcurrentElastic struct {
	DB            *ElasticDB
	actionsCached map[string][]string
}

func (pDB *ConcurrentElastic) getIndex() string {
	return pDB.DB.resolveIndex(TableConcurrents)
}

// Count returns number of Concurrents matching the filter defined by AggregateOptions.
func (pDB *ConcurrentElastic) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	extras := make(map[string]elastic.Aggregation)

	search := pDB.DB.Client.Search().
		Index(pDB.getIndex()).
		Size(0) // return no specific results

	search, err := pDB.DB.addSearchFilters(search, pDB.getIndex(), options)
	if err != nil {
		return nil, false, err
	}

	search, aggregationAdded, err := pDB.DB.addGroupBy(search, pDB.getIndex(), options, extras, nil)
	if err != nil {
		return nil, false, err
	}

	if !aggregationAdded {
		// allow to compute more than 10000 hits (default value) in case there is no aggregation
		search.TrackTotalHits(true)
	}

	// get results
	result, err := search.Do(pDB.DB.Context)
	if err != nil {
		return nil, false, err
	}

	if len(options.GroupBy) == 0 {
		// extract simplified results (no aggregation)
		return CountRowCollection{
			CountRow{
				Count: int(result.Hits.TotalHits.Value),
			},
		}, true, nil
	}

	// extract results
	return pDB.DB.countRowCollectionFromAggregations(result, options)
}
