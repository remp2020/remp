//go:generate goagen bootstrap -d gitlab.com/remp/remp/Beam/go/cmd/tracker/design

package main

import (
	"context"
	"net/http"
	"time"

	"github.com/goadesign/goa"
	"github.com/goadesign/goa/middleware"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/app"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/controller"
)

func main() {
	service := goa.New("beam")

	service.Use(middleware.RequestID())
	service.Use(middleware.LogRequest(true))
	service.Use(middleware.ErrorHandler(service, true))
	service.Use(middleware.Recover())

	app.MountSwaggerController(service, service.NewController("swagger"))
	app.MountTrackController(service, controller.NewTrackController(service))

	server := &http.Server{
		Addr:    ":8080",
		Handler: service.Mux,
	}

	if err := server.ListenAndServe(); err != nil {
		service.LogError("startup", "err", err)
	}

	ctx, _ := context.WithTimeout(context.Background(), 5*time.Second)
	server.Shutdown(ctx)

}
