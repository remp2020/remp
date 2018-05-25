package main

// Config represents config structure for segments cmd.
type Config struct {
	SegmentsAddr string `envconfig:"addr" required:"true"`
	Debug        bool   `envconfig:"debug" required:"false"`

	MysqlNet    string `envconfig:"mysql_net" required:"true"`
	MysqlAddr   string `envconfig:"mysql_addr" required:"true"`
	MysqlUser   string `envconfig:"mysql_user" required:"true"`
	MysqlPasswd string `envconfig:"mysql_passwd" required:"true"`
	MysqlDBName string `envconfig:"mysql_dbname" required:"true"`

	InfluxAddr   string `envconfig:"influx_addr" required:"false"`
	InfluxUser   string `envconfig:"influx_user" required:"false"`
	InfluxPasswd string `envconfig:"influx_passwd" required:"false"`
	InfluxDBName string `envconfig:"influx_dbname" required:"false"`

	ElasticAddr   string `envconfig:"elastic_addr" required:"true"`
	ElasticUser   string `envconfig:"elastic_user" required:"false"`
	ElasticPasswd string `envconfig:"elastic_passwd" required:"false"`
}
