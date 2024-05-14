package main

import (
	"beam/cmd/segments/gen/commerce"
	"beam/cmd/segments/gen/concurrents"
	"beam/cmd/segments/gen/events"
	commercesvr "beam/cmd/segments/gen/http/commerce/server"
	concurrentssvr "beam/cmd/segments/gen/http/concurrents/server"
	eventssvr "beam/cmd/segments/gen/http/events/server"
	journalsvr "beam/cmd/segments/gen/http/journal/server"
	pageviewssvr "beam/cmd/segments/gen/http/pageviews/server"
	segmentssvr "beam/cmd/segments/gen/http/segments/server"
	swaggersvr "beam/cmd/segments/gen/http/swagger/server"
	"beam/cmd/segments/gen/journal"
	"beam/cmd/segments/gen/pageviews"
	"beam/cmd/segments/gen/segments"
	"context"
	"log"
	"net/http"
	"os"
	"sync"
	"time"

	goahttp "goa.design/goa/v3/http"
	httpmdlwr "goa.design/goa/v3/http/middleware"
	"goa.design/goa/v3/middleware"
)

// handleHTTPServer starts configures and starts a HTTP server on the given
// URL. It shuts down the server if any error is received in the error channel.
func handleHTTPServer(
	ctx context.Context,
	addr string,
	journalEndpoints *journal.Endpoints,
	eventsEndpoints *events.Endpoints,
	pageviewsEndpoints *pageviews.Endpoints,
	concurrentsEndpoints *concurrents.Endpoints,
	commerceEndpoints *commerce.Endpoints,
	segmentsEndpoints *segments.Endpoints,
	wg *sync.WaitGroup,
	errc chan error,
	logger *log.Logger,
	debug bool) {

	// Setup goa log adapter.
	adapter := middleware.NewLogger(logger)

	// Provide the transport specific request decoder and response encoder.
	// The goa http package has built-in support for JSON, XML and gob.
	// Other encodings can be used by providing the corresponding functions,
	// see goa.design/implement/encoding.
	var (
		dec = goahttp.RequestDecoder
		enc = goahttp.ResponseEncoder
	)

	// Build the service HTTP request multiplexer and configure it to serve
	// HTTP requests to the service endpoints.
	mux := goahttp.NewMuxer()

	// Wrap the endpoints with the transport specific layers. The generated
	// server packages contains code generated from the design which maps
	// the service input and output data structures to HTTP requests and
	// responses.
	eh := errorHandler(logger)
	swaggerServer := swaggersvr.New(nil, mux, dec, enc, eh, nil, nil, nil)
	journalServer := journalsvr.New(journalEndpoints, mux, dec, enc, eh, nil)
	eventsServer := eventssvr.New(eventsEndpoints, mux, dec, enc, eh, nil)
	pageviewsServer := pageviewssvr.New(pageviewsEndpoints, mux, dec, enc, eh, nil)
	concurrentsServer := concurrentssvr.New(concurrentsEndpoints, mux, dec, enc, eh, nil)
	commerceServer := commercesvr.New(commerceEndpoints, mux, dec, enc, eh, nil)
	segmentsServer := segmentssvr.New(segmentsEndpoints, mux, dec, enc, eh, nil)
	if debug {
		servers := goahttp.Servers{
			swaggerServer,
			journalServer,
			eventsServer,
			pageviewsServer,
			concurrentsServer,
			commerceServer,
			segmentsServer,
		}
		servers.Use(httpmdlwr.Debug(mux, os.Stdout))
	}

	// Configure the mux.
	swaggersvr.Mount(mux, swaggerServer)
	journalsvr.Mount(mux, journalServer)
	eventssvr.Mount(mux, eventsServer)
	pageviewssvr.Mount(mux, pageviewsServer)
	concurrentssvr.Mount(mux, concurrentsServer)
	commercesvr.Mount(mux, commerceServer)
	segmentssvr.Mount(mux, segmentsServer)

	// Wrap the multiplexer with additional middlewares. Middlewares mounted
	// here apply to all the service endpoints.
	var handler http.Handler = mux
	{
		if debug {
			handler = httpmdlwr.Log(adapter)(handler)
		}
		handler = httpmdlwr.RequestID()(handler)
	}

	// Start HTTP server using default configuration, change the code to
	// configure the server as required by your service.
	srv := &http.Server{Addr: addr, Handler: handler, ReadHeaderTimeout: time.Second * 60}
	for _, m := range swaggerServer.Mounts {
		logger.Printf("HTTP %q mounted on %s %s", m.Method, m.Verb, m.Pattern)
	}
	for _, m := range journalServer.Mounts {
		logger.Printf("HTTP %q mounted on %s %s", m.Method, m.Verb, m.Pattern)
	}
	for _, m := range eventsServer.Mounts {
		logger.Printf("HTTP %q mounted on %s %s", m.Method, m.Verb, m.Pattern)
	}
	for _, m := range pageviewsServer.Mounts {
		logger.Printf("HTTP %q mounted on %s %s", m.Method, m.Verb, m.Pattern)
	}
	for _, m := range concurrentsServer.Mounts {
		logger.Printf("HTTP %q mounted on %s %s", m.Method, m.Verb, m.Pattern)
	}
	for _, m := range commerceServer.Mounts {
		logger.Printf("HTTP %q mounted on %s %s", m.Method, m.Verb, m.Pattern)
	}
	for _, m := range segmentsServer.Mounts {
		logger.Printf("HTTP %q mounted on %s %s", m.Method, m.Verb, m.Pattern)
	}

	(*wg).Add(1)
	go func() {
		defer (*wg).Done()

		// Start HTTP server in a separate goroutine.
		go func() {
			logger.Printf("HTTP server listening on %q", addr)
			errc <- srv.ListenAndServe()
		}()

		<-ctx.Done()
		logger.Printf("shutting down HTTP server at %q", addr)

		// Shutdown gracefully with a 30s timeout.
		ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
		defer cancel()

		err := srv.Shutdown(ctx)
		if err != nil {
			logger.Printf("failed to shutdown: %v", err)
		}
	}()
}

// errorHandler returns a function that writes and logs the given error.
// The function also writes and logs the error unique ID so that it's possible
// to correlate.
func errorHandler(logger *log.Logger) func(context.Context, http.ResponseWriter, error) {
	return func(ctx context.Context, w http.ResponseWriter, err error) {
		id := ctx.Value(middleware.RequestIDKey).(string)
		_, _ = w.Write([]byte("[" + id + "] encoding: " + err.Error()))
		logger.Printf("[%s] ERROR: %s", id, err.Error())
	}
}
