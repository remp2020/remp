package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

var Count = MediaType("application/vnd.count+json", func() {
	Description("Count")
	Attributes(func() {
		Attribute("count", Integer)
	})
	View("default", func() {
		Attribute("count")
	})
	Required("count")
})

var GrouppedCounts = MediaType("application/vnd.groupped.counts+json", func() {
	Description("Groupped counts")
	Attributes(func() {
		Attribute("counts", HashOf(String, Integer))
	})
	View("default", func() {
		Attribute("counts")
	})
	Required("counts")
})

var GrouppedSums = MediaType("application/vnd.groupped.sums+json", func() {
	Description("Groupped sums")
	Attributes(func() {
		Attribute("sums", HashOf(String, Number))
	})
	View("default", func() {
		Attribute("sums")
	})
	Required("sums")
})

var CommerceList = MediaType("application/vnd.commerce.list+json", func() {
	Description("CommerceList")
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

var Event = MediaType("application/vnd.event+json", func() {
	Description("Generic event")
	Attributes(func() {
		Attribute("category", String)
		Attribute("action", String)
		Attribute("time", DateTime)
		Attribute("host", String)
		Attribute("ip", String)
		Attribute("token", String)
		Attribute("user_id", String)
		Attribute("url", String)
		Attribute("user_agent", String)
	})
	View("default", func() {
		Attribute("category")
		Attribute("action")
		Attribute("time")
		Attribute("host")
		Attribute("ip")
		Attribute("token")
		Attribute("user_id")
		Attribute("url")
		Attribute("user_agent")
	})
	Required("category", "action", "time", "host", "ip", "token", "user_id", "url", "user_agent")
})
