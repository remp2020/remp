package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

var _ = API("beam", func() {
	Title("Beam tracker")
	Description("Track events and pageviews")
	License(func() {
		Name("MIT")
	})
	Scheme("http")
	Consumes("application/json")
	Produces("application/json")
	Origin("*", func() {
		Methods("GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS")
		Headers("Content-Type")
		MaxAge(3600)
	})
	ResponseTemplate(BadRequest, func() {
		Description("Invalid request sent")
		Status(400)
		Media(ErrorMedia)
	})
	Host("localhost:8081")
})
