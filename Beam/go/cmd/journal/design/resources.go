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

var _ = Resource("events", func() {

	Description("Events journal")
	BasePath("/events")
	NoSecurity()

	Action("count", func() {
		Description("Returns counts of events")
		Routing(GET("/count"))
		Params(func() {
			Param("user_id", String, "Identification of user")
			Param("action", String, "Event action")
			Param("category", String, "Event category")
			Param("time_after", DateTime, "Include all events that happened after specified time")
			Param("time_before", DateTime, "Include all events that happened before specified time")
		})
		Response(OK, func() {
			Media(EventCount)
		})
	})
	Action("list", func() {
		Description("Returns full list of events")
		Routing(GET("/list"))
		Params(func() {
			Param("user_id", String, "Identification of user")
			Param("action", String, "Event action")
			Param("category", String, "Event category")
			Param("time_after", DateTime, "Include all events that happened after specified time")
			Param("time_before", DateTime, "Include all events that happened before specified time")
		})
		Response(OK, func() {
			Media(EventList)
		})
	})
})
