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

	Action("get", func() {
		Description("Get segment")
		Routing(GET("/show"))
		Params(func() {
			Param("id", Integer, "Segment ID")
			Required("id")
		})
		Response(NotFound)
		Response(BadRequest)
		Response(OK, SegmentersSegment)
	})
	Action("list", func() {
		Description("List all segments.")
		Routing(GET("/"))
		Response(NotFound)
		Response(BadRequest)
		Response(OK, func() {
			Media(CollectionOf(Segment, func() {
				View("default")
				View("tiny")
				View("extended")
				View("segmenter")
			}))
		})
	})
	Action("groups", func() {
		Description("List all segment groups.")
		Routing(GET("/groups"))
		Response(OK, func() {
			Media(SegmentGroupsFallback)
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
		Response(NotFound)
		Response(BadRequest)
		Response(OK, ArrayOf(String))
	})
	Action("criteria", func() {
		Description("Provide segment blueprint with criteria for individual tables and fields")
		Routing(
			GET("/criteria"),
		)
		Response(OK, SegmentBlueprint)
	})
	// TODO: divide to two separate endpoints after CRM API refactoring
	Action("create_or_update", func() {
		Description("Create or update segment (for fupdate, use GET parameter ?id={segment_id})")
		Payload(SegmentPayload)
		Routing(POST("/detail"))
		Params(func() {
			Param("id", Integer, "Segment ID")
		})
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(NotFound, func() {
			Description("Returned when segment with provided ID doesn't exist")
		})
		Response(OK, Segment)
	})
	Action("count", func() {
		Description("Returns number of users in segment based on provided criteria")
		Payload(SegmentTinyPayload)
		Routing(POST("/count"))
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, SegmentCount)
	})
	Action("related", func() {
		Description("Returns segments with same or similar criteria")
		Payload(SegmentTinyPayload)
		Routing(POST("/related"))
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, RelatedSegments)
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

	Action("count_action", func() {
		Description("Returns counts of events for given action and category")
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
	Action("count", func() {
		Description("Returns counts of events")
		Routing(POST("/count"))
		Payload(EventOptionsPayload)
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
		Routing(POST("/list"))
		Payload(ListEventOptionsPayload)
		Response(OK, func() {
			Media(CollectionOf(Events, func() {
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

	Action("count_step", func() {
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

	Action("count", func() {
		Description("Returns counts of commerce events")
		Payload(CommerceOptionsPayload)
		Routing(POST("/count"))
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, func() {
			Media(CollectionOf(Count, func() {
				View("default")
			}))
		})
	})

	Action("sum_step", func() {
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

	Action("sum", func() {
		Description("Returns sum of amounts within events")
		Payload(CommerceOptionsPayload)
		Routing(POST("/sum"))
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
		Routing(POST("/list"))
		Payload(ListCommerceOptionsPayload)
		Response(OK, func() {
			Media(CollectionOf(Commerces, func() {
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
				Enum("load", "progress")
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
	Action("avg", func() {
		Description("Returns avg of amounts within events")
		Payload(PageviewOptionsPayload)
		Routing(POST("/actions/:action/avg"))
		Params(func() {
			Param("action", String, "Identification of pageview action", func() {
				Enum("timespent")
			})
		})
		Response(BadRequest, func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Response(OK, func() {
			Media(CollectionOf(Avg, func() {
				View("default")
			}))
		})
	})
	Action("unique", func() {
		Description("Returns unique count of amounts within events")
		Payload(PageviewOptionsPayload)
		Routing(POST("/actions/:action/unique/:item"))
		Params(func() {
			Param("action", String, "Identification of pageview action", func() {
				Enum("load")
			})
			Param("item", String, "Identification of queried unique items", func() {
				Enum("browsers")
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

var _ = Resource("concurrents", func() {
	Description("Show recent concurrent connections")
	BasePath("/journal/concurrents")
	NoSecurity()

	Action("count", func() {
		Description("Returns recent concurrent connections identified by browser id")
		Payload(ConcurrentsOptionsPayload)
		Routing(POST("/count"))
		Response(OK, func() {
			Media(CollectionOf(Count, func() {
				View("default")
			}))
		})
	})
})
