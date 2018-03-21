package model

import (
	"errors"
	"fmt"
	"log"
	"strings"
	"time"

	"github.com/influxdata/influxdb/client/v2"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

// Exported constants for services writing to EventStorage indirectly (e.g. Kafka) and reading from enumerated values.
const (
	CategoryPageview         = "pageview"
	ActionPageviewLoad       = "load"
	ActionPageviewTimespent  = "timespent"
	TablePageviews           = "pageviews"
	TableTimespent           = "pageviews_time_spent"
	TableTimespentAggregated = "pageviews_time_spent_hourly"
	TableTimespentRP         = "timespent_rp"
	FlagArticle              = "_article"
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

// Pageview represents pageview data.
type Pageview struct {
	ArticleID     string
	ArticleLocked bool
	TitleVariant  string
	AuthorID      string
	UTMSource     string
	UTMCampaign   string
	UTMMedium     string
	UTMContent    string
	SocialSource  string

	Token      string
	Time       time.Time
	Host       string
	IP         string
	UserID     string
	URL        string
	UserAgent  string
	BrowserID  string
	SessionID  string
	PageviewID string
	Referer    string
	Subscriber bool
}

// PageviewRow represents one row of grouped list.
type PageviewRow struct {
	Tags      map[string]string
	Pageviews []*Pageview
}

// PageviewRowCollection represents collection of rows of grouped list.
type PageviewRowCollection []PageviewRow

// PageviewStorage is an interface to get pageview events related data.
type PageviewStorage interface {
	// Count returns count of pageviews based on the provided filter options.
	Count(o AggregateOptions) (CountRowCollection, bool, error)
	// Sum returns sum of pageviews based on the provided filter options.
	Sum(o AggregateOptions) (SumRowCollection, bool, error)
	// List returns list of all pageviews based on given PageviewOptions.
	List(o ListOptions) (PageviewRowCollection, error)
	// Categories lists all tracked categories.
	Categories() []string
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all tracked actions under the given category.
	Actions(category string) ([]string, error)
}

// queryBinding represents information about where and how the data should be fetched.
type queryBinding struct {
	Measurement string
	Field       string
}

// PageviewDB is Influx implementation of PageviewStorage.
type PageviewDB struct {
	DB *InfluxDB
}

// Count returns count of pageviews based on the provided filter options.
func (eDB *PageviewDB) Count(o AggregateOptions) (CountRowCollection, bool, error) {
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
func (eDB *PageviewDB) Sum(o AggregateOptions) (SumRowCollection, bool, error) {
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

// List returns list of all pageviews based on given PageviewOptions.
func (eDB *PageviewDB) List(o ListOptions) (PageviewRowCollection, error) {
	var selectFields string
	if len(o.SelectFields) > 0 {
		o.SelectFields = append(o.SelectFields, "token") // at least one value needs to be always selected
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

	log.Println(q.Command)

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
		row := PageviewRow{
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

// resolveQueryBindings returns name of the table and field used within the aggregate function
// based on the provided action.
func (eDB *PageviewDB) resolveQueryBindings(action string) (queryBinding, error) {
	switch action {
	case ActionPageviewLoad:
		return queryBinding{
			Measurement: TablePageviews,
			Field:       "token",
		}, nil
	case ActionPageviewTimespent:
		return queryBinding{
			Measurement: TableTimespentAggregated,
			Field:       "sum",
		}, nil
	}
	return queryBinding{}, fmt.Errorf("unable to resolve query bindings: action [%s] unknown", action)
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
