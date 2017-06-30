package model

import (
	"fmt"
	"time"

	"github.com/influxdata/influxdb/client/v2"
)

// Options represent filter options for event-related calls.
type EventOptions struct {
	UserID     string
	Action     string
	Category   string
	TimeAfter  time.Time
	TimeBefore time.Time
}

type EventStorage interface {
	Count(o EventOptions) (int, error)
}

type EventDB struct {
	DB *InfluxDB
}

func (eDB *EventDB) Count(o EventOptions) (int, error) {
	builder := eDB.DB.QueryBuilder.Select("count(value)").From("events")

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
	return eDB.DB.ResponseCount(response)
}
