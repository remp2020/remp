package main

// Config represents config structure for segments cmd.
type Config struct {
	SegmentsAddr string `envconfig:"addr" required:"true"`

	MysqlNet    string `envconfig:"mysql_net" required:"true"`
	MysqlAddr   string `envconfig:"mysql_addr" required:"true"`
	MysqlUser   string `envconfig:"mysql_user" required:"true"`
	MysqlPasswd string `envconfig:"mysql_passwd" required:"true"`
	MysqlDBName string `envconfig:"mysql_dbname" required:"true"`

	InfluxAddr   string `envconfig:"influx_addr" required:"true"`
	InfluxUser   string `envconfig:"influx_user" required:"true"`
	InfluxPasswd string `envconfig:"influx_passwd" required:"true"`
	InfluxDBName string `envconfig:"influx_dbname" required:"true"`
}
