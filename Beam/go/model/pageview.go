package model

import (
	"errors"
	"fmt"
	"log"
	"time"

	"github.com/influxdata/influxdb/client/v2"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

// Exported constants for services writing to EventStorage indirectly (e.g. Kafka) and reading from enumerated values.
const (
	CategoryPageview   = "pageview"
	ActionPageviewLoad = "load"
	TablePageviews     = "pageviews"
	FlagArticle        = "_article"
)

// PageviewOptions represent filter options for pageview-related calls.
type PageviewOptions struct {
	Action     string
	IDs        []string
	FilterBy   string
	GroupBy    []string
	TimeAfter  time.Time
	TimeBefore time.Time
}

// PageviewCountOptions represent filter options for pageview count call.
type PageviewCountOptions struct {
	Action     string
	IDs        []string
	FilterBy   []*FilterBy
	GroupBy    []string
	TimeAfter  time.Time
	TimeBefore time.Time
}

// FilterBy represents tag and values used to filter results of pageview count call.
type FilterBy struct {
	Tag    string
	Values []string
}

// Pageview represents pageview data.
type Pageview struct {
	ArticleID    string
	AuthorID     string
	UTMSource    string
	UTMCampaign  string
	UTMMedium    string
	UTMContent   string
	SocialSource string

	Token     string
	Time      time.Time
	Host      string
	IP        string
	UserID    string
	URL       string
	UserAgent string
}

// PageviewCollection is collection of pageviews.
type PageviewCollection []*Pageview

// PageviewStorage is an interface to get pageview events related data.
type PageviewStorage interface {
	// Count returns number of pageviews matching the filter defined by PageviewCountOptions.
	Count(o PageviewCountOptions) (CountRowCollection, bool, error)
	// List returns list of all pageviews based on given PageviewOptions.
	List(o PageviewOptions) (PageviewCollection, error)
	// Categories lists all tracked categories.
	Categories() []string
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all tracked actions under the given category.
	Actions(category string) ([]string, error)
}

// PageviewDB is Influx implementation of PageviewStorage.
type PageviewDB struct {
	DB *InfluxDB
}

// Count returns number of pageviews matching the filter defined by PageviewOptions.
func (eDB *PageviewDB) Count(o PageviewCountOptions) (CountRowCollection, bool, error) {
	builder := eDB.DB.QueryBuilder.Select("count(token)").From(`"` + TablePageviews + `"`)
	builder = eDB.addCountQueryFilters(builder, o)

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

// List returns list of all pageviews based on given PageviewOptions.
func (eDB *PageviewDB) List(o PageviewOptions) (PageviewCollection, error) {
	builder := eDB.DB.QueryBuilder.Select("*").From(`"` + TablePageviews + `"`)
	builder = eDB.addQueryFilters(builder, o)

	q := client.Query{
		Command:  builder.Build(),
		Database: eDB.DB.DBName,
	}

	response, err := eDB.DB.Client.Query(q)
	if err != nil {
		return nil, err
	}
	if response.Error() != nil {
		return nil, response.Error()
	}

	ec := PageviewCollection{}

	// no data returned
	if len(response.Results[0].Series) == 0 {
		return ec, nil
	}

	for _, s := range response.Results[0].Series {
		for idx := range s.Values {
			ir := influxquery.NewInfluxResult(s, idx)
			e, err := pageviewFromInfluxResult(ir)
			if err != nil {
				return nil, err
			}
			ec = append(ec, e)
		}
	}

	return ec, nil
}

// Categories lists all tracked categories.
func (eDB *PageviewDB) Categories() []string {
	return []string{
		CategoryPageview,
	}
}

// Flags lists all available flags.
func (eDB *PageviewDB) Flags() []string {
	return []string{
		FlagArticle,
	}
}

// Actions lists all tracked actions under the given category.
func (eDB *PageviewDB) Actions(category string) ([]string, error) {
	switch category {
	case CategoryPageview:
		return []string{
			ActionPageviewLoad,
		}, nil
	}
	return nil, fmt.Errorf("unknown pageview category: %s", category)
}

// Users returns list of all tracked user IDs.
func (eDB *PageviewDB) Users() ([]string, error) {
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

func (eDB *PageviewDB) addQueryFilters(builder influxquery.Builder, o PageviewOptions) influxquery.Builder {
	if o.FilterBy != "" {
		cond := ""
		for i, val := range o.IDs {
			if i > 0 {
				cond += " OR "
			}
			//o.FilterBy should be influx table column
			cond = fmt.Sprintf("%s%s = '%s'", cond, o.FilterBy, val)
		}
		if cond != "" {
			builder = builder.Where(fmt.Sprintf("(%s)", cond))
		}
	}

	builder = addQueryFilterGroupBy(builder, o.GroupBy)
	builder = addQueryFilterAction(builder, o.Action)
	builder = addQueryFilterTimeAfter(builder, o.TimeAfter)
	builder = addQueryFilterTimeBefore(builder, o.TimeBefore)

	return builder
}

func (eDB *PageviewDB) addCountQueryFilters(builder influxquery.Builder, o PageviewCountOptions) influxquery.Builder {
	if len(o.FilterBy) > 0 {
		cond := ""
		for _, val := range o.FilterBy {

			if cond != "" {
				cond += ") AND ("
			}

			// TODO: replace OR with IN when it's implemented into influxDB
			// https://github.com/influxdata/influxdb/issues/2157
			for i, v := range val.Values {
				if i > 0 {
					cond += " OR "
				}
				cond = fmt.Sprintf("%s%s = '%s'", cond, val.Tag, v)
			}
		}

		if cond != "" {
			builder = builder.Where(fmt.Sprintf("(%s)", cond))
		}
	}

	builder = addQueryFilterGroupBy(builder, o.GroupBy)
	builder = addQueryFilterAction(builder, o.Action)
	builder = addQueryFilterTimeAfter(builder, o.TimeAfter)
	builder = addQueryFilterTimeBefore(builder, o.TimeBefore)

	return builder
}

func addQueryFilterGroupBy(builder influxquery.Builder, groupBy []string) influxquery.Builder {
	condGroupBy := ""
	for _, val := range groupBy {
		if val == "" {
			continue
		}
		if condGroupBy != "" {
			condGroupBy = fmt.Sprintf(`%s, "%s"`, condGroupBy, val)
		} else {
			condGroupBy = fmt.Sprintf(`"%s"`, val)
		}
	}
	if condGroupBy != "" {
		builder = builder.GroupBy(condGroupBy)
	}

	return builder
}

func addQueryFilterAction(builder influxquery.Builder, action string) influxquery.Builder {
	if action != "" {
		builder = builder.Where(fmt.Sprintf("action = '%s'", action))
	}
	return builder
}

func addQueryFilterTimeAfter(builder influxquery.Builder, timeAfter time.Time) influxquery.Builder {
	if !timeAfter.IsZero() {
		builder = builder.Where(fmt.Sprintf("time >= %d", timeAfter.UnixNano()))
	}
	return builder
}

func addQueryFilterTimeBefore(builder influxquery.Builder, timeBefore time.Time) influxquery.Builder {
	if !timeBefore.IsZero() {
		builder = builder.Where(fmt.Sprintf("time < %d", timeBefore.UnixNano()))
	}
	return builder
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

	utmSource, ok := ir.StringValue("utm_source")
	if ok {
		pageview.UTMSource = utmSource
	}
	utmMedium, ok := ir.StringValue("utm_medium")
	if ok {
		pageview.UTMMedium = utmMedium
	}
	utmCampaign, ok := ir.StringValue("utm_campaign")
	if ok {
		pageview.UTMCampaign = utmCampaign
	}
	utmContent, ok := ir.StringValue("utm_content")
	if ok {
		pageview.UTMContent = utmContent
	}
	social, ok := ir.StringValue("social")
	if ok {
		pageview.SocialSource = social
	}
	host, ok := ir.StringValue("host")
	if ok {
		pageview.Host = host
	}
	ip, ok := ir.StringValue("ip")
	if ok {
		pageview.IP = ip
	}
	userID, ok := ir.StringValue("user_id")
	if ok {
		pageview.UserID = userID
	}
	url, ok := ir.StringValue("url")
	if ok {
		pageview.URL = url
	}
	userAgent, ok := ir.StringValue("user_agent")
	if ok {
		pageview.UserAgent = userAgent
	}

	return pageview, nil
}
