package model

import (
	"fmt"
	"log"
	"time"

	"github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

// Exported constants for services writing to EventStorage indirectly (e.g. Kafka).
const (
	CategoryCommerce = "commerce"
	TableCommerce    = "commerce"
)

// CommerceOptions represent filter options for commerce-related calls.
type CommerceOptions struct {
	IDs        []string
	FilterBy   FilterType
	Group      bool
	Step       string
	TimeAfter  time.Time
	TimeBefore time.Time
}

// Commerce represents commerce event data.
type Commerce struct {
	Step      string
	Token     string
	Time      time.Time
	Host      string
	IP        string
	UserID    string
	URL       string
	UserAgent string
}

// CommerceCollection is collection of commerce events.
type CommerceCollection []*Commerce

// CommerceStorage is an interface to get commerce event related data.
type CommerceStorage interface {
	// Count returns count of events based on the provided filter options.
	Count(o AggregateOptions) (CountRowCollection, bool, error)
	// Sum returns sum of events based on the provided filter options.
	Sum(o AggregateOptions) (SumRowCollection, bool, error)
	// List returns list of all events based on given CommerceOptions.
	List(o AggregateOptions) (CommerceCollection, error)
	// Categories lists all available categories.
	Categories() []string
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all available actions under the given category.
	Actions(category string) ([]string, error)
}

// CommerceDB is Influx implementation of CommerceStorage.
type CommerceDB struct {
	DB *InfluxDB
}

// Count returns count of events based on the provided filter options.
func (cDB *CommerceDB) Count(o AggregateOptions) (CountRowCollection, bool, error) {
	builder := cDB.DB.QueryBuilder.Select(`count("revenue")`).From(`"` + TableCommerce + `"`)
	builder = addAggregateQueryFilters(builder, o)

	bb := builder.Build()
	log.Println("commerce count query:", bb)

	q := client.Query{
		Command:  bb,
		Database: cDB.DB.DBName,
	}

	response, err := cDB.DB.Client.Query(q)
	if err != nil {
		return nil, false, err
	}
	if response.Error() != nil {
		return nil, false, response.Error()
	}

	// process response
	return cDB.DB.MultiGroupedCount(response)
}

// List returns list of all events based on given CommerceOptions.
func (cDB *CommerceDB) List(o AggregateOptions) (CommerceCollection, error) {
	builder := cDB.DB.QueryBuilder.Select("*").From(`"` + TableCommerce + `"`)
	builder = addAggregateQueryFilters(builder, o)

	q := client.Query{
		Command:  builder.Build(),
		Database: cDB.DB.DBName,
	}

	response, err := cDB.DB.Client.Query(q)
	if err != nil {
		return nil, err
	}
	if response.Error() != nil {
		return nil, response.Error()
	}

	cc := CommerceCollection{}

	// no data returned
	if len(response.Results[0].Series) == 0 {
		return cc, nil
	}

	for _, s := range response.Results[0].Series {
		for idx := range s.Values {
			ir := influxquery.NewInfluxResult(s, idx)
			c, err := commerceFromInfluxResult(ir)
			if err != nil {
				return nil, err
			}
			cc = append(cc, c)
		}
	}

	return cc, nil
}

// Sum returns sum of events based on the provided filter options.
func (cDB *CommerceDB) Sum(o AggregateOptions) (SumRowCollection, bool, error) {
	builder := cDB.DB.QueryBuilder.Select(`sum("revenue")`).From(`"` + TableCommerce + `"`)
	builder = addAggregateQueryFilters(builder, o)

	bb := builder.Build()
	log.Println("commerce sum query:", bb)

	q := client.Query{
		Command:  bb,
		Database: cDB.DB.DBName,
	}

	response, err := cDB.DB.Client.Query(q)
	if err != nil {
		return nil, false, err
	}
	if response.Error() != nil {
		return nil, false, response.Error()
	}

	// process response
	return cDB.DB.GroupedSum(response)
}

// Categories lists all available categories.
func (cDB *CommerceDB) Categories() []string {
	return []string{
		CategoryCommerce,
	}
}

// Flags lists all available flags.
func (cDB *CommerceDB) Flags() []string {
	return []string{}
}

// Actions lists all available actions under the given category.
func (cDB *CommerceDB) Actions(category string) ([]string, error) {
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

func commerceFromInfluxResult(ir *influxquery.Result) (*Commerce, error) {
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
	commerce := &Commerce{
		Token: token,
		Time:  t,
	}

	host, ok := ir.StringValue("host")
	if ok {
		commerce.Host = host
	}
	ip, ok := ir.StringValue("ip")
	if ok {
		commerce.IP = ip
	}
	userID, ok := ir.StringValue("user_id")
	if ok {
		commerce.UserID = userID
	}
	url, ok := ir.StringValue("url")
	if ok {
		commerce.URL = url
	}
	userAgent, ok := ir.StringValue("user_agent")
	if ok {
		commerce.UserAgent = userAgent
	}

	return commerce, nil
}
