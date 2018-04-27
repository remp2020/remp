package model

import (
	"fmt"

	"github.com/olivere/elastic"
)

// CommerceElastic is ElasticDB implementation of CommerceStorage.
type CommerceElastic struct {
	DB *ElasticDB
}

// Count returns count of events based on the provided filter options.
func (cDB *CommerceElastic) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	search := cDB.DB.Client.Search().
		Index("commerce").
		Type("_doc").
		Size(0) // return no specific results

	search, err := cDB.DB.addSearchFilters(search, "commerce", options)
	if err != nil {
		return nil, false, err
	}

	search, err = cDB.DB.addGroupBy(search, "commerce", options, nil)
	if err != nil {
		return nil, false, err
	}

	// get results
	result, err := search.Do(cDB.DB.Context)
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
	return cDB.DB.countRowCollectionFromAggregations(result, options)
}

// List returns list of all events based on given CommerceOptions.
func (cDB *CommerceElastic) List(options AggregateOptions) (CommerceCollection, error) {
	return CommerceCollection{}, nil
}

// Sum returns sum of events based on the provided filter options.
func (cDB *CommerceElastic) Sum(options AggregateOptions) (SumRowCollection, bool, error) {
	extras := make(map[string]elastic.Aggregation)
	targetAgg := "revenue_sum"
	extras[targetAgg] = elastic.NewSumAggregation().Field("revenue")

	search := cDB.DB.Client.Search().
		Index("commerce").
		Type("_doc").
		Size(0) // return no specific results

	search, err := cDB.DB.addSearchFilters(search, "commerce", options)
	if err != nil {
		return nil, false, err
	}

	search, err = cDB.DB.addGroupBy(search, "commerce", options, extras)
	if err != nil {
		return nil, false, err
	}

	// get results
	result, err := search.Do(cDB.DB.Context)
	if err != nil {
		return nil, false, err
	}

	return cDB.DB.sumRowCollectionFromAggregations(result, options, targetAgg)
}

// Categories lists all available categories.
func (cDB *CommerceElastic) Categories() []string {
	return []string{
		CategoryCommerce,
	}
}

// Flags lists all available flags.
func (cDB *CommerceElastic) Flags() []string {
	return []string{}
}

// Actions lists all available actions under the given category.
func (cDB *CommerceElastic) Actions(category string) ([]string, error) {
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
