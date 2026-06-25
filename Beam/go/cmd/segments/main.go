package main

import (
	"beam/cmd/segments/gen/commerce"
	"beam/cmd/segments/gen/concurrents"
	"beam/cmd/segments/gen/events"
	"beam/cmd/segments/gen/journal"
	"beam/cmd/segments/gen/pageviews"
	"beam/cmd/segments/gen/segments"
	"context"
	"crypto/tls"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"strings"
	"sync"
	"syscall"
	"time"

	"github.com/elastic/elastic-transport-go/v8/elastictransport"
	"github.com/elastic/go-elasticsearch/v8"
	"github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
	"github.com/patrickmn/go-cache"
	"github.com/pkg/errors"

	"beam/cmd/segments/controller"
	"beam/model"
)

func main() {
	logger := log.New(os.Stderr, "[segments] ", log.Ltime)

	err := godotenv.Load()
	if err != nil {
		logger.Fatalln(errors.Wrap(err, "unable to load .env file"))
	}
	var c Config
	if err := envconfig.Process("segments", &c); err != nil {
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

	var eventStorage model.EventStorage
	var pageviewStorage model.PageviewStorage
	var commerceStorage model.CommerceStorage
	var concurrentsStorage model.ConcurrentsStorage

	eventStorage, pageviewStorage, commerceStorage, concurrentsStorage, err = initGoElasticsearchStorages(ctx, c, logger)
	if err != nil {
		logger.Fatalln(err)
	}

	countCache := cache.New(5*time.Minute, 10*time.Minute)
	segmentStorage := &model.SegmentDB{
		MySQL:           mysqlDB,
		CountCache:      countCache,
		EventStorage:    eventStorage,
		PageviewStorage: pageviewStorage,
		CommerceStorage: commerceStorage,
	}

	segmentBlueprintStorage := &model.SegmentBlueprintDB{
		EventStorage:    eventStorage,
		PageviewStorage: pageviewStorage,
		CommerceStorage: commerceStorage,
	}

	// caching

	ticker10s := time.NewTicker(10 * time.Second)
	defer ticker10s.Stop()

	ticker1m := time.NewTicker(time.Minute)
	defer ticker1m.Stop()

	ticker1h := time.NewTicker(time.Hour)
	defer ticker1h.Stop()

	cacheSegmentDB := func() {
		if err = segmentStorage.Cache(); err != nil {
			logger.Fatalln("unable to cache segments", "err", err)
		}
	}
	cacheExplicitSegments := func() {
		if err = segmentStorage.CacheExplicitSegments(); err != nil {
			logger.Fatalln("unable to cache explicit segment", "err", err)
		}
	}
	cacheSegmentsCount := func() {
		if _, err = segmentStorage.CountAll(); err != nil {
			logger.Fatalln("unable to cache counts for segment", "err", err)
		}
	}
	cacheEventDB := func() {
		if err = eventStorage.Cache(); err != nil {
			logger.Fatalln("unable to cache events", "err", err)
		}
	}

	wg.Add(1)
	cacheSegmentDB()
	cacheExplicitSegments()
	cacheEventDB()
	cacheSegmentsCount()
	go func() {
		defer wg.Done()
		logger.Println("starting segment caching")
		logger.Println("starting explicit segment caching")
		logger.Println("starting events caching")
		logger.Println("starting segment counts caching")
		for {
			select {
			case <-ticker10s.C:
				cacheSegmentDB()
			case <-ticker1m.C:
				cacheExplicitSegments()
				cacheSegmentsCount()
			case <-ticker1h.C:
				cacheEventDB()
			case <-ctx.Done():
				logger.Println("segment caching stopped")
				logger.Println("explicit segment caching stopped")
				logger.Println("events caching stopped")
				logger.Println("segment counts caching stopped")
				return
			}
		}
	}()

	// controllers init

	segmentConfig := controller.SegmentConfig{
		URLEdit: c.URLEdit,
	}

	// Initialize the services.

	journalSvc := controller.NewJournalController(eventStorage, commerceStorage, pageviewStorage)
	eventsSvc := controller.NewEventController(eventStorage)
	pageviewsSvc := controller.NewPageviewController(pageviewStorage)
	concurrentsSvc := controller.NewConcurrentsController(concurrentsStorage)
	commerceSvc := controller.NewCommerceController(commerceStorage)
	segmentsSvc := controller.NewSegmentController(segmentStorage, segmentBlueprintStorage, segmentConfig)

	// Wrap the services in endpoints that can be invoked from other services
	// potentially running in different processes.

	journalEndpoints := journal.NewEndpoints(journalSvc)
	eventsEndpoints := events.NewEndpoints(eventsSvc)
	pageviewsEndpoints := pageviews.NewEndpoints(pageviewsSvc)
	concurrentsEndpoints := concurrents.NewEndpoints(concurrentsSvc)
	commerceEndpoints := commerce.NewEndpoints(commerceSvc)
	segmentsEndpoints := segments.NewEndpoints(segmentsSvc)

	handleHTTPServer(
		ctx,
		c.SegmentsAddr,
		journalEndpoints,
		eventsEndpoints,
		pageviewsEndpoints,
		concurrentsEndpoints,
		commerceEndpoints,
		segmentsEndpoints,
		&wg,
		errc,
		logger,
		c.Debug,
	)

	// Wait for signal.
	logger.Printf("exiting (%v)", <-errc)

	// Send cancellation signal to the goroutines.
	cancel()

	wg.Wait()
	logger.Println("bye bye")
}


func initGoElasticsearchStorages(ctx context.Context, c Config, logger *log.Logger) (model.EventStorage, model.PageviewStorage, model.CommerceStorage, model.ConcurrentsStorage, error) {
	elasticAddrs := strings.Split(c.ElasticAddrs, ",")

	cfg := elasticsearch.Config{
		Addresses: elasticAddrs,
		Username:  c.ElasticUser,
		Password:  c.ElasticPasswd,
		Transport: &http.Transport{
			TLSClientConfig: &tls.Config{
				InsecureSkipVerify: true,
			},
		},
	}

	if c.Debug {
		cfg.Logger = &elastictransport.TextLogger{
			Output:             os.Stdout,
			EnableRequestBody:  true,
			EnableResponseBody: true,
		}
	}

	ec, err := elasticsearch.NewClient(cfg)
	if err != nil {
		return nil, nil, nil, nil, errors.Wrap(err, "unable to initialize go-elasticsearch client")
	}

	esDB := model.NewElasticsearchDB(ctx, ec, c.IndexPrefix, c.Debug)

	eventStorage := &model.EventElasticsearch{
		DB: esDB,
	}
	commerceStorage := &model.CommerceElasticsearch{
		DB: esDB,
	}
	pageviewStorage := &model.PageviewElasticsearch{
		DB: esDB,
	}
	concurrentsStorage := &model.ConcurrentElasticsearch{
		DB: esDB,
	}

	// push explicit mapping to existing indices
	indexesAndMappings := [][]string{
		{"pageviews", `{"properties": {"subscriber": {"type": "boolean"},"signed_in": {"type": "boolean"}}}`},
		{"pageviews_time_spent", `{"properties": {"subscriber": {"type": "boolean"},"signed_in": {"type": "boolean"}}}`},
		{"pageviews_progress", `{"properties": {"subscriber": {"type": "boolean"},"signed_in": {"type": "boolean"}}}`},
		{"commerce", ""}, // no explicit mapping of `revenue` field here (see remp/remp#1419), preferably use ILM
		{"events", ""},
		{"concurrents_by_browser", ""},
		{"impressions", ""},
	}

	for _, row := range indexesAndMappings {
		index := row[0]
		mapping := row[1]

		exists, err := esDB.IndexOrAliasExists(ctx, index)
		if err != nil {
			return nil, nil, nil, nil, err
		}

		if exists && mapping != "" {
			err = esDB.PushMapping(ctx, index, mapping)
			if err != nil {
				return nil, nil, nil, nil, err
			}
		}
	}

	logger.Println("go-elasticsearch/v8 storages initialized successfully")
	return eventStorage, pageviewStorage, commerceStorage, concurrentsStorage, nil
}
