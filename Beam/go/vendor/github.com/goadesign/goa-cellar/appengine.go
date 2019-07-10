// +build appengine

package main

import (
	"net/http"
	"os"

	"google.golang.org/appengine"

	"github.com/go-kit/kit/log"
	"github.com/goadesign/goa"
	"github.com/goadesign/goa-cellar/app"
	"github.com/goadesign/goa-cellar/controllers"
	"github.com/goadesign/goa-cellar/store"
	"github.com/goadesign/goa/logging/kit"
	"github.com/goadesign/goa/middleware"
)

const (
	projectID  = "goa-cellar"
	bucketName = "artifacts.cellar.goa.design"
)

func main() {
	// Configure logging for appengine
	w := log.NewSyncWriter(os.Stderr)
	logger := log.NewLogfmtLogger(w)

	// Create goa application
	service := goa.New("cellar")
	service.WithLogger(goakit.New(logger))

	// Setup middleware
	service.Use(middleware.RequestID())
	service.Use(middleware.LogRequest(true))
	service.Use(middleware.ErrorHandler(service, true))
	service.Use(middleware.Recover())

	// Setup database connection
	db := store.NewDB()

	// Mount account controller onto application
	ac := controllers.NewAccount(service, db)
	app.MountAccountController(service, ac)

	// Mount bottle controller onto application
	bc := controllers.NewBottle(service, db)
	app.MountBottleController(service, bc)

	// Mount public controller onto service
	pc := controllers.NewPublic(service)
	app.MountPublicController(service, pc)

	// Mount js controller onto service
	jc := controllers.NewJs(service)
	app.MountJsController(service, jc)

	// Mount swagger controller onto service
	sc := controllers.NewSwagger(service)
	app.MountSwaggerController(service, sc)

	// Setup HTTP handler
	http.Handle("/", service.Mux)
	appengine.Main()
}
