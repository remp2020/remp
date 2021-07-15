//go:generate goagen bootstrap -d beam/cmd/tracker/design

package main

import (
	"beam/cmd/tracker/app"
	"beam/cmd/tracker/controller"
	"beam/model"
	"context"
	"log"
	"net/http"
	"os"
	"os/signal"
	"strings"
	"sync"
	"syscall"
	"time"

	"github.com/go-sql-driver/mysql"
	"github.com/goadesign/goa"
	"github.com/goadesign/goa/middleware"
	"github.com/jmoiron/sqlx"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
	"github.com/pkg/errors"
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

	ctx, cancelCtx := context.WithCancel(context.Background())

	service := goa.New("tracker")

	service.Use(middleware.RequestID())
	if c.Debug {
		service.Use(middleware.LogRequest(true))
		service.Use(middleware.LogResponse())
	}
	service.Use(middleware.ErrorHandler(service, true))
	service.Use(middleware.Recover())

	// Broker init

	service.LogInfo("initializing message broker producer", "implementation", c.BrokerImpl)
	eventProducer, err := newProducer(ctx, &c, service)
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
		strings.Split(c.InternalHosts, ","),
		c.TimespentLimit,
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

func newProducer(ctx context.Context, config *Config, service *goa.Service) (controller.EventProducer, error) {
	switch config.BrokerImpl {
	case "kafka":
		brokerAddrs := strings.Split(config.BrokerAddrs, ",")
		for _, addr := range brokerAddrs {
			service.LogInfo("connecting to kafka broker", "bind", addr)
		}
		saslConfig := &controller.SaslConfig{
			Username: config.KafkaSaslUser,
			Password: config.KafkaSaslPasswd,
		}
		producer, err := controller.NewKafkaEventProducer(brokerAddrs, saslConfig)
		if err != nil {
			return nil, err
		}
		return producer, nil

	case "pubsub":
		service.LogInfo("connecting to pubsub", "projectID", config.PubSubProjectId, "topicID", config.PubSubTopicId)
		producer, err := controller.NewPubSubEventProducer(ctx, config.PubSubProjectId, config.PubSubTopicId)
		if err != nil {
			return nil, err
		}
		return producer, nil

	default:
		return nil, errors.Errorf("unknown broker implementation %q", config.BrokerImpl)
	}
}
