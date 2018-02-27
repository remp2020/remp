//go:generate goagen bootstrap -d gitlab.com/remp/remp/Beam/go/cmd/tracker/design

package main

import (
	"context"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"sync"
	"syscall"
	"time"

	"github.com/Shopify/sarama"
	"github.com/go-sql-driver/mysql"
	"github.com/goadesign/goa"
	"github.com/goadesign/goa/middleware"
	client "github.com/influxdata/influxdb/client/v2"
	"github.com/jmoiron/sqlx"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/app"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/controller"
	"gitlab.com/remp/remp/Beam/go/influxquery"
	"gitlab.com/remp/remp/Beam/go/model"
)

func main() {
	err := godotenv.Load()
	if err != nil {
		log.Fatalln(errors.Wrap(err, "unable to load .env file"))
	}
	var c Config
	if err := envconfig.Process("tracker", &c); err != nil {
		log.Fatalln(errors.Wrap(err, "unable to process envconfig"))
	}

	stop := make(chan os.Signal, 3)
	signal.Notify(stop, syscall.SIGINT, syscall.SIGTERM, syscall.SIGQUIT)

	service := goa.New("tracker")

	service.Use(middleware.RequestID())
	if c.Debug {
		service.Use(middleware.LogRequest(true))
		service.Use(middleware.LogResponse())
	}
	service.Use(middleware.ErrorHandler(service, true))
	service.Use(middleware.Recover())

	// kafka init

	service.LogInfo("connecting to broker", "bind", c.BrokerAddr)
	eventProducer, err := newProducer([]string{c.BrokerAddr})
	if err != nil {
		log.Fatalln(err)
	}
	defer eventProducer.Close()

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

	propertyDB := &model.PropertyDB{
		MySQL: mysqlDB,
	}

	err = influxDBpreparation(c)
	if err != nil {
		log.Fatalln(errors.Wrap(err, "unable to prepare InfluxDB"))
	}

	// server cancellation

	var wg sync.WaitGroup
	ctx, cancelCtx := context.WithCancel(context.Background())

	ticker := time.NewTicker(10 * time.Second)
	defer ticker.Stop()

	cacheProperties := func() {
		if err := propertyDB.Cache(); err != nil {
			service.LogError("unable to cache properties", "err", err)
		}
	}

	wg.Add(1)
	cacheProperties()
	go func() {
		defer wg.Done()
		service.LogInfo("starting property caching")
		for {
			select {
			case <-ticker.C:
				cacheProperties()
			case <-ctx.Done():
				service.LogInfo("property caching stopped")
				return
			}
		}
	}()

	// controllers init

	app.MountSwaggerController(service, service.NewController("swagger"))
	app.MountTrackController(service, controller.NewTrackController(
		service,
		eventProducer,
		propertyDB,
	))

	// server init

	service.LogInfo("starting server", "bind", c.TrackerAddr)
	srv := &http.Server{
		Addr:    c.TrackerAddr,
		Handler: service.Mux,
	}

	wg.Add(1)
	go func() {
		if err := srv.ListenAndServe(); err != nil {
			if err != http.ErrServerClosed {
				service.LogError("startup", "err", err)
				stop <- syscall.SIGQUIT
			}
			wg.Done()
		}
	}()

	s := <-stop
	service.LogInfo("shutting down", "signal", s)
	srv.Shutdown(ctx)
	cancelCtx()
	wg.Wait()
	service.LogInfo("bye bye")
}

func newProducer(brokerList []string) (sarama.AsyncProducer, error) {

	config := sarama.NewConfig()
	config.ClientID = "beam-tracker"
	config.Producer.RequiredAcks = sarama.WaitForLocal       // Only wait for the leader to ack
	config.Producer.Compression = sarama.CompressionSnappy   // Compress messages
	config.Producer.Flush.Frequency = 500 * time.Millisecond // Flush batches every 500ms

	producer, err := sarama.NewAsyncProducer(brokerList, config)
	if err != nil {
		return nil, err
	}

	// We will just log to STDOUT if we're not able to produce messages.
	// Note: messages will only be returned here after all retry attempts are exhausted.
	go func() {
		for err := range producer.Errors() {
			log.Println("Failed to write kafka producer entry:", err)
		}
	}()

	return producer, nil
}

// influxDBpreparation prepares InfluxDB to be used by tracker.
//
// Adds required:
//  - retention policies
//  - continuous queries
func influxDBpreparation(c Config) error {
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
		Debug:        c.Debug,
	}

	ok, err := influxDB.RetentionPolicy(model.TableTimespentRP, "2d")
	if err != nil {
		return err
	}
	if !ok {
		err = influxDB.AlterRetentionPolicy(model.TableTimespentRP, "2d")
		if err != nil {
			return err
		}
	}

	query := fmt.Sprintf(`SELECT SUM("timespent")
	INTO "%s"
	FROM "%s"."%s"
	GROUP BY time(15m), "user_id", "_article", "article_id", "remp_pageview_id", "social"`, model.TableTimespentAggregated, model.TableTimespentRP, model.TableTimespent)

	ok, err = influxDB.ContinuousQuery(model.TableTimespentAggregated, "1m", query)
	if err != nil {
		return err
	}
	if !ok {
		//TODO: decide how to handle if continuous query exists (needs to be DROPed and CREATEd againg)
		log.Printf("continuous query was not altered")
	}

	return nil
}
