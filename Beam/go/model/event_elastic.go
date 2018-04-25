package model

import (
	"fmt"
	"log"
	"reflect"

	"github.com/olivere/elastic"
)

// EventElastic is ElasticDB implementation of EventStorage.
type EventElastic struct {
	DB               *ElasticDB
	categoriesCached []string
	actionsCached    map[string][]string
}

// Count returns number of events matching the filter defined by EventOptions.
func (eDB *EventElastic) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	search := eDB.DB.Client.Search().
		Index("events").
		Type("_doc").
		Size(0) // return no specific results

	search, err := eDB.DB.addSearchFilters(search, "events", options)
	if err != nil {
		return nil, false, err
	}

	search, err = eDB.DB.addGroupBy(search, "events", options, nil)
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

	// extract aggregate results
	return eDB.DB.countRowCollectionFromAggregations(result.Aggregations, options)
}

// List returns list of all events based on given EventOptions.
func (eDB *EventElastic) List(o EventOptions) (EventCollection, error) {
	ec := EventCollection{}
	return ec, nil
}

// Categories lists all tracked categories.
func (eDB *EventElastic) Categories() ([]string, error) {
	// try to load from cache first
	if ec := eDB.categoriesCached; len(ec) > 0 {
		return ec, nil
	}

	// prepare aggregation
	search := eDB.DB.Client.Search().Index("events").Type("_doc").Size(0)
	agg := elastic.NewTermsAggregation().Field("category.keyword")
	search = search.Aggregation("buckets", agg)

	// get results
	result, err := search.Do(eDB.DB.Context)
	if err != nil {
		return nil, err
	}
	aggResult, _ := result.Aggregations.Terms("buckets")

	categories := []string{}
	for _, bucket := range aggResult.Buckets {
		key, ok := bucket.Key.(string) // non-nested aggregation has string key
		if !ok {
			return nil, fmt.Errorf("unexpected type of bucket key: %T", bucket.Key)
		}
		categories = append(categories, key)
	}

	return categories, nil
}

// Flags lists all available flags.
func (eDB *EventElastic) Flags() []string {
	return []string{}
}

// Actions lists all tracked actions under the given category.
func (eDB *EventElastic) Actions(category string) ([]string, error) {
	// try to load from cache first
	if ac := eDB.actionsCached[category]; len(ac) > 0 {
		return ac, nil
	}

	// prepare aggregation
	search := eDB.DB.Client.Search().Index("events").Type("_doc").Size(0)
	agg := elastic.NewTermsAggregation().Field("action.keyword")
	search = search.Aggregation("buckets", agg)

	// set filter
	filters := elastic.NewBoolQuery().Must(elastic.NewTermQuery("category", category))
	search = search.Query(filters)

	// get results
	result, err := search.Do(eDB.DB.Context)
	if err != nil {
		return nil, err
	}
	aggResult, _ := result.Aggregations.Terms("buckets")

	actions := []string{}
	for _, bucket := range aggResult.Buckets {
		key, ok := bucket.Key.(string) // non-nested aggregation has string key
		if !ok {
			return nil, fmt.Errorf("unexpected type of bucket key: %T", bucket.Key)
		}
		actions = append(actions, key)
	}

	return actions, nil
}

// Users lists all tracked users.
func (eDB *EventElastic) Users() ([]string, error) {
	// prepare aggregation
	search := eDB.DB.Client.Search().Index("events").Type("_doc").Size(0)
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

// Cache stores event categories and activities in memory.
func (eDB *EventElastic) Cache() error {
	// cache categories
	oldc := eDB.categoriesCached
	eDB.categoriesCached = []string{} // cache niled so Categories() loads categories from DB
	cl, err := eDB.Categories()
	if err != nil {
		return err
	}
	eDB.categoriesCached = cl

	if !reflect.DeepEqual(oldc, eDB.categoriesCached) {
		log.Println("event categories cache reloaded")
	}

	// cache actions for each category
	olda := eDB.actionsCached
	eDB.actionsCached = make(map[string][]string) // cache niled so Actions() loads actions from DB
	for _, c := range cl {
		cal, err := eDB.Actions(c)
		if err != nil {
			return err
		}
		eDB.actionsCached[c] = cal
	}

	if !reflect.DeepEqual(olda, eDB.actionsCached) {
		log.Println("event actions cache reloaded")
	}

	return nil
}
