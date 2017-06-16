//go:generate goagen bootstrap -d gitlab.com/remp/remp/Beam/go/cmd/tracker/design

package main

import (
	"context"
	"log"
	"net/http"
	"time"

	"github.com/goadesign/goa"
	"github.com/goadesign/goa/middleware"
	"github.com/influxdata/influxdb/client/v2"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/journal/app"
	"gitlab.com/remp/remp/Beam/go/cmd/journal/controller"
	"gitlab.com/remp/remp/Beam/go/cmd/journal/influxquery"
	"gitlab.com/remp/remp/Beam/go/cmd/journal/service/datalake/influx"
)

func main() {
	err := godotenv.Load()
	if err != nil {
		log.Fatalln(errors.Wrap(err, "unable to load .env file"))
	}
	var c Config
	if err := envconfig.Process("journal", &c); err != nil {
		log.Fatalln(errors.Wrap(err, "unable to process envconfig"))
	}

	service := goa.New("journal")

	service.Use(middleware.RequestID())
	service.Use(middleware.LogRequest(true))
	service.Use(middleware.ErrorHandler(service, true))
	service.Use(middleware.Recover())

	app.MountSwaggerController(service, service.NewController("swagger"))
	ic, err := client.NewHTTPClient(client.HTTPConfig{
		Addr:     c.InfluxHost,
		Username: c.InfluxUsername,
		Password: c.InfluxPassword,
	})
	if err != nil {
		log.Fatalln(errors.Wrap(err, "unable to initialize influx http client"))
	}
	iqb := influxquery.New()

	influxService := influx.NewService(ic, iqb, c.InfluxUsername, c.InfluxPassword)
	influxDB := influx.New(
		influx.Options{
			InfluxDB: c.InfluxDB,
		},
		influxService,
	)

	app.MountEventsController(service, controller.NewEventController(service, influxDB))

	log.Println("starting server:", c.JournalAddr)
	srv := &http.Server{
		Addr:    c.JournalAddr,
		Handler: service.Mux,
	}

	if err := srv.ListenAndServe(); err != nil {
		service.LogError("startup", "err", err)
	}

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()
	srv.Shutdown(ctx)

}
