package main

type Config struct {
	TrackerAddr string `envconfig:"tracker_addr";required:"true"`
	BrokerAddr  string `envconfig:"broker_addr";required:"true"`
}
