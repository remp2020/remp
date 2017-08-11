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

type FilterType string

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

type InfluxDB struct {
	DBName       string
	Client       client.Client
	QueryBuilder influxquery.Builder
	Debug        bool
}

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

func (iDB *InfluxDB) Count(response *client.Response) (int, error) {
	counts, err := iDB.GroupedCount(response, FilterType(""))
	if err != nil {
		return 0, err
	}
	return counts[""], nil
}

func (iDB *InfluxDB) GroupedCount(response *client.Response, ft FilterType) (map[string]int, error) {
	counts := make(map[string]int)
	if len(response.Results[0].Series) == 0 {
		counts[""] = 0
		return counts, nil
	}

	for _, s := range response.Results[0].Series {
		jsonCount, ok := s.Values[0][1].(json.Number)
		if !ok {
			return nil, errors.New("influx result is not json.Number, cannot proceed")
		}
		count, err := jsonCount.Int64()
		if err != nil {
			return nil, errors.Wrap(err, fmt.Sprintf("unable to parse influx count [%s]", count))
		}
		counts[s.Tags[ft.column()]] = int(count)
	}

	return counts, nil
}

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
			return nil, errors.Wrap(err, fmt.Sprintf("unable to parse influx count [%s]", sum))
		}
		sums[s.Tags[ft.column()]] = sum
	}

	return sums, nil
}
