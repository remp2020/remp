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

var Segment = MediaType("application/vnd.segment+json", func() {
	Description("Segment check")
	Attributes(func() {
		Attribute("code", String, "Code-friendly identificator of segment")
		Attribute("name", String, "User-friendly name of segment")
		Attribute("group", SegmentGroup)
	})
	View("default", func() {
		Attribute("code")
		Attribute("name")
		Attribute("group")
	})
	Required("code", "name", "group")
})

var SegmentCheck = MediaType("application/vnd.segment.check+json", func() {
	Description("Segment check")
	Attributes(func() {
		Attribute("check", Boolean, "Flag whether user is in the segment or not")
	})
	View("default", func() {
		Attribute("check")
	})
	Required("check")
})

var SegmentGroup = MediaType("application/vnd.segment.group+json", func() {
	Description("Segment group")
	Attributes(func() {
		Attribute("id", Integer, "Internal ID of segment group")
		Attribute("name", String, "User-friendly name of segment group")
		Attribute("sorting", Integer, "Sort order index")
	})
	View("default", func() {
		Attribute("id")
		Attribute("name")
		Attribute("sorting")
	})
	Required("id", "name", "sorting")
})

var User = MediaType("application/vnd.user+json", func() {
	Description("User")
	Attributes(func() {
		Attribute("id", String, "Identificator of user")
		Attribute("email", String, "E-mail of user")
	})
	View("default", func() {
		Attribute("id")
		Attribute("email")
	})
	Required("id")
})
