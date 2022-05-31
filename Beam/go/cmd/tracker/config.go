package main

// Config represents config structure for tracker cmd.
type Config struct {
	TrackerAddr string `envconfig:"addr" required:"true"`
	BrokerAddrs string `envconfig:"broker_addrs" required:"true"`
	Debug       bool   `envconfig:"debug" required:"false"`

	MysqlNet    string `envconfig:"mysql_net" required:"true"`
	MysqlAddr   string `envconfig:"mysql_addr" required:"true"`
	MysqlUser   string `envconfig:"mysql_user" required:"true"`
	MysqlPasswd string `envconfig:"mysql_passwd" required:"true"`
	MysqlDBName string `envconfig:"mysql_dbname" required:"true"`

	KafkaSaslUser   string `envconfig:"kafka_sasl_user" required:"false"`
	KafkaSaslPasswd string `envconfig:"kafka_sasl_passwd" required:"false"`

	InternalHosts string `envconfig:"internal_hosts" required:"false"`

	TimespentLimit int `envconfig:"timespent_limit" required:"false" default:"0"`

	BrokerImpl string `envconfig:"broker_impl" required:"false" default:"kafka"`

	PubSubProjectID string `envconfig:"pubsub_project_id" required:"false"`
	PubSubTopicID   string `envconfig:"pubsub_topic_id" required:"false"`
}
