package influx

import (
	"gitlab.com/remp/remp/Beam/go/cmd/journal/service/datalake"
)

// Options represent Influx app configuration options.
type Options struct {
	InfluxDB string
}

// InfluxDB is layer to communicate with Influx datalake.
type InfluxDB struct {
	service Service
	dbName  string
}

// New returns instance of Datalake implemented in InfluxDB.
func New(options Options, service Service) datalake.DataLake {
	return &InfluxDB{
		dbName:  options.InfluxDB,
		service: service,
	}
}

// EventCount returns count of events based on filters in options.
func (i *InfluxDB) EventCount(o datalake.Options) (*datalake.EventCountResult, error) {
	return i.service.EventCount(i.dbName, o)
}
