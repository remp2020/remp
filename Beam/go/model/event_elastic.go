package model

import (
	"encoding/json"
	"fmt"
	"io"
	"log"
	"reflect"
	"strconv"

	"github.com/olivere/elastic/v7"
	"github.com/pkg/errors"
)

// EventElastic is ElasticDB implementation of EventStorage.
type EventElastic struct {
	DB               *ElasticDB
	categoriesCached []string
	actionsCached    map[string][]string
}

func (eDB *EventElastic) getIndex() string {
	return eDB.DB.resolveIndex("events")
}

// Count returns number of events matching the filter defined by EventOptions.
func (eDB *EventElastic) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	extras := make(map[string]elastic.Aggregation)

	search := eDB.DB.Client.Search().
		Index(eDB.getIndex()).
		Size(0) // return no specific results

	search, err := eDB.DB.addSearchFilters(search, eDB.getIndex(), options)
	if err != nil {
		return nil, false, err
	}

	var dateHistogramAgg *elastic.DateHistogramAggregation

	if options.TimeHistogram != nil {
		tz := "UTC"
		if options.TimeHistogram.TimeZone != nil {
			tz = options.TimeHistogram.TimeZone.String()
		}

		dateHistogramAgg = elastic.NewDateHistogramAggregation().
			Field("time").
			FixedInterval(options.TimeHistogram.Interval).
			TimeZone(tz).
			MinDocCount(0).
			ExtendedBounds(options.TimeAfter, options.TimeBefore)
	}

	search, aggregationAdded, err := eDB.DB.addGroupBy(search, eDB.getIndex(), options, extras, dateHistogramAgg)
	if err != nil {
		return nil, false, err
	}

	if !aggregationAdded {
		// allow to compute more than 10000 hits (default value) in case there is no aggregation
		search.TrackTotalHits(true)
	}

	// get results
	result, err := search.Do(eDB.DB.Context)
	if err != nil {
		return nil, false, err
	}

	if len(options.GroupBy) == 0 && options.TimeHistogram == nil {
		// extract simplified results (no aggregation)
		return CountRowCollection{
			CountRow{
				Count: int(result.Hits.TotalHits.Value),
			},
		}, true, nil
	}

	// extract aggregate results
	return eDB.DB.countRowCollectionFromAggregations(result, options)
}

// List returns list of all events based on given EventOptions.
func (eDB *EventElastic) List(options ListOptions) (EventRowCollection, error) {
	var erc EventRowCollection

	fsc := elastic.NewFetchSourceContext(true).Include(options.SelectFields...)
	scroll := eDB.DB.Client.Scroll(eDB.getIndex()).
		Size(1000).
		FetchSourceContext(fsc)

	scroll, err := eDB.DB.addScrollFilters(scroll, eDB.getIndex(), options.AggregateOptions)
	if err != nil {
		return nil, err
	}
	defer scroll.Clear(eDB.DB.Context)

	// prepare EventRow buckets
	erBuckets := make(map[string]*EventRow)

	// get results
	for {
		results, err := scroll.Do(eDB.DB.Context)
		if err == io.EOF {
			break
		}
		if err != nil {
			return nil, errors.Wrap(err, "error while reading list data from elastic")
		}

		// Send the hits to the hits channel
		for _, hit := range results.Hits.Hits {
			// populate event for collection
			event := &Event{}
			if err := json.Unmarshal(hit.Source, event); err != nil {
				return nil, errors.Wrap(err, "error reading pageview record from elastic")
			}
			event.ID = hit.Id

			// extract raw event data to build tags map
			rawEvent := make(map[string]interface{})
			if err := json.Unmarshal(hit.Source, &rawEvent); err != nil {
				return nil, errors.Wrap(err, "error reading pageview record from elastic")
			}

			// we need to get string value for tags by type casting
			tags := make(map[string]string)
			key := ""
			for _, field := range options.GroupBy {
				var tagVal string
				switch val := rawEvent[field].(type) {
				case nil:
					tagVal = ""
				case bool:
					if val {
						tagVal = "1"
					} else {
						tagVal = "0"
					}
				case string:
					tagVal = val
				case float64:
					tagVal = strconv.FormatFloat(val, 'f', 0, 64)
				case int64:
					tagVal = strconv.FormatInt(val, 10)
				default:
					return nil, fmt.Errorf("unhandled tag type in pageview listing: %T", rawEvent[field])
				}

				tags[field] = tagVal
				key = fmt.Sprintf("%s%s=%s_", key, field, tagVal)
			}

			// place Event instance into proper EventRow based on tags (key)
			er, ok := erBuckets[key]
			if !ok {
				er = &EventRow{
					Tags: tags,
				}
				erBuckets[key] = er
			}
			er.Events = append(er.Events, event)
		}
	}

	for _, er := range erBuckets {
		erc = append(erc, er)
	}

	return erc, nil
}

// Categories lists all tracked categories.
func (eDB *EventElastic) Categories() ([]string, error) {
	// try to load from cache first
	if ec := eDB.categoriesCached; len(ec) > 0 {
		return ec, nil
	}

	// prepare aggregation
	search := eDB.DB.Client.Search().Index(eDB.getIndex()).Size(0)
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
	search := eDB.DB.Client.Search().Index(eDB.getIndex()).Size(0)
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
	search := eDB.DB.Client.Search().Index(eDB.getIndex()).Size(0)
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
