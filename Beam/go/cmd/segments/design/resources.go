package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

// set of constants reused within multiple actions
const (
	SegmentPattern        = `^[a-zA-Z0-9_\-@.]+$`
	UserPattern           = `^[a-zA-Z0-9_\-@.]+$`
	CacheParamDescription = `JSON-encoded object of internal cache with count of events provided by third party (mostly Beam's remplib.js), e.g.:

	{
		10: { // segment rule ID
			"d": "2017-11-07T08:06:26.612Z" // RFC3339 date
			"c": 11 // number of occurrences
		}
	}`
	FieldsParamDescription = `JSON-encoded object of overriden pairs, e.g.:

	{
		"utm_campaign": "custom-campaign-id",
		// ...
	}`
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
	Action("check_user", func() {
		Description("Check whether given user ID belongs to segment.")
		Routing(GET("/:segment_code/users/check/:user_id"))
		Params(func() {
			Param("segment_code", String, "Segment code", func() {
				Pattern(SegmentPattern)
			})
			Param("user_id", String, "User ID", func() {
				Pattern(UserPattern)
			})
			Param("fields", String, FieldsParamDescription)
			Param("cache", String, CacheParamDescription)
		})
		Response(NotFound)
		Response(BadRequest)
		Response(OK, func() {
			Media(SegmentCheck)
		})
	})
	Action("check_browser", func() {
		Description("Check whether given browser ID belongs to segment.")
		Routing(GET("/:segment_code/browsers/check/:browser_id"))
		Params(func() {
			Param("segment_code", String, "Segment code", func() {
				Pattern(SegmentPattern)
			})
			Param("browser_id", String, "Browser ID", func() {
				Pattern(UserPattern)
			})
			Param("fields", String, FieldsParamDescription)
			Param("cache", String, CacheParamDescription)
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
			Param("fields", String, FieldsParamDescription)
		})
		Payload(RuleOverrides)
		Response(NotFound)
		Response(BadRequest)
		Response(OK, ArrayOf(String))
	})
})

var _ = Resource("journal", func() {
	Description("Common journal calls")
	BasePath("/journal")
	NoSecurity()

	Action("flags", func() {
		Description("List of all available flags")
		Routing(GET("/flags"))
		Response(OK, Flags)
	})
})

var _ = Resource("events", func() {
	Description("Events journal")
	BasePath("/journal/events")
	NoSecurity()

	Action("count", func() {
		Description("Returns counts of events")
		Routing(POST("/categories/:category/actions/:action/count"))
		Payload(EventOptionsPayload)
		Params(func() {
			Param("action", String, "Event action")
			Param("category", String, "Event category")
		})
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, func() {
			Media(CollectionOf(Count, func() {
				View("default")
			}))
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
	Action("users", func() {
		Description("List of all tracked users")
		Routing(GET("/users"))
		Response(OK, ArrayOf(String))
	})
})

var _ = Resource("commerce", func() {
	Description("Commerce journal")
	BasePath("/journal/commerce")
	NoSecurity()

	Action("count", func() {
		Description("Returns counts of commerce events")
		Payload(CommerceOptionsPayload)
		Routing(POST("/steps/:step/count"))
		Params(func() {
			Param("step", String, "Identification of commerce step", func() {
				Enum("checkout", "payment", "purchase", "refund")
			})
		})
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, func() {
			Media(CollectionOf(Count, func() {
				View("default")
			}))
		})
	})

	Action("sum", func() {
		Description("Returns sum of amounts within events")
		Payload(CommerceOptionsPayload)
		Routing(POST("/steps/:step/sum"))
		Params(func() {
			Param("step", String, "Identification of commerce step", func() {
				Enum("checkout", "payment", "purchase", "refund")
			})
		})
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, func() {
			Media(CollectionOf(Sum, func() {
				View("default")
			}))
		})
	})
	Action("list", func() {
		Description("Returns full list of events")
		Routing(POST("/steps/:step/list"))
		Params(func() {
			Param("step", String, "Identification of commerce step", func() {
				Enum("checkout", "payment", "purchase", "refund")
			})
		})
		Payload(CommerceOptionsPayload)
		Response(OK, func() {
			Media(CollectionOf(Commerce, func() {
				View("default")
			}))
		})
	})
	Action("categories", func() {
		Description("List of all available categories")
		Routing(GET("/categories"))
		Response(OK, ArrayOf(String))
	})
	Action("actions", func() {
		Description("List of all available actions for given category")
		Routing(GET("/categories/:category/actions"))
		Params(func() {
			Param("category", String, "Category (step) under which the actions were tracked")
		})
		Response(OK, ArrayOf(String))
	})
})

var _ = Resource("pageviews", func() {
	Description("Pageviews journal")
	BasePath("/journal/pageviews")
	NoSecurity()

	Action("count", func() {
		Description("Returns counts of pageviews")
		Payload(PageviewOptionsPayload)
		Routing(POST("/actions/:action/count"))
		Params(func() {
			Param("action", String, "Identification of pageview action", func() {
				Enum("load")
			})
		})
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, func() {
			Media(CollectionOf(Count, func() {
				View("default")
			}))
		})
	})
	Action("sum", func() {
		Description("Returns sum of amounts within events")
		Payload(PageviewOptionsPayload)
		Routing(POST("/actions/:action/sum"))
		Params(func() {
			Param("action", String, "Identification of pageview action", func() {
				Enum("timespent")
			})
		})
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, func() {
			Media(CollectionOf(Sum, func() {
				View("default")
			}))
		})
	})
	Action("list", func() {
		Description("Returns full list of pageviews")
		Routing(POST("/list"))
		Payload(ListPageviewOptionsPayload)
		Response(OK, func() {
			Media(CollectionOf(Pageviews, func() {
				View("default")
			}))
		})
	})
	Action("categories", func() {
		Description("List of all available categories")
		Routing(GET("/categories"))
		Response(OK, ArrayOf(String))
	})
	Action("actions", func() {
		Description("List of all available actions for given category")
		Routing(GET("/categories/:category/actions"))
		Params(func() {
			Param("category", String, "Category under which the actions were tracked")
		})
		Response(OK, ArrayOf(String))
	})
})
