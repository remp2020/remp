package design

import (
	. "goa.design/goa/v3/dsl"
	cors "goa.design/plugins/v3/cors/dsl"
)

var _ = API("tracker", func() {
	Title("Beam tracker")
	Description("Track events and pageviews")
	License(func() {
		Name("MIT")
	})

	HTTP(func() {
		Consumes("application/json")
		Produces("application/json")
	})

	cors.Origin("*", func() {
		cors.Methods("GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS")
		cors.Headers("Content-Type")
		cors.MaxAge(3600)
		cors.Credentials()
	})

	Server("tracker_server", func() {
		Host("localhost", func() {
			Description("default host")
			URI("http://localhost:8081/")
		})
	})
})
