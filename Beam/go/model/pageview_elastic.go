package model

import (
	"encoding/json"
	"fmt"
	"io"
	"strconv"

	"github.com/olivere/elastic/v7"
	"github.com/pkg/errors"
)

// queryBinding represents information about where and how the data should be fetched.
type elasticQueryBinding struct {
	Index string
	Field string
}

// PageviewElastic is ElasticDB implementation of PageviewStorage.
type PageviewElastic struct {
	DB            *ElasticDB
	actionsCached map[string][]string
}

// Count returns number of Pageviews matching the filter defined by PageviewOptions.
func (pDB *PageviewElastic) Count(options AggregateOptions) (CountRowCollection, bool, error) {
	// pageview events are stored in multiple measurements which need to be resolved
	binding, err := pDB.resolveQueryBindings(options.Action)
	if err != nil {
		return nil, false, err
	}

	index := pDB.DB.resolveIndex(binding.Index)

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	options.Action = ""

	var afterKey map[string]interface{}
	var crc CountRowCollection

	for {
		search := pDB.DB.Client.Search().
			Index(index).
			Size(0) // return no specific results

		search, err = pDB.DB.addSearchFilters(search, index, options)
		if err != nil {
			return nil, false, err
		}

		search, aggregationAdded, err := pDB.DB.addCompositeGroupBy(search, index, afterKey, options)
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

		if !aggregationAdded {
			// extract and return simplified results (no aggregation, no pagination)
			return CountRowCollection{
				CountRow{
					Count: int(result.Hits.TotalHits.Value),
				},
			}, true, nil
		}

		// get (paginated) aggregation results and append them to final results
		aggResult, _ := result.Aggregations.Composite("buckets")
		crcPage, err := pDB.DB.countRowCollectionFromCompositeBuckets(aggResult.Buckets, options)
		if err != nil {
			return nil, false, err
		}
		crc = append(crc, crcPage...)

		if aggResult.AfterKey == nil {
			// pagination is finished, exit
			break
		}

		afterKey = aggResult.AfterKey
	}

	return crc, true, nil
}

// Sum returns number of Pageviews matching the filter defined by AggregateOptions.
func (pDB *PageviewElastic) Sum(options AggregateOptions) (SumRowCollection, bool, error) {
	// pageview events are stored in multiple measurements which need to be resolved
	binding, err := pDB.resolveQueryBindings(options.Action)
	if err != nil {
		return nil, false, err
	}

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	options.Action = ""

	extras := make(map[string]elastic.Aggregation)
	targetAgg := fmt.Sprintf("%s_sum", binding.Field)
	extras[targetAgg] = elastic.NewSumAggregation().Field(binding.Field)

	search := pDB.DB.Client.Search().
		Index(pDB.DB.resolveIndex(binding.Index)).
		Size(0) // return no specific results

	search, err = pDB.DB.addSearchFilters(search, pDB.DB.resolveIndex(binding.Index), options)
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
			TimeZone(tz)
	}

	search, aggregationAdded, err := pDB.DB.addGroupBy(search, pDB.DB.resolveIndex(binding.Index), options, extras, dateHistogramAgg)
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

	return pDB.DB.sumRowCollectionFromAggregations(result, options, targetAgg, binding.Field)
}

// Avg returns average count of Pageviews/Timespent records matching the filter defined by AggregateOptions.
func (pDB *PageviewElastic) Avg(options AggregateOptions) (AvgRowCollection, bool, error) {
	// pageview events are stored in multiple measurements which need to be resolved
	binding, err := pDB.resolveQueryBindings(options.Action)
	if err != nil {
		return nil, false, err
	}

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	options.Action = ""

	extras := make(map[string]elastic.Aggregation)
	targetAgg := fmt.Sprintf("%s_avg", binding.Field)
	extras[targetAgg] = elastic.NewAvgAggregation().Field(binding.Field)

	search := pDB.DB.Client.Search().
		Index(pDB.DB.resolveIndex(binding.Index)).
		Size(0) // return no specific results

	search, err = pDB.DB.addSearchFilters(search, pDB.DB.resolveIndex(binding.Index), options)
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
			TimeZone(tz)
	}

	search, aggregationAdded, err := pDB.DB.addGroupBy(search, pDB.DB.resolveIndex(binding.Index), options, extras, dateHistogramAgg)
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

	return pDB.DB.avgRowCollectionFromAggregations(result, options, targetAgg, binding.Field)
}

// Unique returns unique count of Pageviews records matching the filter defined by AggregateOptions.
func (pDB *PageviewElastic) Unique(options AggregateOptions, item string) (CountRowCollection, bool, error) {
	var binding elasticQueryBinding
	switch item {
	case UniqueCountBrowsers:
		binding = elasticQueryBinding{
			Index: TablePageviews,
			Field: "browser_id.keyword",
		}
	case UniqueCountUsers:
		binding = elasticQueryBinding{
			Index: TablePageviews,
			Field: "user_id.keyword",
		}
	default:
		return nil, false, fmt.Errorf("unable to count uniques for item [%s] ", item)
	}

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	options.Action = ""

	extras := make(map[string]elastic.Aggregation)
	targetAgg := fmt.Sprintf("%s_unique", binding.Field)
	extras[targetAgg] = elastic.NewCardinalityAggregation().Field(binding.Field)

	search := pDB.DB.Client.Search().
		Index(pDB.DB.resolveIndex(binding.Index)).
		Size(0) // return no specific results

	search, err := pDB.DB.addSearchFilters(search, pDB.DB.resolveIndex(binding.Index), options)
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
			TimeZone(tz)
	}

	search, aggregationAdded, err := pDB.DB.addGroupBy(search, pDB.DB.resolveIndex(binding.Index), options, extras, dateHistogramAgg)
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

	return pDB.DB.uniqueRowCollectionFromAggregations(result, options, targetAgg, binding.Field)
}

// List returns list of all Pageviews based on given PageviewOptions.
func (pDB *PageviewElastic) List(options ListPageviewsOptions) (PageviewRowCollection, error) {
	var prc PageviewRowCollection

	index := pDB.DB.resolveIndex("pageviews")

	fsc := elastic.NewFetchSourceContext(true).Include(options.SelectFields...)
	scroll := pDB.DB.Client.Scroll(index).
		Size(1000).
		FetchSourceContext(fsc)

	scroll, err := pDB.DB.addScrollFilters(scroll, index, options.AggregateOptions)
	if err != nil {
		return nil, err
	}
	defer scroll.Clear(pDB.DB.Context)

	// prepare PageviewRow buckets
	prBuckets := make(map[string]*PageviewRow)

	pageviewIDs := []string{}

	// get results
	for {
		results, err := scroll.Do(pDB.DB.Context)
		if err == io.EOF {
			break
		}
		if err != nil {
			return nil, errors.Wrap(err, "error while reading list data from elastic")
		}

		// Send the hits to the hits channel
		for _, hit := range results.Hits.Hits {
			// populate pageview for collection
			pv := &Pageview{}
			if err := json.Unmarshal(hit.Source, pv); err != nil {
				return nil, errors.Wrap(err, "error reading pageview record from elastic")
			}
			pv.ID = hit.Id

			// extract raw pageview data to build tags map
			rawPv := make(map[string]interface{})
			if err := json.Unmarshal(hit.Source, &rawPv); err != nil {
				return nil, errors.Wrap(err, "error reading pageview record from elastic")
			}

			// we need to get string value for tags by type casting
			tags := make(map[string]string)
			key := ""
			for _, field := range options.GroupBy {
				var tagVal string
				switch val := rawPv[field].(type) {
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
					return nil, fmt.Errorf("unhandled tag type in pageview listing: %T", rawPv[field])
				}

				tags[field] = fmt.Sprintf("%s", tagVal)
				key = fmt.Sprintf("%s%s=%s_", key, field, tagVal)
			}

			// place Pageview instance into proper PageviewRow based on tags (key)
			pr, ok := prBuckets[key]
			if !ok {
				pr = &PageviewRow{
					Tags: tags,
				}
				prBuckets[key] = pr
			}

			if pv.ID != "" {
				pageviewIDs = append(pageviewIDs, pv.ID)
			}

			pr.Pageviews = append(pr.Pageviews, pv)
		}
	}

	// Load timespent
	timespentForPageviews := make(map[string]int)
	if len(pageviewIDs) > 0 && options.LoadTimespent {
		timespentForPageviews, err = loadTimespent(pDB, pageviewIDs)
		if err != nil {
			return nil, err
		}
	}

	// Load page and article progress
	articleProgressForPageviews := make(map[string]float32)
	pageProgressForPageviews := make(map[string]float32)
	if len(pageviewIDs) > 0 && options.LoadProgress {
		pageProgressForPageviews, articleProgressForPageviews, err = loadProgress(pDB, pageviewIDs)
		if err != nil {
			return nil, err
		}
	}

	for _, pr := range prBuckets {
		for _, pv := range pr.Pageviews {
			if timespent, ok := timespentForPageviews[pv.ID]; ok {
				pv.Timespent = timespent
			}
			if articleProgress, ok := articleProgressForPageviews[pv.ID]; ok {
				pv.ArticleProgress = articleProgress
			}
			if pageProgress, ok := pageProgressForPageviews[pv.ID]; ok {
				pv.PageProgress = pageProgress
			}
		}

		prc = append(prc, pr)
	}

	return prc, nil
}

func loadTimespent(pDB *PageviewElastic, pageviewIDs []string) (map[string]int, error) {
	index := pDB.DB.resolveIndex("pageviews_time_spent")

	fsc := elastic.NewFetchSourceContext(true).Include("timespent", "remp_pageview_id")
	scroll := pDB.DB.Client.Scroll(index).
		Size(1000).
		FetchSourceContext(fsc)

	var ao AggregateOptions

	fb := &FilterBy{
		Tag:     "remp_pageview_id",
		Values:  pageviewIDs,
		Inverse: false,
	}
	ao.FilterBy = append(ao.FilterBy, fb)

	scroll, err := pDB.DB.addScrollFilters(scroll, index, ao)
	if err != nil {
		return nil, err
	}
	defer scroll.Clear(pDB.DB.Context)

	timespentForPageviews := make(map[string]int)

	for {
		results, err := scroll.Do(pDB.DB.Context)
		if err == io.EOF {
			break
		}
		if err != nil {
			return nil, errors.Wrap(err, "error while reading list data from elastic")
		}

		for _, hit := range results.Hits.Hits {
			pv := &Pageview{}
			if err := json.Unmarshal(hit.Source, pv); err != nil {
				return nil, errors.Wrap(err, "error reading timespent record from elastic")
			}
			timespentForPageviews[hit.Id] = pv.Timespent
		}
	}

	return timespentForPageviews, nil
}

func loadProgress(pDB *PageviewElastic, pageviewIDs []string) (map[string]float32, map[string]float32, error) {
	index := pDB.DB.resolveIndex("pageviews_progress")
	fsc := elastic.NewFetchSourceContext(true).Include(
		"article_progress",
		"page_progress",
		"remp_pageview_id",
	)
	scroll := pDB.DB.Client.Scroll(index).Size(1000).FetchSourceContext(fsc)

	var ao AggregateOptions

	fb := &FilterBy{
		Tag:     "remp_pageview_id",
		Values:  pageviewIDs,
		Inverse: false,
	}
	ao.FilterBy = append(ao.FilterBy, fb)

	scroll, err := pDB.DB.addScrollFilters(scroll, index, ao)
	if err != nil {
		return nil, nil, err
	}
	defer scroll.Clear(pDB.DB.Context)

	pageProgressForPageviews := make(map[string]float32)
	articleProgressForPageviews := make(map[string]float32)

	for {
		results, err := scroll.Do(pDB.DB.Context)
		if err == io.EOF {
			break
		}
		if err != nil {
			return nil, nil, errors.Wrap(err, "error while reading list data from elastic")
		}

		for _, hit := range results.Hits.Hits {
			pv := &Pageview{}
			if err := json.Unmarshal(hit.Source, pv); err != nil {
				return nil, nil, errors.Wrap(err, "error reading timespent record from elastic")
			}
			pageProgressForPageviews[hit.Id] = pv.PageProgress
			articleProgressForPageviews[hit.Id] = pv.ArticleProgress

		}
	}

	return pageProgressForPageviews, articleProgressForPageviews, nil
}

// Categories lists all tracked categories.
func (pDB *PageviewElastic) Categories() ([]string, error) {
	return []string{
		CategoryPageview,
	}, nil
}

// Flags lists all available flags.
func (pDB *PageviewElastic) Flags() []string {
	return []string{
		FlagArticle,
	}
}

// Actions lists all tracked actions under the given category.
func (pDB *PageviewElastic) Actions(category string) ([]string, error) {
	switch category {
	case CategoryPageview:
		return []string{
			ActionPageviewLoad,
		}, nil
	}
	return nil, fmt.Errorf("unknown pageview category: %s", category)
}

// Users lists all tracked users.
func (pDB *PageviewElastic) Users() ([]string, error) {
	// prepare aggregation
	search := pDB.DB.Client.Search().Index(pDB.DB.resolveIndex("pageviews")).Size(0)
	agg := elastic.NewTermsAggregation().Field("user_id.keyword")
	search = search.Aggregation("buckets", agg)

	// get results
	result, err := search.Do(pDB.DB.Context)
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

// resolveQueryBindings returns name of the table and field used within the aggregate function
// based on the provided action.
func (pDB *PageviewElastic) resolveQueryBindings(action string) (elasticQueryBinding, error) {
	switch action {
	case ActionPageviewLoad:
		return elasticQueryBinding{
			Index: TablePageviews,
			Field: "token",
		}, nil
	case ActionPageviewTimespent:
		return elasticQueryBinding{
			Index: TableTimespent,
			Field: "timespent",
		}, nil
	case ActionPageviewProgress:
		return elasticQueryBinding{
			Index: TableProgress,
			Field: "page_progress",
		}, nil
	}

	return elasticQueryBinding{}, fmt.Errorf("unable to resolve query bindings: action [%s] unknown", action)
}
