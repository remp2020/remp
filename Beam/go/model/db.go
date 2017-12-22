package model

import (
	"encoding/json"
	"fmt"
	"log"

	"github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

var fts = map[string]string{
	"articles": "article_id",
	"authors":  "author_id",
	"users":    "user_id",
}

// FilterType represents special enum layer for data filtering.
type FilterType string

// NewFilterType validates support for provided type and returns FilterType instance.
func NewFilterType(t string) (FilterType, error) {
	_, ok := fts[t]
	if !ok {
		return "", fmt.Errorf("unsupported FilterType: %s", t)
	}
	return FilterType(t), nil
}

func (gt FilterType) column() string {
	return fts[string(gt)]
}

// CountRow represents one row of grouped count.
type CountRow struct {
	Tags  map[string]string
	Count int
}

// CountRowCollection represents collection of rows of grouped count.
type CountRowCollection []CountRow

// InfluxDB represents data layer based on InfluxDB.
type InfluxDB struct {
	DBName       string
	Client       client.Client
	QueryBuilder influxquery.Builder
	Debug        bool
}

// Exec executes the provided query string and returns response.
func (iDB *InfluxDB) Exec(cmd string) (*client.Response, error) {
	if iDB.Debug {
		log.Println("InfluxDB:", cmd)
	}
	q := client.Query{
		Command:  cmd,
		Database: iDB.DBName,
	}
	return iDB.Client.Query(q)
}

// Count parses the provided response and extracts count from the query result.
func (iDB *InfluxDB) Count(response *client.Response) (int, bool, error) {
	counts, ok, err := iDB.GroupedCount(response, FilterType(""))
	if err != nil {
		return 0, false, err
	}
	if !ok {
		return 0, false, nil
	}
	return counts[""], true, nil
}

// GroupedCount parses the provided response and extracts counts based on provided filter type.
func (iDB *InfluxDB) GroupedCount(response *client.Response, ft FilterType) (map[string]int, bool, error) {
	counts := make(map[string]int)
	if len(response.Results[0].Series) == 0 {
		return nil, false, nil
	}

	for _, s := range response.Results[0].Series {
		jsonCount, ok := s.Values[0][1].(json.Number)
		if !ok {
			return nil, false, errors.New("influx result is not json.Number, cannot proceed")
		}
		count, err := jsonCount.Int64()
		if err != nil {
			return nil, false, errors.Wrap(err, fmt.Sprintf("unable to parse influx count [%d]", count))
		}
		counts[s.Tags[ft.column()]] = int(count)
	}

	return counts, true, nil
}

// MultiGroupedCount parses the provided response and extracts counts based on provided filter column.
func (iDB *InfluxDB) MultiGroupedCount(response *client.Response) (CountRowCollection, bool, error) {
	var results CountRowCollection
	if len(response.Results[0].Series) == 0 {
		return nil, false, nil
	}

	for _, s := range response.Results[0].Series {
		var row CountRow
		row.Tags = s.Tags
		jsonCount, ok := s.Values[0][1].(json.Number)
		if !ok {
			return nil, false, errors.New("influx result is not json.Number, cannot proceed")
		}
		count, err := jsonCount.Int64()
		if err != nil {
			return nil, false, errors.Wrap(err, fmt.Sprintf("unable to parse influx count [%d]", count))
		}
		row.Count = int(count)
		results = append(results, row)
	}

	return results, true, nil
}

// GroupedSum parses the provided response and extracts sums based on provided filter type.
func (iDB *InfluxDB) GroupedSum(response *client.Response, ft FilterType) (map[string]float64, error) {
	sums := make(map[string]float64)
	if len(response.Results[0].Series) == 0 {
		sums[""] = 0
		return sums, nil
	}

	for _, s := range response.Results[0].Series {
		jsonCount, ok := s.Values[0][1].(json.Number)
		if !ok {
			return nil, errors.New("influx result is not json.Number, cannot proceed")
		}
		sum, err := jsonCount.Float64()
		if err != nil {
			return nil, errors.Wrap(err, fmt.Sprintf("unable to parse influx count [%f]", sum))
		}
		sums[s.Tags[ft.column()]] = sum
	}

	return sums, nil
}
