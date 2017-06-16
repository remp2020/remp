package influx

import (
	"fmt"

	"encoding/json"

	"github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/journal/influxquery"
	"gitlab.com/remp/remp/Beam/go/cmd/journal/service/datalake"
)

// NewService returns implementation of Service interface.
func NewService(client client.Client, builder influxquery.Builder, login string, password string) Service {
	return &idbService{
		login:    login,
		password: password,
		client:   client,
		builder:  builder,
	}
}

// Service represents lowlevel communication layer with the Influx datalake.
type Service interface {
	// EventCount returns count of events based on filters in options.
	EventCount(dbName string, options datalake.Options) (*datalake.EventCountResult, error)
}

// idbService represents client/v2 implementation of Service interface.
type idbService struct {
	login    string
	password string
	client   client.Client
	builder  influxquery.Builder
}

// EventCount returns count of events based on filters in options.
func (is *idbService) EventCount(dbName string, options datalake.Options) (*datalake.EventCountResult, error) {
	builder := is.builder.Select("count(value)").From("events")
	if options.UserID != "" {
		builder.Where(fmt.Sprintf("user_id = '%s'", options.UserID))
	}
	if options.Category != "" {
		builder.Where(fmt.Sprintf("category = '%s'", options.Category))
	}
	if options.Action != "" {
		builder.Where(fmt.Sprintf("action = '%s'", options.Action))
	}
	if !options.TimeAfter.IsZero() {
		builder.Where(fmt.Sprintf("time <= %d", options.TimeAfter.UnixNano()))
	}
	if !options.TimeBefore.IsZero() {
		builder.Where(fmt.Sprintf("time <= %d", options.TimeBefore.UnixNano()))
	}
	q := client.Query{
		Command:  builder.Build(),
		Database: dbName,
	}

	response, err := is.client.Query(q)
	if err != nil {
		return nil, err
	}
	if response.Error() != nil {
		return nil, response.Error()
	}

	// no data returned
	if len(response.Results[0].Series) == 0 {
		return &datalake.EventCountResult{
			Count: 0,
		}, nil
	}

	// process response
	jsonCount, ok := response.Results[0].Series[0].Values[0][1].(json.Number)
	if !ok {
		return nil, errors.New("influx result is not string, cannot proceed")
	}
	count, err := jsonCount.Int64()
	if err != nil {
		return nil, errors.Wrap(err, fmt.Sprintf("unable to parse influx count [%s]", count))
	}
	return &datalake.EventCountResult{
		Count: int(count),
	}, nil
}
