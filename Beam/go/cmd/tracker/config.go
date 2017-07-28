package main

type Config struct {
	TrackerAddr string `envconfig:"addr";required:"true"`
	BrokerAddr  string `envconfig:"broker_addr";required:"true"`

	MysqlNet    string `envconfig:"mysql_net";required:"true"`
	MysqlAddr   string `envconfig:"mysql_addr";required:"true"`
	MysqlUser   string `envconfig:"mysql_user";required:"true"`
	MysqlPasswd string `envconfig:"mysql_passwd";required:"true"`
	MysqlDBName string `envconfig:"mysql_dbname";required:"true"`
}
