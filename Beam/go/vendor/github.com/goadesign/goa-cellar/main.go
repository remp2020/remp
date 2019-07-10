// +build !appengine,!appenginevm

package main

import (
	"os"

	"github.com/go-kit/kit/log"
	"github.com/goadesign/goa"
	"github.com/goadesign/goa-cellar/app"
	"github.com/goadesign/goa-cellar/controllers"
	"github.com/goadesign/goa-cellar/store"
	"github.com/goadesign/goa/logging/kit"
	"github.com/goadesign/goa/middleware"
)

func main() {
	// Create goa service
	service := goa.New("cellar")

	// Setup logger
	w := log.NewSyncWriter(os.Stderr)
	logger := log.NewLogfmtLogger(w)
	service.WithLogger(goakit.New(logger))

	// Setup basic middleware
	service.Use(middleware.RequestID())
	service.Use(middleware.LogRequest(true))
	service.Use(middleware.ErrorHandler(service, true))
	service.Use(middleware.Recover())

	// Setup database connection
	db := store.NewDB()

	// Mount account controller onto service
	ac := controllers.NewAccount(service, db)
	app.MountAccountController(service, ac)

	// Mount bottle controller onto service
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

	// Run service
	if err := service.ListenAndServe(":8081"); err != nil {
		service.LogError(err.Error())
	}
}
