package design

import (
	. "goa.design/goa/v3/dsl"
)

var EventCount = ResultType("application/vnd.event.count+json", func() {
	Description("Count")
	Attributes(func() {
		Attribute("count", Int)
	})
	Required("count")
})

var Count = ResultType("application/vnd.count+json", func() {
	Description("Count")
	Attributes(func() {
		Attribute("tags", MapOf(String, String))
		Attribute("count", Int)
		Attribute("time_histogram", CollectionOf(TimeHistogram))
		Attribute("count_histogram", CollectionOf(CountHistogram))
	})
	Required("tags", "count")
})

var Sum = ResultType("application/vnd.sum+json", func() {
	Description("Sum")
	Attributes(func() {
		Attribute("tags", MapOf(String, String))
		Attribute("sum", Float64)
		Attribute("time_histogram", CollectionOf(TimeHistogram))
	})
	Required("tags", "sum")
})

var Avg = ResultType("application/vnd.avg+json", func() {
	Description("Avg")
	Attributes(func() {
		Attribute("tags", MapOf(String, String))
		Attribute("avg", Float64)
		Attribute("time_histogram", CollectionOf(TimeHistogram))
	})
	Required("tags", "avg")
})

var Pageviews = ResultType("application/vnd.pageviews+json", func() {
	Description("Pageviews")
	Attributes(func() {
		Attribute("tags", MapOf(String, String))
		Attribute("pageviews", CollectionOf(Pageview))
	})
	Required("tags", "pageviews")
})

var Events = ResultType("application/vnd.events+json", func() {
	Description("Events")
	Attributes(func() {
		Attribute("tags", MapOf(String, String))
		Attribute("events", CollectionOf(Event))
	})
	Required("tags", "events")
})

var Commerces = ResultType("application/vnd.commerces+json", func() {
	Description("Commerce events")
	Attributes(func() {
		Attribute("tags", MapOf(String, String))
		Attribute("commerces", CollectionOf(Commerce))
	})
	Required("tags", "commerces")
})

var Segment = ResultType("application/vnd.segment+json", func() {
	Description("Segment")
	Attributes(func() {
		Attribute("id", Int, "ID of segment")
		Attribute("code", String, "Code-friendly identificator of segment")
		Attribute("name", String, "User-friendly name of segment")
		Attribute("group", SegmentGroup)
		Attribute("criteria", Any, "Criteria used to build segment")
		Attribute("url", String, "URL to segment")

		Attribute("table_name", String)
		Attribute("fields", ArrayOf(String))
		Attribute("group_id", Int)
	})
	View("default", func() {
		Attribute("id")
		Attribute("code")
		Attribute("name")
		Attribute("group")
		Attribute("criteria")
	})
	View("tiny", func() {
		Attribute("id")
		Attribute("code")
		Attribute("name")
		Attribute("group")
	})
	View("extended", func() {
		Attribute("id")
		Attribute("code")
		Attribute("name")
		Attribute("group")
		Attribute("url")
	})
	View("segmenter", func() {
		Attribute("id")
		Attribute("code")
		Attribute("name")
		Attribute("table_name")
		Attribute("group")

		Attribute("criteria")
		Attribute("fields")
		Attribute("group_id")
	})
	Required("id", "code", "name", "group")
})

var RelatedSegments = ResultType("application/vnd.segments.related+json", func() {
	Description("Related segments")
	Attributes(func() {
		Attribute("segments", CollectionOf(Segment))
	})
	View("default", func() {
		Attribute("segments", func() {
			View("extended")
		})
	})
	Required("segments")
})

var SegmentersSegment = ResultType("application/vnd.segmenters.segment.+json", func() {
	Description("Segment returned for segmenter")
	Attributes(func() {
		Attribute("status", String)
		Attribute("segment", Segment)
	})
	View("default", func() {
		Attribute("status")
		Attribute("segment", func() {
			View("segmenter")
		})
	})
	Required("status", "segment")
})

var SegmentCheck = ResultType("application/vnd.segment.check+json", func() {
	Description("Segment check")
	Attributes(func() {
		Attribute("check", Boolean, "Flag whether user is in the segment or not")
		Attribute("cache", MapOf(Int, SegmentRuleCache), "Cache object for third party (remplib.js) to use indexed by SegmentRule-based key")
		Attribute("event_rules", MapOf(String, ArrayOf(Int)), "Map of which rules should be incremented for selected events.")
		Attribute("overridable_fields", MapOf(Int, ArrayOf(String)), "Array of overridable fields belonging to rules.")
		Attribute("flags", MapOf(Int, MapOf(String, String)), "Array of flags belonging to rules.")
	})
	Required("check", "cache", "event_rules", "overridable_fields", "flags")
})

var SegmentGroup = ResultType("application/vnd.segment.group+json", func() {
	Description("Segment group")
	Attributes(func() {
		Attribute("id", Int, "Internal ID of segment group")
		Attribute("name", String, "User-friendly name of segment group")
		Attribute("sorting", Int, "Sort order index")
	})
	Required("id", "name", "sorting")
})

var SegmentBlueprint = ResultType("application/vnd.segment.blueprint+json", func() {
	Description("Segment blueprint")
	Attributes(func() {
		Attribute("blueprint", CollectionOf(SegmentBlueprintTable))
	})
	Required("blueprint")
})

var SegmentBlueprintTable = ResultType("application/vnd.segment.blueprint.table+json", func() {
	Description("Blueprint of one table available for segment")
	Attributes(func() {
		Attribute("table", String, "Table name")
		Attribute("fields", ArrayOf(String), "Fields of table")
		Attribute("criteria", CollectionOf(SegmentBlueprintTableCriterion), "Processing criteria for fields of table")
	})
	Required("table", "fields", "criteria")
})

var SegmentBlueprintTableCriterion = ResultType("application/vnd.segment.blueprint.table.criterion+json", func() {
	Description("Criterion for one field of table available for segment")
	Attributes(func() {
		Attribute("key", String, "Field of table to which is this criterion related")
		Attribute("label", String, "Human readable name of field")
		Attribute("params", Any, "Criteria of field parameters")
		Attribute("fields", ArrayOf(String), "Field parameters")
	})
	Required("key", "label", "params")
})

var SegmentCount = ResultType("application/vnd.segment.count+json", func() {
	Description("Segment count")
	Attributes(func() {
		Attribute("count", Int, "Number of users in segment based on provided criteria")
		Attribute("status", String, "Status of count. If everything is fine, returns `ok`.")
	})
	Required("count", "status")
})

var Event = ResultType("application/vnd.event+json", func() {
	Description("Generic event")
	Attributes(func() {
		Attribute("id", String)
		Attribute("category", String)
		Attribute("action", String)
		Attribute("system", System)
		Attribute("user", User)
		Attribute("article_id", String)

		Attribute("rtm_source", String)
		Attribute("rtm_campaign", String)
		Attribute("rtm_medium", String)
		Attribute("rtm_content", String)

		Attribute("utm_source", String)
		Attribute("utm_campaign", String)
		Attribute("utm_medium", String)
		Attribute("utm_content", String)
	})
	Required("id", "system", "category", "action")
})

var TimeHistogram = ResultType("application/vnd.time.histogram+json", func() {
	Description("Time histogram data")
	Attributes(func() {
		Attribute("time", String, func() {
			Format(FormatDateTime)
		})
		Attribute("value", Float64)
	})
	Required("time", "value")
})

var CountHistogram = ResultType("application/vnd.count.histogram+json", func() {
	Description("Count histogram data")
	Attributes(func() {
		Attribute("bucket_key", Float64)
		Attribute("value", Int)
	})
	Required("bucket_key", "value")
})

var Commerce = ResultType("application/vnd.commerce+json", func() {
	Description("Commerce event")
	Attributes(func() {
		Attribute("id", String)
		Attribute("step", String, func() {
			Enum("checkout", "payment", "purchase", "refund")
		})
		Attribute("checkout", CommerceCheckout)
		Attribute("payment", CommercePayment)
		Attribute("purchase", CommercePayment)
		Attribute("refund", CommercePayment)

		Attribute("source", Source, "RTM source metadata")
		Attribute("article", Article)
		Attribute("system", System)
		Attribute("user", User)
	})
	Required("id", "step", "system", "user")
})

var Pageview = ResultType("application/vnd.pageview+json", func() {
	Description("Pageview event")
	Attributes(func() {
		Attribute("id", String)
		Attribute("system", System)
		Attribute("user", User)
		Attribute("article", Article)
	})
	Required("system")
})

var User = ResultType("application/vnd.user+json", func() {
	Attributes(func() {
		Attribute("id", String, "ID of reader")
		Attribute("subscriber", Boolean, "Flag whether user is subscriber (has paid for access)")
		Attribute("browser_id", String, "Anonymized ID of user's browser")
		Attribute("url", String, "URL of the content/conversion point", func() {
			Format("uri")
		})
		Attribute("canonical_url", String, "Canonical URL of the content/conversion point", func() {
			Format("uri")
		})
		Attribute("user_agent", String, "User agent of client")
		Attribute("ip_address", String, "IP address of client", func() {
			Format("ip")
		})
		Attribute("source", Source, "RTM source metadata")
		Attribute("remp_session_id", String, "ID of reader's session")
		Attribute("remp_pageview_id", String, "ID of pageview")
		Attribute("referer", String, "Value of HTTP referer header (if present)")
		Attribute("derived_referer_medium", String, "Medium which trigerred the pageview (e.g. internal, search, ...)")
		Attribute("derived_referer_source", String, "Specific source of medium which trigerred the pageview (e.g. Google, Gmail, ...)")
		Attribute("derived_referer_host_with_path", String, "Referer without query parameters (only host and path)")
		Attribute("timespent", Int, "Number of seconds spent during pageview (if recorded)")
		Attribute("page_progress", Float32, "Page reading progress (0.0-1.0, if recorded)")
		Attribute("article_progress", Float32, "Article reading progress (0.0-1.0, if recorded)")
	})
	Required("remp_pageview_id")
})

var System = ResultType("application/vnd.system+json", func() {
	Attributes(func() {
		Attribute("property_token", String, "Property token")
		Attribute("time", String, "Time of occurrence", func() {
			Format(FormatDateTime)
		})
	})
	Required("property_token", "time")
})

var Article = ResultType("application/vnd.article+json", func() {
	Attributes(func() {
		Attribute("id", String, "ID of article")
		Attribute("category", String, "Page category (homepage, world news...")
		Attribute("tags", ArrayOf(String), "List of tags (breaking news, trump...")
		Attribute("author_id", String, "ID of author")
		Attribute("content_type", String, "Type of the content, e.g. 'article'")
		Attribute("locked", Boolean, "Flag whether content was locked for the visitor")
		Attribute("variants", MapOf(String, String), "Hash of key-value pairs bearing A/B test variant information (what's A/B-tested / variant label)")
	})
	Required("id")
})

var Source = ResultType("application/vnd.source+json", func() {
	Attributes(func() {
		Attribute("rtm_source", String, "Origin of user (e.g. remp_campaign)")
		Attribute("rtm_medium", String, "Medium through which the came (e.g. overlay, inline)")
		Attribute("rtm_campaign", String, "Reference to specific campaign (e.g. campaign ID")
		Attribute("rtm_content", String, "Reference to specific campaign mean (e.g. banner ID)")

		Attribute("utm_source", String, "[DEPRECATED] Origin of user (e.g. remp_campaign). Deprecated, please use rtm_source.")
		Attribute("utm_medium", String, "[DEPRECATED] Medium through which the came (e.g. overlay, inline). Deprecated, please use rtm_medium.")
		Attribute("utm_campaign", String, "[DEPRECATED] Reference to specific campaign (e.g. campaign ID). Deprecated, please use rtm_campaign.")
		Attribute("utm_content", String, "[DEPRECATED] Reference to specific campaign mean (e.g. banner ID). Deprecated, please use rtm_content.")
	})
})

var CommerceCheckout = ResultType("application/vnd.commerce.checkout+json", func() {
	Attributes(func() {
		Attribute("funnel_id", String, "ID of funnel user is being routed trough")
	})
	Required("funnel_id")
})

var CommercePayment = ResultType("application/vnd.commerce.payment+json", func() {

	Attributes(func() {
		Attribute("funnel_id", String, "ID of funnel user is being routed trough")
		Attribute("transaction_id", String, "Public ID of transaction (variable symbol)")
		Attribute("product_ids", ArrayOf(String), "Public IDs of selected products")
		Attribute("revenue", Revenue, "Amount of money for given payment")
	})
	Required("funnel_id", "revenue", "transaction_id", "product_ids")
})

var Revenue = ResultType("application/vnd.revenue+json", func() {
	Attributes(func() {
		Attribute("amount", Float64, "Numeric amount of money")
		Attribute("currency", String, "ISO 4217 representation of currency")
	})
	Required("amount", "currency")
})

var Flags = ResultType("application/vnd.flags+json", func() {
	Attributes(func() {
		Attribute("pageviews", ArrayOf(String), "Pageviews category flags")
		Attribute("commerce", ArrayOf(String), "Commerce category flags")
		Attribute("events", ArrayOf(String), "Events category flags")
	})
	Required("pageviews", "commerce", "events")
})

var SegmentGroupsFallback = ResultType("application/vnd.segment.groups.fallback", func() {
	Attributes(func() {
		Attribute("status", String, "OK flag to check before reading the data")
		Attribute("groups", CollectionOf(SegmentGroup))
	})
	Required("status", "groups")
})
