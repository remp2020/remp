package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

var SegmentPayload = Type("SegmentPayload", func() {
	Attribute("name", String)
	Attribute("rules", ArrayOf(SegmentRule))

	Required("name", "rules")
})

var SegmentRule = Type("SegmentRule", func() {
	Attribute("count", Integer)
	Attribute("event")
	Attribute("category")
	Attribute("time_after", DateTime)
	Attribute("time_before", DateTime)

	Required("count", "event", "category")
})
