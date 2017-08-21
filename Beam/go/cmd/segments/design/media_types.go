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

var Event = MediaType("application/vnd.event+json", func() {
	Description("Generic event")
	Attributes(func() {
		Attribute("category", String)
		Attribute("action", String)
		Attribute("system", System)
		Attribute("user", User)
	})
	View("default", func() {
		Attribute("category")
		Attribute("action")
		Attribute("system")
		Attribute("user")
	})
	Required("system", "category", "action")
})

var User = Type("User", func() {
	Attribute("id", String, "ID of reader")
	Attribute("url", String, "URL of the content/conversion point", func() {
		Format("uri")
	})
	Attribute("user_agent", String, "User agent of client")
	Attribute("ip_address", String, "IP address of client", func() {
		Format("ip")
	})
})

var System = Type("System", func() {
	Attribute("property_token", UUID, "Property token")
	Attribute("time", DateTime, "Time of occurrence")
	Required("property_token", "time")
})
