package design

import . "github.com/goadesign/goa/design"
import . "github.com/goadesign/goa/design/apidsl"

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
		Response(BadRequest)
		Response(Accepted)
	})
	Action("commerce", func() {
		Description("Track new pageview")
		Payload(Commerce)
		Routing(POST("/commerce"))
		Response(BadRequest)
		Response(Accepted)
	})
	Action("event", func() {
		Description("Track generic event")
		Payload(Event)
		Routing(POST("/event"))
		Response(BadRequest)
		Response(Accepted)
	})
})
