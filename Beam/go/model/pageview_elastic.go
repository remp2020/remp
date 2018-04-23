package model

import (
	"fmt"
	"log"

	"github.com/olivere/elastic"
)

// PageviewElastic is ElasticDB implementation of PageviewStorage.
type PageviewElastic struct {
	DB            *ElasticDB
	actionsCached map[string][]string
}

// Count returns number of Pageviews matching the filter defined by PageviewOptions.
func (eDB *PageviewElastic) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	search := eDB.DB.Client.Search().
		Index("pageviews").
		Type("_doc").
		Size(0) // return no specific results

	search, err := eDB.DB.addFilters(search, "pageviews", options)
	if err != nil {
		return nil, false, err
	}

	search, err = eDB.DB.addGroupBy(search, "pageviews", options)
	if err != nil {
		return nil, false, err
	}

	// get results
	result, err := search.Do(eDB.DB.Context)
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
	return eDB.DB.countRowCollectionFromBuckets(result.Aggregations, options)
}

// Sum returns number of Pageviews matching the filter defined by AggregateOptions.
func (eDB *PageviewElastic) Sum(o AggregateOptions) (SumRowCollection, bool, error) {
	search := eDB.DB.Client.Search().
		Index("pageviews").
		Type("_doc").
		Size(0) // return no specific results

	search, err := eDB.DB.addFilters(search, "pageviews", o)
	if err != nil {
		return nil, false, err
	}

	search, err = eDB.DB.addGroupBy(search, "pageviews", o)
	if err != nil {
		return nil, false, err
	}

	// get results
	result, err := search.Do(eDB.DB.Context)
	if err != nil {
		return nil, false, err
	}

	log.Println(result)

	return SumRowCollection{}, true, nil
}

// List returns list of all Pageviews based on given PageviewOptions.
func (eDB *PageviewElastic) List(o ListOptions) (PageviewRowCollection, error) {
	ec := PageviewRowCollection{}
	return ec, nil
}

// Categories lists all tracked categories.
func (eDB *PageviewElastic) Categories() []string {
	return []string{
		CategoryPageview,
	}
}

// Flags lists all available flags.
func (eDB *PageviewElastic) Flags() []string {
	return []string{
		FlagArticle,
	}
}

// Actions lists all tracked actions under the given category.
func (eDB *PageviewElastic) Actions(category string) ([]string, error) {
	switch category {
	case CategoryPageview:
		return []string{
			ActionPageviewLoad,
		}, nil
	}
	return nil, fmt.Errorf("unknown pageview category: %s", category)
}

// Users lists all tracked users.
func (eDB *PageviewElastic) Users() ([]string, error) {
	// prepare aggregation
	search := eDB.DB.Client.Search().Index("Pageviews").Type("_doc").Size(0)
	agg := elastic.NewTermsAggregation().Field("user_id.keyword")
	search = search.Aggregation("buckets", agg)

	// get results
	result, err := search.Do(eDB.DB.Context)
	if err != nil {
		return nil, err
	}
	aggResult, _ := result.Aggregations.Terms("buckets")

	users := []string{}
	for _, bucket := range aggResult.Buckets {
		key, ok := bucket.Key.(string) // non-nested aggregation has string key
		if !ok {
			return nil, fmt.Errorf("unexpected type of bucket key: %T", bucket.Key)
		}
		users = append(users, key)
	}

	return users, nil
}
