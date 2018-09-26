package model

import (
	"errors"
	"fmt"
	"log"
	"strings"

	"github.com/influxdata/influxdb/client/v2"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

// influxQueryBinding represents information about where and how the data should be fetched.
type influxQueryBinding struct {
	Measurement string
	Field       string
}

// PageviewInflux is Influx implementation of PageviewStorage.
type PageviewInflux struct {
	DB *InfluxDB
}

// Count returns count of pageviews based on the provided filter options.
func (eDB *PageviewInflux) Count(o AggregateOptions) (CountRowCollection, bool, error) {
	// pageview events are stored in multiple measurements which need to be resolved
	binding, err := eDB.resolveQueryBindings(o.Action)
	if err != nil {
		return nil, false, err
	}

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	o.Action = ""

	builder := eDB.DB.QueryBuilder.Select(fmt.Sprintf("COUNT(%s)", binding.Field)).From(fmt.Sprintf("%s", binding.Measurement))
	builder = addAggregateQueryFilters(builder, o)

	bb := builder.Build()
	log.Println("pageview count query:", bb)

	q := client.Query{
		Command:  bb,
		Database: eDB.DB.DBName,
	}

	response, err := eDB.DB.Client.Query(q)
	if err != nil {
		return nil, false, err
	}
	if response.Error() != nil {
		return nil, false, response.Error()
	}

	// process response
	return eDB.DB.MultiGroupedCount(response)
}

// Sum returns sum of pageviews based on the provided filter options.
func (eDB *PageviewInflux) Sum(o AggregateOptions) (SumRowCollection, bool, error) {
	// pageview events are stored in multiple measurements which need to be resolved
	binding, err := eDB.resolveQueryBindings(o.Action)
	if err != nil {
		return nil, false, err
	}

	// action is not being tracked within separate measurements and we would get no records back
	// removing it before applying filter
	o.Action = ""

	builder := eDB.DB.QueryBuilder.Select(fmt.Sprintf("SUM(%s)", binding.Field)).From(fmt.Sprintf("%s", binding.Measurement))
	builder = addAggregateQueryFilters(builder, o)

	bb := builder.Build()
	log.Println("pageview sum query:", bb)

	q := client.Query{
		Command:  bb,
		Database: eDB.DB.DBName,
	}

	response, err := eDB.DB.Client.Query(q)
	if err != nil {
		return nil, false, err
	}
	if response.Error() != nil {
		return nil, false, response.Error()
	}

	// process response
	return eDB.DB.GroupedSum(response)
}

// Avg is not implemented as Influx will be removed in the future
func (eDB *PageviewInflux) Avg(o AggregateOptions) (AvgRowCollection, bool, error) {
	return nil, false, errors.New("avg method not implemented")
}

// Unique is not implemented as Influx will be removed in the future
func (eDB *PageviewInflux) Unique(o AggregateOptions, item string) (CountRowCollection, bool, error) {
	return nil, false, errors.New("unique method not implemented")
}

// List returns list of all pageviews based on given PageviewOptions.
func (eDB *PageviewInflux) List(o ListOptions) (PageviewRowCollection, error) {
	var selectFields string
	if len(o.SelectFields) > 0 {
		o.SelectFields = append(o.SelectFields, "token", "remp_pageview_id") // at least one value needs to be always selected
		selectFields = strings.Join(o.SelectFields, ",")
	} else {
		selectFields = "*"
	}

	builder := eDB.DB.QueryBuilder.Select(selectFields).From(`"` + TablePageviews + `"`)
	builder = addAggregateQueryFilters(builder, o.AggregateOptions)

	q := client.Query{
		Command:  builder.Build(),
		Database: eDB.DB.DBName,
	}
	log.Println("pageview list query:", q.Command)

	response, err := eDB.DB.Client.Query(q)
	if err != nil {
		return nil, err
	}
	if response.Error() != nil {
		return nil, response.Error()
	}

	prc := PageviewRowCollection{}

	// no data returned
	if len(response.Results[0].Series) == 0 {
		return prc, nil
	}

	for _, s := range response.Results[0].Series {
		row := &PageviewRow{
			Tags: s.Tags,
		}
		for idx := range s.Values {
			ir := influxquery.NewInfluxResult(s, idx)
			p, err := pageviewFromInfluxResult(ir)
			if err != nil {
				return nil, err
			}
			row.Pageviews = append(row.Pageviews, p)
		}
		prc = append(prc, row)
	}

	return prc, nil
}

// Categories lists all tracked categories.
func (eDB *PageviewInflux) Categories() []string {
	return []string{
		CategoryPageview,
	}
}

// Flags lists all available flags.
func (eDB *PageviewInflux) Flags() []string {
	return []string{
		FlagArticle,
	}
}

// Actions lists all tracked actions under the given category.
func (eDB *PageviewInflux) Actions(category string) ([]string, error) {
	switch category {
	case CategoryPageview:
		return []string{
			ActionPageviewLoad,
		}, nil
	}
	return nil, fmt.Errorf("unknown pageview category: %s", category)
}

// Users returns list of all tracked user IDs.
func (eDB *PageviewInflux) Users() ([]string, error) {
	q := client.Query{
		Command:  `SHOW TAG VALUES FROM "` + TablePageviews + `" WITH KEY = "user_id"`,
		Database: eDB.DB.DBName,
	}

	response, err := eDB.DB.Client.Query(q)
	if err != nil {
		return nil, err
	}
	if response.Error() != nil {
		return nil, response.Error()
	}

	users := []string{}
	if len(response.Results[0].Series) == 0 {
		return users, nil
	}
	for _, val := range response.Results[0].Series[0].Values {
		strVal, ok := val[1].(string)
		if !ok {
			return nil, errors.New("unable to convert influx result value to string")
		}
		users = append(users, strVal)
	}
	return users, nil
}

// resolveQueryBindings returns name of the table and field used within the aggregate function
// based on the provided action.
func (eDB *PageviewInflux) resolveQueryBindings(action string) (influxQueryBinding, error) {
	switch action {
	case ActionPageviewLoad:
		return influxQueryBinding{
			Measurement: TablePageviews,
			Field:       "token",
		}, nil
	case ActionPageviewTimespent:
		return influxQueryBinding{
			Measurement: TableTimespentAggregated,
			Field:       "sum",
		}, nil
	}
	return influxQueryBinding{}, fmt.Errorf("unable to resolve query bindings: action [%s] unknown", action)
}

func pageviewFromInfluxResult(ir *influxquery.Result) (*Pageview, error) {
	token, ok := ir.StringValue("token")
	if !ok {
		return nil, errors.New("unable to map Token to influx result column")
	}
	t, ok, err := ir.TimeValue("time")
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, errors.New("unable to map Time to influx result column")
	}
	pageview := &Pageview{
		Token: token,
		Time:  t,
	}

	if utmSource, ok := ir.StringValue("utm_source"); ok {
		pageview.UTMSource = utmSource
	}
	if utmMedium, ok := ir.StringValue("utm_medium"); ok {
		pageview.UTMMedium = utmMedium
	}
	if utmCampaign, ok := ir.StringValue("utm_campaign"); ok {
		pageview.UTMCampaign = utmCampaign
	}
	if utmContent, ok := ir.StringValue("utm_content"); ok {
		pageview.UTMContent = utmContent
	}
	if social, ok := ir.StringValue("social"); ok {
		pageview.SocialSource = social
	}
	if ip, ok := ir.StringValue("ip"); ok {
		pageview.IP = ip
	}
	if userID, ok := ir.StringValue("user_id"); ok {
		pageview.UserID = userID
	}
	if url, ok := ir.StringValue("url"); ok {
		pageview.URL = url
	}
	if userAgent, ok := ir.StringValue("user_agent"); ok {
		pageview.UserAgent = userAgent
	}
	if referer, ok := ir.StringValue("referer"); ok {
		pageview.Referer = referer
	}
	if browserID, ok := ir.StringValue("browser_id"); ok {
		pageview.BrowserID = browserID
	}
	if subscriber, ok := ir.BoolValue("subscriber"); ok {
		pageview.Subscriber = subscriber
	}
	if articleLocked, ok := ir.BoolValue("locked"); ok {
		pageview.ArticleLocked = articleLocked
	}
	if sessionID, ok := ir.StringValue("remp_session_id"); ok {
		pageview.SessionID = sessionID
	}
	if pageviewID, ok := ir.StringValue("remp_pageview_id"); ok {
		pageview.PageviewID = pageviewID
	}

	return pageview, nil
}
