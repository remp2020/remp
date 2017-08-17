package design

import . "github.com/goadesign/goa/design"
import . "github.com/goadesign/goa/design/apidsl"

const (
	SegmentPattern = `^[a-zA-Z0-9_\-@.]+$`
	UserPattern    = `^[a-zA-Z0-9_\-@.]+$`
)

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
		Response(BadRequest)
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
			Param("segment_code", String, "Segment code", func() {
				Pattern(SegmentPattern)
			})
			Param("user_id", String, "User ID", func() {
				Pattern(UserPattern)
			})
		})
		Response(NotFound)
		Response(BadRequest)
		Response(OK, func() {
			Media(SegmentCheck)
		})
	})
	Action("users", func() {
		Description("List users of segment.")
		Routing(
			GET("/:segment_code/users"),
		)
		Params(func() {
			Param("segment_code", String, "Segment code", func() {
				Pattern(SegmentPattern)
			})
		})
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
			Param("time_after", DateTime, "Include all events that happened after specified RFC3339 datetime")
			Param("time_before", DateTime, "Include all events that happened before specified RFC3339 datetime")
		})
		Response(OK, func() {
			Media(Count)
		})
	})
	Action("list", func() {
		Description("Returns full list of events")
		Routing(GET("/list"))
		Params(func() {
			Param("user_id", String, "Identification of user")
			Param("action", String, "Event action")
			Param("category", String, "Event category")
			Param("time_after", DateTime, "Include all events that happened after specified RFC3339 datetime")
			Param("time_before", DateTime, "Include all events that happened before specified RFC3339 datetime")
		})
		Response(OK, func() {
			Media(CollectionOf(Event, func() {
				View("default")
			}))
		})
	})
	Action("categories", func() {
		Description("List of all tracked categories")
		Routing(GET("/categories"))
		Response(OK, ArrayOf(String))
	})
	Action("actions", func() {
		Description("List of all tracked actions for given category")
		Routing(GET("/categories/:category/actions"))
		Params(func() {
			Param("category", String, "Category under which the actions were tracked")
		})
		Response(OK, ArrayOf(String))
	})
})

var _ = Resource("commerce", func() {
	Description("Commerce journal")
	BasePath("/journal/commerce")
	NoSecurity()

	Action("count", func() {
		Description("Returns counts of events")
		Routing(GET("/:step/count"))
		Params(func() {
			Param("filter_by", String, "Selection of data filtering type", func() {
				Enum("users", "articles", "authors")
			})
			Param("ids", ArrayOf(String), "Filter for selection groupping (used only when _filter_by_ is set)")
			Param("group", Boolean, "Whether to group results by filter values (used only when _filter_by_ is set)")
			Param("step", String, "Identification of commerce step", func() {
				Enum("checkout", "payment", "purchase", "refund")
			})
			Param("time_after", DateTime, "Include all events that happened after specified RFC3339 datetime")
			Param("time_before", DateTime, "Include all events that happened before specified RFC3339 datetime")
		})
		Response(OK, func() {
			Media(GrouppedCounts)
		})
	})
	Action("sum", func() {
		Description("Returns sum of amounts within events")
		Routing(GET("/:step/sum"))
		Params(func() {
			Param("filter_by", String, "Selection of data filtering type", func() {
				Enum("users", "articles", "authors")
			})
			Param("ids", ArrayOf(String), "Filter for selection groupping (used only when _filter_by_ is set)")
			Param("group", Boolean, "Whether to group results by filter values (used only when _filter_by_ is set)")
			Param("step", String, "Identification of commerce step", func() {
				Enum("checkout", "payment", "purchase", "refund")
			})
			Param("time_after", DateTime, "Include all events that happened after specified RFC3339 datetime")
			Param("time_before", DateTime, "Include all events that happened before specified RFC3339 datetime")
		})
		Response(OK, func() {
			Media(GrouppedSums)
		})
	})
	Action("list", func() {
		Description("Returns full list of events")
		Routing(GET("/list"))
		Params(func() {
			Param("filter_by", String, "Selection of data filtering type", func() {
				Enum("users", "articles", "authors")
			})
			Param("ids", ArrayOf(String), "Filter for selection groupping (used only when _filter_by_ is set)")
			Param("group", Boolean, "Whether to group results by filter values (used only when _filter_by_ is set)")
			Param("step", String, "Identification of commerce step", func() {
				Enum("checkout", "payment", "purchase", "refund")
			})
			Param("time_after", DateTime, "Include all events that happened after specified RFC3339 datetime")
			Param("time_before", DateTime, "Include all events that happened before specified RFC3339 datetime")
		})
		Response(OK, func() {
			Media(CommerceList)
		})
	})
})
