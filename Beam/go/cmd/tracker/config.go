package main

// Config represents config structure for tracker cmd.
type Config struct {
	TrackerAddr string `envconfig:"addr" required:"true"`
	BrokerAddr  string `envconfig:"broker_addr" required:"true"`
	Debug       bool   `envconfig:"debug" required:"false"`

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
