package main

type Config struct {
	Addr       string `envconfig:"addr";required:"true"`
	BrokerAddr string `envconfig:"broker_addr";required:"true"`
}
