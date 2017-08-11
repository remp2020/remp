package model

import (
	"fmt"
	"log"
	"time"

	"github.com/influxdata/influxdb/client/v2"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

// Options represent filter options for commerce-related calls.
type CommerceOptions struct {
	IDs        []string
	FilterBy   FilterType
	Group      bool
	Step       string
	TimeAfter  time.Time
	TimeBefore time.Time
}

type CommerceStorage interface {
	// Count returns count of events based on the provided filter options.
	Count(o CommerceOptions) (map[string]int, error)
	// Sum returns sum of events based on the provided filter options.
	Sum(o CommerceOptions) (map[string]float64, error)
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
		builder = builder.Where(fmt.Sprintf("time <= %d", o.TimeAfter.UnixNano()))
	}
	if !o.TimeBefore.IsZero() {
		builder = builder.Where(fmt.Sprintf("time <= %d", o.TimeBefore.UnixNano()))
	}

	return builder
}
