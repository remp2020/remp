package model

import (
	"github.com/olivere/elastic"
)

// ConcurrentElastic is ElasticDB implementation of ConcurrentStorage.
type ConcurrentElastic struct {
	DB            *ElasticDB
	actionsCached map[string][]string
}

// Count returns number of Concurrents matching the filter defined by AggregateOptions.
func (pDB *ConcurrentElastic) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	extras := make(map[string]elastic.Aggregation)

	search := pDB.DB.Client.Search().
		Index(TableConcurrents).
		Type("_doc").
		Size(0) // return no specific results

	search, err := pDB.DB.addSearchFilters(search, TableConcurrents, options)
	if err != nil {
		return nil, false, err
	}

	search, err = pDB.DB.addGroupBy(search, TableConcurrents, options, extras, nil)
	if err != nil {
		return nil, false, err
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
				Count: int(result.Hits.TotalHits),
			},
		}, true, nil
	}

	// extract results
	return pDB.DB.countRowCollectionFromAggregations(result, options)
}
