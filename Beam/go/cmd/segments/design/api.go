package design

import (
	. "goa.design/goa/v3/dsl"
	cors "goa.design/plugins/v3/cors/dsl"
)

var _ = API("journal", func() {
	Title("Beam Journal")
	Description("Provides journal records")
	License(func() {
		Name("MIT")
	})

	Server("journal_server", func() {
		Host("localhost", func() {
			Description("default host")
			URI("http://localhost:8082/")
		})
	})

	cors.Origin("*", func() {
		cors.Methods("GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS")
		cors.Headers("Content-Type", "Authorization")
	})

	HTTP(func() {
		Consumes("application/json")
		Produces("application/json")
	})

})
