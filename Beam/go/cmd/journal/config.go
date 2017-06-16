package main

type Config struct {
	JournalAddr    string `envconfig:"journal_addr";required:"true"`
	InfluxHost     string `envconfig:"influx_host";required:"true"`
	InfluxUsername string `envconfig:"influx_username";required:"true"`
	InfluxPassword string `envconfig:"influx_password";required:"true"`
	InfluxDB       string `envconfig:"influx_db";required:"true"`
}
