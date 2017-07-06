package main

type Config struct {
	TrackerAddr string `envconfig:"addr";required:"true"`
	BrokerAddr  string `envconfig:"broker_addr";required:"true"`
}
