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

var _ = Resource("segments", func() {
	Description("Segment operations")
	BasePath("/segments")
	NoSecurity()

	Action("list", func() {
		Description("List all segments.")
		Routing(GET("/"))
		Response(NotFound)
		Response(OK, func() {
			Media(CollectionOf(Segment, func() {
				View("default")
			}))
		})
	})
	Action("check", func() {
		Description("Retrieve segment with given ID.")
		Routing(GET("/:segment_code/check/:user_id"))
		Params(func() {
			Param("segment_code", String, "Segment code")
			Param("user_id", String, "User ID")
		})
		Response(NotFound)
		Response(OK, func() {
			Media(SegmentCheck)
		})
	})
	Action("users", func() {
		Description("Change segment.")
		Routing(
			GET("/:segment_code/users"),
		)
		Params(func() {
			Param("segment_code", UUID, "Segment code")
		})
		Payload(SegmentPayload)
		Response(NotFound)
		Response(BadRequest)
		Response(OK, func() {
			Media(CollectionOf(User, func() {
				View("default")
			}))
		})
	})
})

var _ = Resource("events", func() {
	Description("Events journal")
	BasePath("/journal/events")
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
