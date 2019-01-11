package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

var EventCount = MediaType("application/vnd.event.count+json", func() {
	Description("Count")
	Attributes(func() {
		Attribute("count", Integer)
	})
	View("default", func() {
		Attribute("count")
	})
	Required("count")
})

var Count = MediaType("application/vnd.count+json", func() {
	Description("Count")
	Attributes(func() {
		Attribute("tags", HashOf(String, String))
		Attribute("count", Integer)
		Attribute("time_histogram", CollectionOf(TimeHistogram))
	})
	View("default", func() {
		Attribute("tags")
		Attribute("count")
		Attribute("time_histogram")
	})
	Required("tags", "count")
})

var Sum = MediaType("application/vnd.sum+json", func() {
	Description("Sum")
	Attributes(func() {
		Attribute("tags", HashOf(String, String))
		Attribute("sum", Number)
		Attribute("time_histogram", CollectionOf(TimeHistogram))
	})
	View("default", func() {
		Attribute("tags")
		Attribute("sum")
		Attribute("time_histogram")
	})
	Required("tags", "sum")
})

var Avg = MediaType("application/vnd.avg+json", func() {
	Description("Avg")
	Attributes(func() {
		Attribute("tags", HashOf(String, String))
		Attribute("avg", Number)
		Attribute("time_histogram", CollectionOf(TimeHistogram))
	})
	View("default", func() {
		Attribute("tags")
		Attribute("avg")
		Attribute("time_histogram")
	})
	Required("tags", "avg")
})

var Pageviews = MediaType("application/vnd.pageviews+json", func() {
	Description("Pageviews")
	Attributes(func() {
		Attribute("tags", HashOf(String, String))
		Attribute("pageviews", CollectionOf(Pageview))
	})
	View("default", func() {
		Attribute("tags")
		Attribute("pageviews")
	})
	Required("tags", "pageviews")
})

var Events = MediaType("application/vnd.events+json", func() {
	Description("Events")
	Attributes(func() {
		Attribute("tags", HashOf(String, String))
		Attribute("events", CollectionOf(Event))
	})
	View("default", func() {
		Attribute("tags")
		Attribute("events")
	})
	Required("tags", "events")
})

var Commerces = MediaType("application/vnd.commerces+json", func() {
	Description("Commerce events")
	Attributes(func() {
		Attribute("tags", HashOf(String, String))
		Attribute("commerces", CollectionOf(Commerce))
	})
	View("default", func() {
		Attribute("tags")
		Attribute("commerces")
	})
	Required("tags", "commerces")
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
		Attribute("cache", HashOf(Integer, SegmentRuleCache), "Cache object for third party (remplib.js) to use indexed by SegmentRule-based key")
		Attribute("event_rules", HashOf(String, ArrayOf(Integer)), "Map of which rules should be incremented for selected events.")
		Attribute("overridable_fields", HashOf(Integer, ArrayOf(String)), "Array of overridable fields belonging to rules.")
		Attribute("flags", HashOf(Integer, HashOf(String, String)), "Array of flags belonging to rules.")
	})
	View("default", func() {
		Attribute("check")
		Attribute("cache")
		Attribute("event_rules")
		Attribute("overridable_fields")
		Attribute("flags")
	})
	Required("check", "cache", "event_rules", "overridable_fields", "flags")
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
		Attribute("id", String)
		Attribute("category", String)
		Attribute("action", String)
		Attribute("system", System)
		Attribute("user", User)
		Attribute("utm_source", String)
		Attribute("utm_campaign", String)
		Attribute("utm_medium", String)
		Attribute("utm_content", String)
	})
	View("default", func() {
		Attribute("id")
		Attribute("category")
		Attribute("action")
		Attribute("system")
		Attribute("user")
		Attribute("utm_source")
		Attribute("utm_campaign")
		Attribute("utm_medium")
		Attribute("utm_content")
	})
	Required("system", "category", "action")
})

var TimeHistogram = MediaType("application/vnd.time.histogram+json", func() {
	Description("Time histogram data")
	Attributes(func() {
		Attribute("time", DateTime)
		Attribute("value", Number)
	})
	View("default", func() {
		Attribute("time")
		Attribute("value")
	})
	Required("time", "value")
})

var Commerce = MediaType("application/vnd.commerce+json", func() {
	Description("Commerce event")
	Attributes(func() {
		Attribute("id", String)
		Attribute("step", String, func() {
			Enum("checkout", "payment", "purchase", "refund")
		})
		Attribute("details", CommerceDetails)
		Attribute("source", Source, "UTM and social source metadata")
		Attribute("article", Article)
		Attribute("system", System)
		Attribute("user", User)
	})
	View("default", func() {
		Attribute("id")
		Attribute("step")
		Attribute("details")
		Attribute("source")
		Attribute("article")
		Attribute("system")
		Attribute("user")
	})
	Required("step", "system", "user")
})

var Pageview = MediaType("application/vnd.pageview+json", func() {
	Description("Pageview event")
	Attributes(func() {
		Attribute("id", String)
		Attribute("system", System)
		Attribute("user", User)
		Attribute("article", Article)
	})
	View("default", func() {
		Attribute("id")
		Attribute("system")
		Attribute("user")
		Attribute("article")
	})
	Required("system")
})

var User = MediaType("application/vnd.user+json", func() {
	Attributes(func() {
		Attribute("id", String, "ID of reader")
		Attribute("subscriber", Boolean, "Flag whether user is subscriber (has paid for access)")
		Attribute("browser_id", String, "Anonymized ID of user's browser")
		Attribute("url", String, "URL of the content/conversion point", func() {
			Format("uri")
		})
		Attribute("user_agent", String, "User agent of client")
		Attribute("ip_address", String, "IP address of client", func() {
			Format("ip")
		})
		Attribute("source", Source, "UTM and social source metadata")
		Attribute("remp_session_id", String, "ID of reader's session")
		Attribute("remp_pageview_id", String, "ID of pageview")
		Attribute("referer", String, "Value of HTTP referer header (if present)")
		Attribute("timespent", Integer, "Number of seconds spent during pageview (if recorded)")
	})
	View("default", func() {
		Attribute("id")
		Attribute("subscriber")
		Attribute("browser_id")
		Attribute("url")
		Attribute("user_agent")
		Attribute("ip_address")
		Attribute("source")
		Attribute("remp_session_id")
		Attribute("remp_pageview_id")
		Attribute("referer")
		Attribute("timespent")
	})
	Required("remp_pageview_id")
})

var System = MediaType("application/vnd.system+json", func() {
	Attributes(func() {
		Attribute("property_token", UUID, "Property token")
		Attribute("time", DateTime, "Time of occurrence")
	})
	View("default", func() {
		Attribute("property_token")
		Attribute("time")
	})
	Required("property_token", "time")
})

var Article = MediaType("application/vnd.article+json", func() {
	Attributes(func() {
		Attribute("id", String, "ID of article")
		Attribute("category", String, "Page category (homepage, world news...")
		Attribute("tags", ArrayOf(String), "List of tags (breaking news, trump...")
		Attribute("author_id", String, "ID of author")
		Attribute("locked", Boolean, "Flag whether content was locked for the visitor")
		Attribute("variants", HashOf(String, String), "Hash of key-value pairs bearing A/B test variant information (what's A/B-tested / variant label)")
	})
	View("default", func() {
		Attribute("id")
		Attribute("category")
		Attribute("tags")
		Attribute("author_id")
		Attribute("locked")
		Attribute("variants")
	})
	Required("id")
})

var Source = MediaType("application/vnd.source+json", func() {
	Attributes(func() {
		Attribute("utm_source", String, "Origin of user (e.g. remp_campaign)")
		Attribute("utm_medium", String, "Medium through which the came (e.g. overlay, inline)")
		Attribute("utm_campaign", String, "Reference to specific campaign (e.g. campaign ID")
		Attribute("utm_content", String, "Reference to specific campaign mean (e.g. banner ID)")
		Attribute("social", String, "Social source if available")
	})
	View("default", func() {
		Attribute("utm_source")
		Attribute("utm_medium")
		Attribute("utm_campaign")
		Attribute("utm_content")
		Attribute("social")
	})
})

var CommerceDetails = MediaType("application/vnd.commerce_details+json", func() {
	Attributes(func() {
		Attribute("funnel_id", String, "ID of funnel user is being routed trough")
		Attribute("transaction_id", String, "Public ID of transaction (variable symbol)")
		Attribute("product_ids", ArrayOf(String), "Public IDs of selected products")
		Attribute("revenue", Revenue, "Amount of money for given payment")
	})
	View("default", func() {
		Attribute("funnel_id")
		Attribute("transaction_id")
		Attribute("product_ids")
		Attribute("revenue")
	})
	Required("funnel_id", "revenue", "transaction_id", "product_ids")
})

var Revenue = MediaType("application/vnd.revenue+json", func() {
	Attributes(func() {
		Attribute("amount", Number, "Numeric amount of money")
		Attribute("currency", String, "ISO 4217 representation of currency")
	})
	View("default", func() {
		Attribute("amount")
		Attribute("currency")
	})
	Required("amount", "currency")
})

var Flags = MediaType("application/vnd.flags+json", func() {
	Attributes(func() {
		Attribute("pageviews", ArrayOf(String), "Pageviews category flags")
		Attribute("commerce", ArrayOf(String), "Commerce category flags")
		Attribute("events", ArrayOf(String), "Events category flags")
	})
	View("default", func() {
		Attribute("pageviews")
		Attribute("commerce")
		Attribute("events")
	})
	Required("pageviews", "commerce", "events")
})
