//go:generate goagen -d gitlab.com/remp/remp/Beam/go/cmd/segments/design

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

	"github.com/go-sql-driver/mysql"
	"github.com/goadesign/goa"
	"github.com/goadesign/goa/middleware"
	"github.com/jmoiron/sqlx"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
	"github.com/olivere/elastic"
	cache "github.com/patrickmn/go-cache"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/controller"
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

	stop := make(chan os.Signal, 3)
	signal.Notify(stop, syscall.SIGINT, syscall.SIGTERM, syscall.SIGQUIT)

	ctx, cancelCtx := context.WithCancel(context.Background())

	service := goa.New("segments")

	service.Use(middleware.RequestID())
	if c.Debug {
		service.Use(middleware.LogRequest(true))
		service.Use(middleware.LogResponse())
	}
	service.Use(middleware.ErrorHandler(service, true))
	service.Use(middleware.Recover())

	app.MountSwaggerController(service, service.NewController("swagger"))

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

	var eventStorage model.EventStorage
	var pageviewStorage model.PageviewStorage
	var commerceStorage model.CommerceStorage
	var concurrentsStorage model.ConcurrentsStorage

	eventStorage, pageviewStorage, commerceStorage, concurrentsStorage, err = initElasticEventStorages(ctx, c)
	if err != nil {
		log.Fatalln(err)
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

	// server cancellation

	var wg sync.WaitGroup

	// caching

	ticker10s := time.NewTicker(10 * time.Second)
	defer ticker10s.Stop()

	ticker1m := time.NewTicker(time.Minute)
	defer ticker1m.Stop()

	ticker1h := time.NewTicker(time.Hour)
	defer ticker1h.Stop()

	cacheSegmentDB := func() {
		if err := segmentStorage.Cache(); err != nil {
			service.LogError("unable to cache segments", "err", err)
		}
	}
	cacheExplicitSegments := func() {
		if err := segmentStorage.CacheExplicitSegments(); err != nil {
			service.LogError("unable to cache explicit segment", "err", err)
		}
	}
	cacheSegmentsCount := func() {
		if _, err := segmentStorage.CountAll(); err != nil {
			service.LogError("unable to cache counts for segment", "err", err)
		}
	}
	cacheEventDB := func() {
		if err := eventStorage.Cache(); err != nil {
			service.LogError("unable to cache events", "err", err)
		}
	}

	wg.Add(1)
	cacheSegmentDB()
	cacheExplicitSegments()
	cacheEventDB()
	cacheSegmentsCount()
	go func() {
		defer wg.Done()
		service.LogInfo("starting property caching")
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
				service.LogInfo("property caching stopped")
				return
			}
		}
	}()

	// controllers init

	segmentConfig := controller.SegmentConfig{
		URLEdit: c.URLEdit,
	}

	app.MountJournalController(service, controller.NewJournalController(service, eventStorage, commerceStorage, pageviewStorage))
	app.MountEventsController(service, controller.NewEventController(service, eventStorage))
	app.MountCommerceController(service, controller.NewCommerceController(service, commerceStorage))
	app.MountPageviewsController(service, controller.NewPageviewController(service, pageviewStorage))
	app.MountSegmentsController(service, controller.NewSegmentController(service, segmentStorage, segmentBlueprintStorage, segmentConfig))
	app.MountConcurrentsController(service, controller.NewConcurrentsController(service, concurrentsStorage))

	// server init

	service.LogInfo("starting server", "bind", c.SegmentsAddr)
	srv := &http.Server{
		Addr:    c.SegmentsAddr,
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

func initElasticEventStorages(ctx context.Context, c Config) (model.EventStorage, model.PageviewStorage, model.CommerceStorage, model.ConcurrentsStorage, error) {
	elasticAddrs := strings.Split(c.ElasticAddrs, ",")
	eopts := []elastic.ClientOptionFunc{
		elastic.SetBasicAuth(c.ElasticUser, c.ElasticPasswd),
		elastic.SetURL(elasticAddrs...),
		elastic.SetSniff(false),
		elastic.SetHealthcheckInterval(10 * time.Second),
		elastic.SetErrorLog(log.New(os.Stderr, "ELASTIC ", log.LstdFlags)),
	}
	if c.Debug {
		eopts = append(
			eopts,
			elastic.SetInfoLog(log.New(os.Stdout, "", log.LstdFlags)),
			elastic.SetTraceLog(log.New(os.Stdout, "", log.LstdFlags)),
		)
	}
	ec, err := elastic.NewClient(eopts...)
	if err != nil {
		return nil, nil, nil, nil, errors.Wrap(err, "unable to initialize elasticsearch client")
	}
	elasticDB := model.NewElasticDB(ctx, ec, c.Debug)

	eventStorage := &model.EventElastic{
		DB: elasticDB,
	}
	commerceStorage := &model.CommerceElastic{
		DB: elasticDB,
	}
	pageviewStorage := &model.PageviewElastic{
		DB: elasticDB,
	}
	concurrentsStorage := &model.ConcurrentElastic{
		DB: elasticDB,
	}

	return eventStorage, pageviewStorage, commerceStorage, concurrentsStorage, nil
}
