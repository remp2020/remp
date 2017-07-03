package model

import (
	"encoding/json"
	"fmt"
	"log"

	"github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

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

func (iDB *InfluxDB) ResponseCount(response *client.Response) (int, error) {
	if len(response.Results[0].Series) == 0 {
		return 0, nil
	}
	jsonCount, ok := response.Results[0].Series[0].Values[0][1].(json.Number)
	if !ok {
		return 0, errors.New("influx result is not json.Number, cannot proceed")
	}
	count, err := jsonCount.Int64()
	if err != nil {
		return 0, errors.Wrap(err, fmt.Sprintf("unable to parse influx count [%s]", count))
	}
	return int(count), nil
}
