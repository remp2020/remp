package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

var _ = Resource("swagger", func() {
	Origin("*", func() {
		Methods("GET", "OPTIONS")
		Headers("*")
	})
	NoSecurity()
	Files("/swagger.json", "swagger/swagger.json")
})

var _ = Resource("track", func() {

	Description("Track different types of events")
	BasePath("/track")
	NoSecurity()

	Action("pageview", func() {
		Description("Track new pageview")
		Payload(Pageview)
		Routing(POST("/pageview"))
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(NotFound, func() {
			Description("Returned when property_token was not found")
		})
		Response(Accepted)
	})
	Action("commerce", func() {
		Description("Track new pageview")
		Payload(Commerce)
		Routing(POST("/commerce"))
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(NotFound, func() {
			Description("Returned when property_token was not found")
		})
		Response(Accepted)
	})
	Action("event", func() {
		Description("Track generic event")
		Payload(Event)
		Routing(POST("/event"))
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(NotFound, func() {
			Description("Returned when property_token was not found")
		})
		Response(Accepted)
	})
	Action("entity", func() {
		Description("Track generic entity")
		Payload(Entity)
		Routing(POST("/entity"))
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification or entity data does not pass validation")
		})
		Response(NotFound, func() {
			Description("Returned when property_token was not found")
		})
		Response(Accepted)
	})
})
