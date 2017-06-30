//go:generate goagen -d gitlab.com/remp/remp/Beam/go/cmd/segments/design

package main

import (
	"context"
	"log"
	"net/http"
	"time"

	"github.com/go-sql-driver/mysql"
	"github.com/goadesign/goa"
	"github.com/goadesign/goa/middleware"
	"github.com/influxdata/influxdb/client/v2"
	"github.com/jmoiron/sqlx"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/controller"
	"gitlab.com/remp/remp/Beam/go/influxquery"
	"gitlab.com/remp/remp/Beam/go/model"
)

func main() {
	err := godotenv.Load()
	if err != nil {
		log.Fatalln(errors.Wrap(err, "unable to load .env file"))
	}
	var c Config
	if err := envconfig.Process("segments", &c); err != nil {
		log.Fatalln(errors.Wrap(err, "unable to process envconfig"))
	}

	service := goa.New("segments")

	service.Use(middleware.RequestID())
	service.Use(middleware.LogRequest(true))
	service.Use(middleware.ErrorHandler(service, true))
	service.Use(middleware.Recover())

	app.MountSwaggerController(service, service.NewController("swagger"))

	// DB init

	mysqlDBConfig := mysql.Config{
		Net:       c.MysqlNet,
		Addr:      c.MysqlAddr,
		User:      c.MysqlUser,
		Passwd:    c.MysqlPasswd,
		DBName:    c.MysqlDBName,
		ParseTime: true,
	}
	mysqlDB, err := sqlx.Connect("mysql", mysqlDBConfig.FormatDSN())
	if err != nil {
		log.Fatalln(errors.Wrap(err, "unable to connect to MySQL"))
	}

	ic, err := client.NewHTTPClient(client.HTTPConfig{
		Addr:     c.InfluxAddr,
		Username: c.InfluxUser,
		Password: c.InfluxPasswd,
	})
	if err != nil {
		log.Fatalln(errors.Wrap(err, "unable to initialize influx http client"))
	}
	influxDB := &model.InfluxDB{
		DBName:       c.InfluxDBName,
		Client:       ic,
		QueryBuilder: influxquery.NewInfluxBuilder(),
		Debug:        true,
	}

	eventDB := &model.EventDB{
		DB: influxDB,
	}
	segmentDB := &model.SegmentDB{
		MySQL:    mysqlDB,
		InfluxDB: influxDB,
	}

	// controllers init

	app.MountEventsController(service, controller.NewEventController(service, eventDB))
	app.MountSegmentsController(service, controller.NewSegmentController(service, segmentDB))

	// server init

	log.Println("starting server:", c.SegmentsAddr)
	srv := &http.Server{
		Addr:    c.SegmentsAddr,
		Handler: service.Mux,
	}

	if err := srv.ListenAndServe(); err != nil {
		service.LogError("startup", "err", err)
	}

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()
	srv.Shutdown(ctx)

}
