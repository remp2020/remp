package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

var EventCount = MediaType("application/vnd.event.count+json", func() {
	Description("EventCount")
	Attributes(func() {
		Attribute("count", Integer)
	})
	View("default", func() {
		Attribute("count")
	})
	Required("count")
})

var EventList = MediaType("application/vnd.event.list+json", func() {
	Description("EventList")
	Attributes(func() {
		Attribute("dummy")
	})
	View("default", func() {
		Attribute("dummy")
	})
})
