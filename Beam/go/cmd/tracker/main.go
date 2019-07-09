//go:generate goagen bootstrap -d gitlab.com/remp/remp/Beam/go/cmd/tracker/design

package main

import (
	"context"
	"log"
	"net/http"
	"os"
	"os/signal"
	"strings"
	"sync"
	"syscall"
	"time"

	"github.com/Shopify/sarama"
	"github.com/go-sql-driver/mysql"
	"github.com/goadesign/goa"
	"github.com/goadesign/goa/middleware"
	"github.com/jmoiron/sqlx"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/app"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/controller"
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

	brokerAddrs := strings.Split(c.BrokerAddrs, ",")
	for _, addr := range brokerAddrs {
		service.LogInfo("connecting to broker", "bind", addr)
	}

	eventProducer, err := newProducer(brokerAddrs)
	if err != nil {
		log.Fatalln(err)
	}
	defer eventProducer.Close()

	// DB init

	mysqlDBConfig := mysql.Config{
		Net:                  c.MysqlNet,
		Addr:                 c.MysqlAddr,
		User:                 c.MysqlUser,
		Passwd:               c.MysqlPasswd,
		DBName:               c.MysqlDBName,
		ParseTime:            true,
		AllowNativePasswords: true,
	}
	mysqlDB, err := sqlx.Connect("mysql", mysqlDBConfig.FormatDSN())
	if err != nil {
		log.Fatalln(errors.Wrap(err, "unable to connect to MySQL"))
	}

	propertyDB := &model.PropertyDB{
		MySQL: mysqlDB,
	}

	entitySchemaDB := &model.EntitySchemaDB{
		MySQL: mysqlDB,
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

	cacheEntities := func() {
		if err := entitySchemaDB.Cache(); err != nil {
			service.LogError("unable to cache entity schemas", "err", err)
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

	cacheEntities()
	go func() {
		defer wg.Done()
		service.LogInfo("starting entity schemas caching")
		for {
			select {
			case <-ticker.C:
				cacheEntities()
			case <-ctx.Done():
				service.LogInfo("entity schemas caching stopped")
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
		entitySchemaDB,
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
