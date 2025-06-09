package design

import (
	. "goa.design/goa/v3/dsl"
)

var _ = Service("swagger", func() {
	Files("/swagger.json", "gen/http/openapi.json")
	Files("/openapi3.json", "gen/http/openapi3.json")
})

var _ = Service("track", func() {
	Description("Track different types of events")

	HTTP(func() {
		Path("/track")
	})

	Method("pageview", func() {
		Description("Track new pageview")
		Payload(Pageview)
		HTTP(func() {
			POST("/pageview")
			Response(StatusAccepted)
			Response("bad_request", StatusBadRequest)
			Response("not_found", StatusNotFound)
		})
		Error("bad_request", func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Error("not_found", func() {
			Description("Returned when property_token was not found")
		})
	})

	Method("commerce", func() {
		Description("Track new pageview")
		Payload(Commerce)
		HTTP(func() {
			POST("/commerce")
			Response(StatusAccepted)
		})
		Error("bad_request", func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Error("not_found", func() {
			Description("Returned when property_token was not found")
		})
	})

	Method("event", func() {
		Description("Track generic event")
		Payload(Event)
		HTTP(func() {
			POST("/event")
			Response(StatusAccepted)
		})
		Error("bad_request", func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Error("not_found", func() {
			Description("Returned when property_token was not found")
		})
	})

	Method("entity", func() {
		Description("Track generic entity")
		Payload(Entity)
		HTTP(func() {
			POST("/entity")
			Response(StatusAccepted)
		})
		Error("bad_request", func() {
			Description("Returned when request does not comply with Swagger specification or entity data does not pass validation")
		})
		Error("not_found", func() {
			Description("Returned when property_token was not found")
		})
	})

	Method("impressions", func() {
		Description("Track impressions")
		Payload(Impressions)
		HTTP(func() {
			POST("/impressions")
			Response(StatusAccepted)
		})
		Error("bad_request", func() {
			Description("Returned when request does not comply with Swagger specification")
		})
		Error("not_found", func() {
			Description("Returned when property_token was not found")
		})
	})
})
