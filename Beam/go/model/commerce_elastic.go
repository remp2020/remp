package model

import (
	"encoding/json"
	"fmt"
	"io"
	"strconv"

	"github.com/olivere/elastic"
	"github.com/pkg/errors"
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

	var dateHistogramAgg *elastic.DateHistogramAggregation
	if options.TimeHistogram != nil {
		dateHistogramAgg = elastic.NewDateHistogramAggregation().
			Field("time").
			Interval(options.TimeHistogram.Interval).
			TimeZone("UTC").
			Offset(options.TimeHistogram.Offset).
			MinDocCount(0).
			ExtendedBounds(options.TimeAfter, options.TimeBefore)
	}

	search, err = cDB.DB.addGroupBy(search, "commerce", options, nil, dateHistogramAgg)
	if err != nil {
		return nil, false, err
	}

	// get results
	result, err := search.Do(cDB.DB.Context)
	if err != nil {
		return nil, false, err
	}

	if len(options.GroupBy) == 0 && options.TimeHistogram == nil {
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
func (cDB *CommerceElastic) List(options ListOptions) (CommerceRowCollection, error) {
	var crc CommerceRowCollection

	fsc := elastic.NewFetchSourceContext(true).Include(options.SelectFields...)
	scroll := cDB.DB.Client.Scroll("commerce").
		Type("_doc").
		Size(1000).
		FetchSourceContext(fsc)

	scroll, err := cDB.DB.addScrollFilters(scroll, "commerce", options.AggregateOptions)
	if err != nil {
		return nil, err
	}

	// prepare EventRow buckets
	crBuckets := make(map[string]*CommerceRow)

	// get results
	for {
		results, err := scroll.Do(cDB.DB.Context)
		if err == io.EOF {
			break
		}
		if err != nil {
			return nil, errors.Wrap(err, "error while reading list data from elastic")
		}

		// Send the hits to the hits channel
		for _, hit := range results.Hits.Hits {
			// populate commerce for collection
			commerce := &Commerce{}
			if err := json.Unmarshal(*hit.Source, commerce); err != nil {
				return nil, errors.Wrap(err, "error reading commerce record from elastic")
			}
			commerce.ID = hit.Id

			// extract raw event data to build tags map
			rawCommerce := make(map[string]interface{})
			if err := json.Unmarshal(*hit.Source, &rawCommerce); err != nil {
				return nil, errors.Wrap(err, "error reading pageview record from elastic")
			}

			// we need to get string value for tags by type casting
			tags := make(map[string]string)
			key := ""
			for _, field := range options.GroupBy {
				var tagVal string
				switch val := rawCommerce[field].(type) {
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
					return nil, fmt.Errorf("unhandled tag type in pageview listing: %T", rawCommerce[field])
				}

				tags[field] = fmt.Sprintf("%s", tagVal)
				key = fmt.Sprintf("%s%s=%s_", key, field, tagVal)
			}

			// place Commerce instance into proper Commerce based on tags (key)
			cr, ok := crBuckets[key]
			if !ok {
				cr = &CommerceRow{
					Tags: tags,
				}
				crBuckets[key] = cr
			}
			cr.Commerces = append(cr.Commerces, commerce)
		}
	}

	for _, cr := range crBuckets {
		crc = append(crc, cr)
	}

	return crc, nil
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

	var dateHistogramAgg *elastic.DateHistogramAggregation
	if options.TimeHistogram != nil {
		dateHistogramAgg = elastic.NewDateHistogramAggregation().
			Field("time").
			Interval(options.TimeHistogram.Interval).
			TimeZone("UTC").
			Offset(options.TimeHistogram.Offset)
	}

	search, err = cDB.DB.addGroupBy(search, "commerce", options, extras, dateHistogramAgg)
	if err != nil {
		return nil, false, err
	}

	// get results
	result, err := search.Do(cDB.DB.Context)
	if err != nil {
		return nil, false, err
	}

	return cDB.DB.sumRowCollectionFromAggregations(result, options, targetAgg, "revenue")
}

// Categories lists all available categories.
func (cDB *CommerceElastic) Categories(o *CategoriesOptions) ([]string, error) {
	return []string{
		CategoryCommerce,
	}, nil
}

// Flags lists all available flags.
func (cDB *CommerceElastic) Flags() []string {
	return []string{}
}

// Actions lists all available actions under the given category.
func (cDB *CommerceElastic) Actions(o ActionsOptions) ([]string, error) {
	switch o.Category {
	case CategoryCommerce:
		return []string{
			"checkout",
			"payment",
			"purchase",
			"refund",
		}, nil
	}
	return nil, fmt.Errorf("unknown commerce category: %s", o.Category)
}
