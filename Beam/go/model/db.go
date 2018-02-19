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

// SumRow represents one row of grouped sum.
type SumRow struct {
	Tags map[string]string
	Sum  float64
}

// SumRowCollection represents collection of rows of grouped sum.
type SumRowCollection []SumRow

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
func (iDB *InfluxDB) GroupedSum(response *client.Response) (SumRowCollection, bool, error) {
	var sums SumRowCollection
	if len(response.Results[0].Series) == 0 {
		return nil, false, nil
	}

	for _, s := range response.Results[0].Series {
		var row SumRow
		row.Tags = s.Tags
		jsonCount, ok := s.Values[0][1].(json.Number)
		if !ok {
			return nil, false, errors.New("influx result is not json.Number, cannot proceed")
		}
		sum, err := jsonCount.Float64()
		if err != nil {
			return nil, false, errors.Wrap(err, fmt.Sprintf("unable to parse influx count [%f]", sum))
		}
		row.Sum = sum
		sums = append(sums, row)
	}

	return sums, true, nil
}

// RetentionPolicy sets retention policy.
//
// - name     - name under which will be retention policy stored.
// - duration - duration of retention policy. Must follow
//              format of duration literal.
func (iDB *InfluxDB) RetentionPolicy(name, duration string) (bool, error) {
	cmd := fmt.Sprintf(`CREATE RETENTION POLICY "%s" ON "%s" DURATION %s REPLICATION 1;`, name, iDB.DBName, duration)
	if iDB.Debug {
		log.Println("InfluxDB: Create retention policy: ", cmd)
	}
	q := client.Query{
		Command:  cmd,
		Database: iDB.DBName,
	}

	response, err := iDB.Client.Query(q)
	if err != nil {
		return false, err
	}
	if response.Error() != nil {
		if response.Error().Error() == "retention policy already exists" {
			if iDB.Debug {
				log.Printf("InfluxDB: Retention policy [%s] already exist", name)
			}
			return false, nil
		}
		return false, response.Error()
	}
	return true, nil
}

// AlterRetentionPolicy alters retention policy.
//
// - name     - name under which is retention policy stored.
// - duration - new duration of retention policy. Must follow
//              format of duration literal.
func (iDB *InfluxDB) AlterRetentionPolicy(name, duration string) error {
	cmd := fmt.Sprintf(`ALTER RETENTION POLICY "%s" ON "%s" DURATION %s;`, name, iDB.DBName, duration)
	if iDB.Debug {
		log.Printf("InfluxDB: Alter retention policy [%s] with new duration [%s]: %s", name, duration, cmd)
	}
	q := client.Query{
		Command:  cmd,
		Database: iDB.DBName,
	}

	response, err := iDB.Client.Query(q)
	if err != nil {
		return err
	}
	if response.Error() != nil {
		return response.Error()
	}
	return nil
}

// ContinuousQuery sets continuous query.
//
// - name     - name under which will be continuous query stored.
// - resample - new resample interval of continuous query. Must follow
// 				format of duration literal.
// - query    - query of continuous query.
func (iDB *InfluxDB) ContinuousQuery(name, resample, query string) (bool, error) {
	cmd := fmt.Sprintf(`CREATE CONTINUOUS QUERY "%s" ON "%s" RESAMPLE EVERY %s BEGIN %s END`, name, iDB.DBName, resample, query)
	if iDB.Debug {
		log.Println("InfluxDB: Create continuous query: ", cmd)
	}
	q := client.Query{
		Command:  cmd,
		Database: iDB.DBName,
	}
	response, err := iDB.Client.Query(q)
	if err != nil {
		return false, err
	}
	if response.Error() != nil {
		if response.Error().Error() == "continuous query already exists" {
			if iDB.Debug {
				log.Printf("InfluxDB: Continuous query [%s] already exist", name)
			}
			return false, nil
		}
		return false, response.Error()
	}
	return true, nil
}
