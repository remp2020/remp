package design

import (
	. "goa.design/goa/v3/dsl"
)

var RuleOverrides = Type("RuleOverrides", func() {
	Description("Additional parameters to override all rules configuration")

	Attribute("fields", MapOf(String, String), "Field values")
})

var SegmentRuleCache = Type("SegmentRuleCache", func() {
	Description("Internal cache object with count of event")

	Attribute("s", String, "Date of last sync with DB.", func() {
		Format(FormatDateTime)
	})
	Attribute("c", Int, "Count of events occurred within timespan of segment rule.")

	Required("s", "c")
})

var ListEventOptionsPayload = Type("ListEventOptionsPayload", func() {
	Description("Parameters to filter events list")

	Attribute("select_fields", ArrayOf(String), "List of fields to select")
	Attribute("conditions", EventOptionsPayload, "Condition definition")

	Required("conditions")
})

var EventOptionsPayload = Type("EventOptionsPayload", func() {
	Description("Parameters to filter event counts")

	Attribute("filter_by", ArrayOf(EventOptionsFilterBy), "Selection of data filtering type")
	Attribute("group_by", ArrayOf(String), "Select tags by which should be data grouped")
	Attribute("time_after", String, "Include all pageviews that happened after specified RFC3339 datetime", func() {
		Format(FormatDateTime)
	})
	Attribute("time_before", String, "Include all pageviews that happened before specified RFC3339 datetime", func() {
		Format(FormatDateTime)
	})
	Attribute("time_histogram", OptionsTimeHistogram, "Attribute containing values for splitting result into buckets")

	Attribute("action", String, "Event action")
	Attribute("category", String, "Event category")
})

var OptionsTimeHistogram = Type("OptionsTimeHistogram", func() {
	Description("Values used to split results in time buckets")

	Attribute("interval", String, "Interval of buckets")
	Attribute("time_zone", String, "Timezone ID as specified in the IANA timezone database, such as`America/Los_Angeles`")

	Required("interval")
})

var OptionsCountHistogram = Type("OptionsCountHistogram", func() {
	Description("Values used to split results in count buckets")

	Attribute("field", String, "Name of the field for aggregation")
	Attribute("interval", Float64, "Interval of buckets")

	Required("field", "interval")
})

var EventOptionsFilterBy = Type("EventOptionsFilterBy", func() {
	Description("Tags and values used to filter results")

	Attribute("tag", String, "Tag used to filter results")
	Attribute("values", ArrayOf(String), "Values of TAG used to filter result")
	Attribute("inverse", Boolean, "If true, condition will be inversed")

	Required("tag", "values")
})

var ListPageviewOptionsPayload = Type("ListPageviewOptionsPayload", func() {
	Description("Parameters to filter pageview list")

	Attribute("select_fields", ArrayOf(String), "List of fields to select")
	Attribute("load_timespent", Boolean, "If true, load timespent for each pageview", func() {
		Default(false)
	})
	Attribute("load_progress", Boolean, "If true, load page and article progress for each pageview", func() {
		Default(false)
	})
	Attribute("conditions", PageviewOptionsPayload, "Condition definition")

	Required("conditions")
})

var PageviewOptionsPayload = Type("PageviewOptionsPayload", func() {
	Description("Parameters to filter pageview counts")

	Attribute("filter_by", ArrayOf(PageviewOptionsFilterBy), "Selection of data filtering type")
	Attribute("group_by", ArrayOf(String), "Select tags by which should be data grouped")
	Attribute("time_after", String, "Include all pageviews that happened after specified RFC3339 datetime", func() {
		Format(FormatDateTime)
	})
	Attribute("time_before", String, "Include all pageviews that happened before specified RFC3339 datetime", func() {
		Format(FormatDateTime)
	})
	Attribute("time_histogram", OptionsTimeHistogram, "Attribute containing values for splitting result into time-based buckets")
	Attribute("count_histogram", OptionsCountHistogram, "Attribute containing values for splitting result into count-based buckets based on provided Field")

	Attribute("action", String, "Identification of pageview action", func() {
		Enum("load", "progress", "timespent")
	})
	Attribute("item", String, "Identification of queried unique items", func() {
		Enum("browsers")
	})
})

var PageviewOptionsFilterBy = Type("PageviewOptionsFilterBy", func() {
	Description("Tags and values used to filter results")

	Attribute("tag", String, "Tag used to filter results (use tag name: user_id, article_id, ...)")
	Attribute("values", ArrayOf(String), "Values of TAG used to filter result")
	Attribute("inverse", Boolean, "If true, condition will be inversed")

	Required("tag", "values")
})

var ConcurrentsOptionsPayload = Type("ConcurrentsOptionsPayload", func() {
	Description("Parameters to filter concurrent views")

	Attribute("time_after", String, "Include all pageviews that happened after specified RFC3339 datetime", func() {
		Format(FormatDateTime)
	})
	Attribute("time_before", String, "Include all pageviews that happened before specified RFC3339 datetime", func() {
		Format(FormatDateTime)
	})
	Attribute("filter_by", ArrayOf(PageviewOptionsFilterBy), "Selection of data filtering type")
	Attribute("group_by", ArrayOf(String), "Select tags by which should be data grouped")
})

var ListCommerceOptionsPayload = Type("ListCommerceOptionsPayload", func() {
	Description("Parameters to filter pageview list")

	Attribute("select_fields", ArrayOf(String), "List of fields to select")
	Attribute("conditions", CommerceOptionsPayload, "Condition definition")

	Required("conditions")
})

var CommerceOptionsPayload = Type("CommerceOptionsPayload", func() {
	Description("Parameters to filter commerce counts")

	Attribute("filter_by", ArrayOf(CommerceOptionsFilterBy), "Selection of data filtering type")
	Attribute("group_by", ArrayOf(String), "Select tags by which should be data grouped")
	Attribute("time_after", String, "Include all pageviews that happened after specified RFC3339 datetime", func() {
		Format(FormatDateTime)
	})
	Attribute("time_before", String, "Include all pageviews that happened before specified RFC3339 datetime", func() {
		Format(FormatDateTime)
	})
	Attribute("time_histogram", OptionsTimeHistogram, "Attribute containing values for splitting result into time-based buckets")
	Attribute("step", String, "Filter particular step", func() {
		Enum("checkout", "payment", "purchase", "refund")
	})
})

var CommerceOptionsFilterBy = Type("CommerceOptionsFilterBy", func() {
	Description("Tags and values used to filter results")

	Attribute("tag", String, "Tag used to filter results")
	Attribute("values", ArrayOf(String), "Values of TAG used to filter result")
	Attribute("inverse", Boolean, "If true, condition will be inversed")

	Required("tag", "values")
})

var SegmentPayload = Type("SegmentPayload", func() {
	Description("Request parameters for segment creation")

	Attribute("name", String, "Name of segment")
	Attribute("table_name", String, "Name of table above which this segment is calculated")
	Attribute("group_id", Int, "ID of parent group")
	Attribute("fields", ArrayOf(String), "List of fields to select")

	Attribute("criteria", SegmentCreateCriteria, "Segment's criteria")
	Attribute("id", Int, "Segment ID")

	Required("name", "table_name", "group_id", "fields", "criteria")
})

var SegmentTinyPayload = Type("SegmentTinyPayload", func() {
	Description("Request parameters for endpoints segments/count and segments/related")

	Attribute("table_name", String, "Name of table above which this segment is calculated")
	Attribute("criteria", SegmentCreateCriteria, "Segment's criteria")
})

var SegmentCreateCriteria = Type("SegmentCreateCriteria", func() {
	Description("Segment's criteria")

	Attribute("nodes", ArrayOf(SegmentCreateCriteriaOperator), "Criteria operators")
	Attribute("version", String, "Version of criteria format")

	Required("nodes", "version")
})

var SegmentCreateCriteriaOperator = Type("SegmentCreateCriteriaOperator", func() {
	Description("Single operator node of Segment's criteria")

	Attribute("type", String, "Type of criterion", func() {
		Enum("operator")
	})
	Attribute("operator", String, "Operator for following criteria nodes", func() {
		Enum("AND", "OR")
	})
	Attribute("nodes", ArrayOf(SegmentCreateCriteriaNode), "Criteria nodes")

	Required("type", "operator", "nodes")
})

var SegmentCreateCriteriaNode = Type("SegmentCreateCriteriaOperatorNode", func() {
	Description("Single node of Segment's criteria")

	Attribute("type", String, "Type of criterion", func() {
		// TODO: add posibility to have "operator" on second (and lower) level of nodes
		Enum("criteria")
	})
	Attribute("key", String, "Key of criterion's type")
	Attribute("negation", Boolean, "Use true if this criterion should be negated")
	Attribute("values", Any)
})
