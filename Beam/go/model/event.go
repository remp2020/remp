package model

import (
	"errors"
	"fmt"
	"time"

	"github.com/influxdata/influxdb/client/v2"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

// Options represent filter options for event-related calls.
type EventOptions struct {
	UserID     string
	Action     string
	Category   string
	TimeAfter  time.Time
	TimeBefore time.Time
}

type Event struct {
	Category  string
	Action    string
	Host      string
	IP        string
	Token     string
	UserID    string
	URL       string
	UserAgent string
	Time      time.Time
}

type EventCollection []*Event

type EventStorage interface {
	Count(o EventOptions) (int, error)

	List(o EventOptions) (EventCollection, error)
}

type EventDB struct {
	DB *InfluxDB
}

func (eDB *EventDB) Count(o EventOptions) (int, error) {
	builder := eDB.DB.QueryBuilder.Select("count(value)").From("events")
	builder = eDB.addQueryFilters(builder, o)

	q := client.Query{
		Command:  builder.Build(),
		Database: eDB.DB.DBName,
	}

	response, err := eDB.DB.Client.Query(q)
	if err != nil {
		return 0, err
	}
	if response.Error() != nil {
		return 0, response.Error()
	}

	// no data returned
	if len(response.Results[0].Series) == 0 {
		return 0, nil
	}

	// process response
	return eDB.DB.Count(response)
}

func (eDB *EventDB) List(o EventOptions) (EventCollection, error) {
	builder := eDB.DB.QueryBuilder.Select("*").From("events")
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

	ec := EventCollection{}

	// no data returned
	if len(response.Results[0].Series) == 0 {
		return ec, nil
	}

	for _, s := range response.Results[0].Series {
		for idx := range s.Values {
			ir := influxquery.NewInfluxResult(s, idx)
			e, err := eventFromInfluxResult(ir)
			if err != nil {
				return nil, err
			}
			ec = append(ec, e)
		}
	}

	return ec, nil
}

func (eDB *EventDB) addQueryFilters(builder influxquery.Builder, o EventOptions) influxquery.Builder {
	if o.UserID != "" {
		builder.Where(fmt.Sprintf("user_id = '%s'", o.UserID))
	}
	if o.Category != "" {
		builder.Where(fmt.Sprintf("category = '%s'", o.Category))
	}
	if o.Action != "" {
		builder.Where(fmt.Sprintf("action = '%s'", o.Action))
	}
	if !o.TimeAfter.IsZero() {
		builder.Where(fmt.Sprintf("time <= %d", o.TimeAfter.UnixNano()))
	}
	if !o.TimeBefore.IsZero() {
		builder.Where(fmt.Sprintf("time <= %d", o.TimeBefore.UnixNano()))
	}
	return builder
}

func eventFromInfluxResult(ir *influxquery.Result) (*Event, error) {
	category, ok := ir.StringValue("category")
	if !ok {
		return nil, errors.New("unable to map Category to influx result column")
	}
	action, ok := ir.StringValue("action")
	if !ok {
		return nil, errors.New("unable to map Action to influx result column")
	}
	host, ok := ir.StringValue("host")
	if !ok {
		return nil, errors.New("unable to map Host to influx result column")
	}
	ip, ok := ir.StringValue("ip")
	if !ok {
		return nil, errors.New("unable to map IP to influx result column")
	}
	token, ok := ir.StringValue("token")
	if !ok {
		return nil, errors.New("unable to map Token to influx result column")
	}
	userID, ok := ir.StringValue("user_id")
	if !ok {
		return nil, errors.New("unable to map UserID to influx result column")
	}
	url, ok := ir.StringValue("url")
	if !ok {
		return nil, errors.New("unable to map URL to influx result column")
	}
	userAgent, ok := ir.StringValue("user_agent")
	if !ok {
		return nil, errors.New("unable to map UserAgent to influx result column")
	}
	t, ok, err := ir.TimeValue("time")
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, errors.New("unable to map Time to influx result column")
	}

	return &Event{
		Category:  category,
		Action:    action,
		Host:      host,
		IP:        ip,
		Token:     token,
		UserID:    userID,
		URL:       url,
		UserAgent: userAgent,
		Time:      t,
	}, nil
}
