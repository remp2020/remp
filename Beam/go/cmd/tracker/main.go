package main

import (
	"beam/cmd/tracker/controller"
	"beam/cmd/tracker/gen/track"
	"beam/model"
	"context"
	"fmt"
	"log"
	"os"
	"os/signal"
	"strings"
	"sync"
	"syscall"
	"time"

	"github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
	"github.com/pkg/errors"
)

func main() {
	logger := log.New(os.Stderr, "[tracker] ", log.Ltime)

	err := godotenv.Load()
	if err != nil {
		logger.Fatalln(errors.Wrap(err, "unable to load .env file"))
	}
	var c Config
	if err := envconfig.Process("tracker", &c); err != nil {
		logger.Fatalln(errors.Wrap(err, "unable to process envconfig"))
	}

	// Create channel used by both the signal handler and server goroutines
	// to notify the main goroutine when to stop the server.

	errc := make(chan error)

	go func() {
		c := make(chan os.Signal, 3)
		signal.Notify(c, syscall.SIGINT, syscall.SIGTERM, syscall.SIGQUIT)
		errc <- fmt.Errorf("%s", <-c)
	}()

	var wg sync.WaitGroup
	ctx, cancel := context.WithCancel(context.Background())

	// Broker init

	logger.Println("initializing message broker producer", "implementation", c.BrokerImpl)
	eventProducer, err := newProducer(ctx, &c, logger)
	if err != nil {
		logger.Fatalln(err)
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
		logger.Fatalln(errors.Wrap(err, "unable to connect to MySQL"))
	}

	propertyDB := &model.PropertyDB{
		MySQL: mysqlDB,
	}

	entitySchemaDB := &model.EntitySchemaDB{
		MySQL: mysqlDB,
	}

	// server cancellation

	ticker := time.NewTicker(10 * time.Second)
	defer ticker.Stop()

	cacheProperties := func() {
		if err = propertyDB.Cache(logger); err != nil {
			logger.Fatalln("unable to cache properties", "err", err)
		}
	}

	wg.Add(1)

	cacheEntities := func() {
		if err = entitySchemaDB.Cache(logger); err != nil {
			logger.Fatalln("unable to cache entity schemas", "err", err)
		}
	}
	wg.Add(1)

	cacheProperties()
	go func() {
		defer wg.Done()
		logger.Println("starting property caching")
		for {
			select {
			case <-ticker.C:
				cacheProperties()
			case <-ctx.Done():
				logger.Println("property caching stopped")
				return
			}
		}
	}()

	cacheEntities()
	go func() {
		defer wg.Done()
		logger.Println("starting entity schemas caching")
		for {
			select {
			case <-ticker.C:
				cacheEntities()
			case <-ctx.Done():
				logger.Println("entity schemas caching stopped")
				return
			}
		}
	}()

	// Initialize the services.
	trackSvc := controller.NewTrackController(
		eventProducer,
		propertyDB,
		entitySchemaDB,
		strings.Split(c.InternalHosts, ","),
		c.TimespentLimit,
	)

	// Wrap the services in endpoints that can be invoked from other services
	// potentially running in different processes.
	trackEndpoints := track.NewEndpoints(trackSvc)

	handleHTTPServer(ctx, c.TrackerAddr, trackEndpoints, &wg, errc, logger, c.Debug)

	// Wait for signal.
	logger.Printf("exiting (%v)", <-errc)

	// Send cancellation signal to the goroutines.
	cancel()

	wg.Wait()
	logger.Println("bye bye")
}

func newProducer(ctx context.Context, config *Config, logger *log.Logger) (controller.EventProducer, error) {
	switch config.BrokerImpl {
	case "kafka":
		brokerAddrs := strings.Split(config.BrokerAddrs, ",")
		for _, addr := range brokerAddrs {
			logger.Println("connecting to kafka broker", "bind", addr)
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
		logger.Println("connecting to pubsub", "projectID", config.PubSubProjectID, "topicID", config.PubSubTopicID)
		producer, err := controller.NewPubSubEventProducer(ctx, config.PubSubProjectID, config.PubSubTopicID)
		if err != nil {
			return nil, err
		}
		return producer, nil

	default:
		return nil, errors.Errorf("unknown broker implementation %q", config.BrokerImpl)
	}
}
