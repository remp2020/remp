package model

import (
	"fmt"
	"log"
	"time"

	"github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

const CategoryCommerce = "commerce"
const TableCommerce = "commerce"

// Options represent filter options for commerce-related calls.
type CommerceOptions struct {
	IDs        []string
	FilterBy   FilterType
	Group      bool
	Step       string
	TimeAfter  time.Time
	TimeBefore time.Time
}

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

type CommerceCollection []*Commerce

type CommerceStorage interface {
	// Count returns count of events based on the provided filter options.
	Count(o CommerceOptions) (map[string]int, error)
	// Sum returns sum of events based on the provided filter options.
	Sum(o CommerceOptions) (map[string]float64, error)
	// List returns list of all events based on given CommerceOptions.
	List(o CommerceOptions) (CommerceCollection, error)
	// Categories lists all available categories.
	Categories() ([]string, error)
	// Actions lists all available actions under the given category.
	Actions(category string) ([]string, error)
}

type CommerceDB struct {
	DB *InfluxDB
}

func (cDB *CommerceDB) Count(o CommerceOptions) (map[string]int, error) {
	builder := cDB.DB.QueryBuilder.Select(`count("revenue")`).From(`"commerce"`)
	builder = cDB.addQueryFilters(builder, o)

	log.Println("query:", builder.Build())

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

	// no data returned
	if len(response.Results[0].Series) == 0 {
		return nil, nil
	}

	// process response
	return cDB.DB.GroupedCount(response, o.FilterBy)
}

// List returns list of all events based on given CommerceOptions.
func (cDB *CommerceDB) List(o CommerceOptions) (CommerceCollection, error) {
	builder := cDB.DB.QueryBuilder.Select("*").From("commerce")
	builder = cDB.addQueryFilters(builder, o)

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

func (cDB *CommerceDB) Sum(o CommerceOptions) (map[string]float64, error) {
	builder := cDB.DB.QueryBuilder.Select(`sum("revenue")`).From(`"commerce"`)
	builder = cDB.addQueryFilters(builder, o)

	log.Println("query:", builder.Build())

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

	// no data returned
	if len(response.Results[0].Series) == 0 {
		return nil, nil
	}

	// process response
	return cDB.DB.GroupedSum(response, o.FilterBy)
}

func (eDB *CommerceDB) Categories() ([]string, error) {
	return []string{
		CategoryCommerce,
	}, nil
}

func (eDB *CommerceDB) Actions(category string) ([]string, error) {
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

func (cDB *CommerceDB) addQueryFilters(builder influxquery.Builder, o CommerceOptions) influxquery.Builder {
	if o.FilterBy != "" {
		cond := ""
		for i, val := range o.IDs {
			if i > 0 {
				cond += " OR "
			}
			cond = fmt.Sprintf("%s%s = '%s'", cond, o.FilterBy.column(), val)
		}
		if cond != "" {
			builder = builder.Where(fmt.Sprintf("(%s)", cond))
		}
		if o.Group {
			builder = builder.GroupBy(fmt.Sprintf(`"%s"`, o.FilterBy.column()))
		}
	}

	if o.Step != "" {
		builder = builder.Where(fmt.Sprintf("step = '%s'", o.Step))
	}
	if !o.TimeAfter.IsZero() {
		builder = builder.Where(fmt.Sprintf("time >= %d", o.TimeAfter.UnixNano()))
	}
	if !o.TimeBefore.IsZero() {
		builder = builder.Where(fmt.Sprintf("time < %d", o.TimeBefore.UnixNano()))
	}

	return builder
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
