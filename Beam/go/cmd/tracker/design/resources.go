package design

import . "github.com/goadesign/goa/design"
import . "github.com/goadesign/goa/design/apidsl"

var _ = Resource("swagger", func() {
	Origin("*", func() {
		Methods("GET", "OPTIONS")
		Headers("*")
	})
	NoSecurity()
	Files("/swagger.json", "public/swagger/swagger.json")
})

var _ = Resource("track", func() {

	Description("Track different types of events")
	BasePath("/track")
	NoSecurity()

	Action("pageview", func() {
		Description("Track new pageview")
		Payload(TrackPageview)
		Routing(POST("/pageview"))
		Response(Accepted)
	})
	Action("commerce", func() {
		Description("Track new pageview")
		Payload(TrackCommerce)
		Routing(POST("/commerce"))
		Response(Accepted)
	})
	Action("event", func() {
		Description("Track generic event")
		Payload(TrackEvent)
		Routing(POST("/event"))
		Response(Accepted)
	})
})
