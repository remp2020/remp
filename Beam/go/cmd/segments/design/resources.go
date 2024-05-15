package design

import (
	. "goa.design/goa/v3/dsl"
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
		"rtm_campaign": "custom-campaign-id",
		// ...
	}`
)

var _ = Service("swagger", func() {
	Files("/swagger.json", "gen/http/openapi.json")
	Files("/openapi3.json", "gen/http/openapi3.json")
})

var _ = Service("segments", func() {
	Description("Segment operations")

	HTTP(func() {
		Path("/segments")
	})

	Method("get", func() {
		Description("Get segment")
		Payload(func() {
			Attribute("id", Int, "Segment ID")
			Required("id")
		})
		Result(SegmentersSegment)
		Error("not_found")
		Error("bad_request")
		HTTP(func() {
			GET("/show")
			Params(func() {
				Param("id")
			})
			Response("not_found", StatusNotFound)
			Response("bad_request", StatusBadRequest)
			Response(StatusOK)
		})
	})

	Method("list", func() {
		Description("List all segments.")
		Error("not_found")
		Error("bad_request")
		Result(CollectionOf(Segment), func() {
			View("default")
			View("tiny")
			View("extended")
			View("segmenter")
		})
		HTTP(func() {
			GET("/")
			Response("not_found", StatusNotFound)
			Response("bad_request", StatusBadRequest)
			Response(StatusOK)
		})
	})
	Method("groups", func() {
		Description("List all segment groups.")
		Result(SegmentGroupsFallback)
		HTTP(func() {
			GET("/groups")
			Response(StatusOK)
		})
	})

	Method("check_user", func() {
		Description("Check whether given user ID belongs to segment.")
		Payload(func() {
			Attribute("segment_code", String, "Segment code", func() {
				Pattern(SegmentPattern)
			})
			Attribute("user_id", String, "User ID", func() {
				Pattern(UserPattern)
			})
			Attribute("fields", String, FieldsParamDescription)
			Attribute("cache", String, CacheParamDescription)
		})
		Error("not_found")
		Error("bad_request")
		Result(SegmentCheck)
		HTTP(func() {
			GET("/{segment_code}/users/check/{user_id}")
			Params(func() {
				Param("fields")
				Param("cache")
			})
			Response("not_found", StatusNotFound)
			Response("bad_request", StatusBadRequest)
			Response(StatusOK)
		})
	})

	Method("check_browser", func() {
		Description("Check whether given browser ID belongs to segment.")
		Error("not_found")
		Error("bad_request")
		Payload(func() {
			Attribute("segment_code", String, "Segment code", func() {
				Pattern(SegmentPattern)
			})
			Attribute("browser_id", String, "Browser ID", func() {
				Pattern(UserPattern)
			})
			Attribute("fields", String, FieldsParamDescription)
			Attribute("cache", String, CacheParamDescription)
		})
		Result(SegmentCheck)
		HTTP(func() {
			GET("/{segment_code}/browsers/check/{browser_id}")
			Params(func() {
				Param("fields")
				Param("cache")
			})
			Response("not_found", StatusNotFound)
			Response("bad_request", StatusBadRequest)
			Response(StatusOK)
		})
	})

	Method("users", func() {
		Description("List users of segment.")
		Error("not_found")
		Error("bad_request")
		Payload(func() {
			Attribute("segment_code", String, "Segment code", func() {
				Pattern(SegmentPattern)
			})
			Attribute("fields", String, FieldsParamDescription)
		})
		Result(ArrayOf(String))
		HTTP(func() {
			GET("/{segment_code}/users")
			Params(func() {
				Param("fields")
			})
			Response("not_found", StatusNotFound)
			Response("bad_request", StatusBadRequest)
			Response(StatusOK)
		})
	})

	Method("criteria", func() {
		Description("Provide segment blueprint with criteria for individual tables and fields")
		Result(SegmentBlueprint)
		HTTP(func() {
			GET("/criteria")
			Response(StatusOK)
		})
	})

	Method("create_or_update", func() {
		Description("Create or update segment (for update, use GET parameter ?id={segment_id})")
		Payload(SegmentPayload)
		Result(Segment)
		Error("bad_request")
		Error("not_found")
		HTTP(func() {
			POST("/detail")
			Params(func() {
				Param("id")
			})
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response("not_found", StatusNotFound, func() {
				Description("Returned when segment with provided ID doesn't exist")
			})
			Response(StatusOK)
		})
	})

	Method("count", func() {
		Description("Returns number of users in segment based on provided criteria")
		Payload(SegmentTinyPayload)
		Error("bad_request")
		Result(SegmentCount)
		HTTP(func() {
			POST("/count")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("related", func() {
		Description("Returns segments with same or similar criteria")
		Payload(SegmentTinyPayload)
		Error("bad_request")
		Result(RelatedSegments)
		HTTP(func() {
			POST("/related")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})
})

var _ = Service("journal", func() {
	Description("Common journal calls")

	HTTP(func() {
		Path("/journal")
	})

	Method("flags", func() {
		Description("List of all available flags")
		Result(Flags)
		HTTP(func() {
			GET("/flags")
			Response(StatusOK)
		})
	})
})

var _ = Service("events", func() {
	Description("Events journal")

	HTTP(func() {
		Path("/journal/events")
	})

	Method("count_action", func() {
		Description("Returns counts of events for given action and category")
		Payload(EventOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Count))
		HTTP(func() {
			POST("/categories/{category}/actions/{action}/count")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("count", func() {
		Description("Returns counts of events")
		Payload(EventOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Count))
		HTTP(func() {
			POST("/count")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})
	Method("list", func() {
		Description("Returns full list of events")
		Payload(ListEventOptionsPayload)
		Result(CollectionOf(Events))
		HTTP(func() {
			POST("/list")
			Response(StatusOK)
		})
	})
	Method("categories", func() {
		Description("List of all tracked categories")
		Result(ArrayOf(String))
		HTTP(func() {
			GET("/categories")
			Response(StatusOK)
		})
	})

	Method("actions", func() {
		Description("List of all tracked actions for given category")
		Payload(func() {
			Attribute("category", String, "Category under which the actions were tracked")
		})
		Result(ArrayOf(String))
		HTTP(func() {
			GET("/categories/{category}/actions")
			Response(StatusOK)
		})
	})

	Method("users", func() {
		Description("List of all tracked users")
		Result(ArrayOf(String))
		HTTP(func() {
			GET("/users")
			Response(StatusOK)
		})
	})
})

var _ = Service("commerce", func() {
	Description("Commerce journal")

	HTTP(func() {
		Path("/journal/commerce")
	})

	Method("count_step", func() {
		Description("Returns counts of commerce events")
		Error("bad_request")
		Result(CollectionOf(Count))
		Payload(CommerceOptionsPayload)
		HTTP(func() {
			POST("/steps/{step}/count")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("count", func() {
		Description("Returns counts of commerce events")
		Payload(CommerceOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Count))
		HTTP(func() {
			POST("/count")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("sum_step", func() {
		Description("Returns sum of amounts within events")
		Payload(CommerceOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Sum))
		HTTP(func() {
			POST("/steps/{step}/sum")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("sum", func() {
		Description("Returns sum of amounts within events")
		Payload(CommerceOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Sum))
		HTTP(func() {
			POST("/sum")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("list", func() {
		Description("Returns full list of events")
		Payload(ListCommerceOptionsPayload)
		Result(CollectionOf(Commerces))
		HTTP(func() {
			POST("/list")
			Response(StatusOK)
		})
	})

	Method("categories", func() {
		Description("List of all available categories")
		Result(ArrayOf(String))
		HTTP(func() {
			GET("/categories")
			Response(StatusOK)
		})
	})

	Method("actions", func() {
		Description("List of all available actions for given category")
		Result(ArrayOf(String))
		Payload(func() {
			Attribute("category", String, "Category (step) under which the actions were tracked")
		})
		HTTP(func() {
			GET("/categories/{category}/actions")
			Response(StatusOK)
		})
	})
})

var _ = Service("pageviews", func() {
	Description("Pageviews journal")

	HTTP(func() {
		Path("/journal/pageviews")
	})

	Method("count", func() {
		Description("Returns counts of pageviews")
		Payload(PageviewOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Count))
		HTTP(func() {
			POST("/actions/{action}/count")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("sum", func() {
		Description("Returns sum of amounts within events")
		Payload(PageviewOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Sum))
		HTTP(func() {
			POST("/actions/{action}/sum")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("avg", func() {
		Description("Returns avg of amounts within events")
		Payload(PageviewOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Avg))
		HTTP(func() {
			POST("/actions/{action}/avg")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("unique", func() {
		Description("Returns unique count of amounts within events")
		Payload(PageviewOptionsPayload)
		Error("bad_request")
		Result(CollectionOf(Count))
		HTTP(func() {
			POST("/actions/{action}/unique/{item}")
			Response("bad_request", StatusBadRequest, func() {
				Description("Returned when request does not comply with Swagger specification")
			})
			Response(StatusOK)
		})
	})

	Method("list", func() {
		Description("Returns full list of pageviews")
		Payload(ListPageviewOptionsPayload)
		Result(CollectionOf(Pageviews))
		HTTP(func() {
			POST("/list")
			Response(StatusOK)
		})
	})

	Method("categories", func() {
		Description("List of all available categories")
		Result(ArrayOf(String))
		HTTP(func() {
			GET("/categories")
			Response(StatusOK)
		})
	})

	Method("actions", func() {
		Description("List of all available actions for given category")
		Payload(func() {
			Attribute("category", String, "Category under which the actions were tracked")
		})
		Result(ArrayOf(String))
		HTTP(func() {
			GET("/categories/{category}/actions")
			Response(StatusOK)
		})
	})
})

var _ = Service("concurrents", func() {
	Description("Show recent concurrent connections")

	Method("count", func() {
		Description("Returns recent concurrent connections identified by browser id")
		Payload(ConcurrentsOptionsPayload)

		Result(CollectionOf(Count))

		HTTP(func() {
			POST("/count")
			Response(StatusOK)
		})
	})
	HTTP(func() {
		Path("/journal/concurrents")
	})
})
