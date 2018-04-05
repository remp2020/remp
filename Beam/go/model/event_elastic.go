package model

import (
	"context"
	"log"

	"github.com/olivere/elastic"
)

// EventElastic is ElasticDB implementation of EventStorage.
type EventElastic struct {
	DB               *ElasticDB
	categoriesCached []string
	actionsCached    map[string][]string
}

// Count returns number of events matching the filter defined by EventOptions.
func (eDB *EventElastic) Count(o AggregateOptions) (CountRowCollection, bool, error) {
	log.Println("DEBUG")
	search := eDB.DB.Client.Search().Index("events").Type("_doc")

	for _, g := range o.GroupBy {
		agg := elastic.NewTermsAggregation().Field(g)
		search = search.Aggregation("buckets", agg)
	}

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
	result, err := search.Do(context.Background())
	if err != nil {
		return nil, false, err
	}

	log.Println(result.Aggregations)

	return CountRowCollection{}, true, nil
}

// List returns list of all events based on given EventOptions.
func (eDB *EventElastic) List(o EventOptions) (EventCollection, error) {
	ec := EventCollection{}
	return ec, nil
}

// Categories lists all tracked categories.
func (eDB *EventElastic) Categories() ([]string, error) {
	categories := []string{}
	return categories, nil
}

// Flags lists all available flags.
func (eDB *EventElastic) Flags() []string {
	return []string{}
}

// Actions lists all tracked actions under the given category.
func (eDB *EventElastic) Actions(category string) ([]string, error) {
	actions := []string{}
	return actions, nil
}

// Users lists all tracked users.
func (eDB *EventElastic) Users() ([]string, error) {
	users := []string{}
	return users, nil
}

// Cache stores event categories and activities in memory.
func (eDB *EventElastic) Cache() error {
	return nil
}
