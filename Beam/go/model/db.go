package model

import (
	influxClient "github.com/influxdata/influxdb/client/v2"
	"gitlab.com/remp/remp/Beam/go/influxquery"
)

type InfluxDB struct {
	DBName       string
	Client       influxClient.Client
	QueryBuilder influxquery.Builder
}
