package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

var RuleOverrides = Type("RuleOverrides", func() {
	Description("Additional parameters to override all rules configuration")

	Attribute("fields", HashOf(String, String), "Field values")
})

var SegmentRuleCache = Type("SegmentRuleCache", func() {
	Description("Internal cache object with count of event")

	Param("s", DateTime, "Date of last sync with DB.")
	Param("c", Integer, "Count of events occurred within timespan of segment rule.")

	Required("s", "c")
})

var EventOptionsPayload = Type("EventOptionsPayload", func() {
	Description("Parameters to filter event counts")

	Attribute("filter_by", ArrayOf(EventOptionsFilterBy), "Selection of data filtering type")
	Attribute("group_by", ArrayOf(String), "Select tags by which should be data grouped")
	Attribute("time_after", DateTime, "Include all pageviews that happened after specified RFC3339 datetime")
	Attribute("time_before", DateTime, "Include all pageviews that happened before specified RFC3339 datetime")
})

var EventOptionsFilterBy = Type("EventOptionsFilterBy", func() {
	Description("Tags and values used to filter results")

	Attribute("tag", String, "Tag used to filter results")
	Attribute("values", ArrayOf(String), "Values of TAG used to filter result")

	Required("tag", "values")
})

var ListPageviewOptionsPayload = Type("ListPageviewOptionsPayload", func() {
	Description("Parameters to filter pageview list")

	Attribute("select_fields", ArrayOf(String), "List of fields to select")
	Attribute("conditions", PageviewOptionsPayload, "Condition definition")

	Required("conditions")
})

var PageviewOptionsPayload = Type("PageviewOptionsPayload", func() {
	Description("Parameters to filter pageview counts")

	Attribute("filter_by", ArrayOf(PageviewOptionsFilterBy), "Selection of data filtering type")
	Attribute("group_by", ArrayOf(String), "Select tags by which should be data grouped")
	Attribute("time_after", DateTime, "Include all pageviews that happened after specified RFC3339 datetime")
	Attribute("time_before", DateTime, "Include all pageviews that happened before specified RFC3339 datetime")
})

var PageviewOptionsFilterBy = Type("PageviewOptionsFilterBy", func() {
	Description("Tags and values used to filter results")

	Attribute("tag", String, "Tag used to filter results (use tag name: user_id, article_id, ...)")
	Attribute("values", ArrayOf(String), "Values of TAG used to filter result")

	Required("tag", "values")
})

var CommerceOptionsPayload = Type("CommerceOptionsPayload", func() {
	Description("Parameters to filter commerce counts")

	Attribute("filter_by", ArrayOf(CommerceOptionsFilterBy), "Selection of data filtering type")
	Attribute("group_by", ArrayOf(String), "Select tags by which should be data grouped")
	Attribute("time_after", DateTime, "Include all pageviews that happened after specified RFC3339 datetime")
	Attribute("time_before", DateTime, "Include all pageviews that happened before specified RFC3339 datetime")
})

var CommerceOptionsFilterBy = Type("CommerceOptionsFilterBy", func() {
	Description("Tags and values used to filter results")

	Attribute("tag", String, "Tag used to filter results", func() {
		Enum("user_id", "article_id", "author_id")
	})
	Attribute("values", ArrayOf(String), "Values of TAG used to filter result")

	Required("tag", "values")
})
