package design

import (
	. "goa.design/goa/v3/dsl"
)

var User = Type("User", func() {
	Attribute("id", String, "ID of logged user")
	Attribute("subscriber", Boolean, "Flag whether user is subscriber (has paid for access)")
	Attribute("subscription_ids", ArrayOf(String), "List of currently active subscription IDs granting user access")
	Attribute("browser_id", String, "Anonymized ID of user's browser")
	Attribute("url", String, "URL of the content/conversion point", func() {
		Format("uri")
	})
	Attribute("canonical_url", String, "Canonical URL of the content/conversion point. If not provided, value of url attribute is used.", func() {
		Format("uri")
	})
	Attribute("adblock", Boolean, "Flag whether user has adblock enabled")
	Attribute("window_height", Int, "Height of the users browser window")
	Attribute("window_width", Int, "Width of the users browser window")
	Attribute("cookies", Boolean, "Flag whether user has cookies enabled")
	Attribute("websockets", Boolean, "Flag whether user has websockets supported browser")
	Attribute("user_agent", String, "User agent of client")
	Attribute("ip_address", String, "IP address of client", func() {
		Format("ip")
	})
	Attribute("source", Source, "RTM (REMP-like UTM) and social source metadata")
	Attribute("remp_session_id", String, "ID of reader's session")
	Attribute("remp_pageview_id", String, "ID of pageview")
	Attribute("referer", String, "Value of HTTP referer header (if present)")
	Attribute("explicit_referer_medium", String, "Explicit referer medium value that overrides implicit referer medium based on Referer header")
})

var System = Type("System", func() {
	Attribute("property_token", String, "Property token", func() {
		Format(FormatUUID)
	})
	Attribute("time", String, "Time of occurrence", func() {
		Format(FormatDateTime)
	})
	Required("property_token", "time")
})
var Pageview = Type("Pageview", func() {
	Description("Pageview is the payload for tracking pageview event")

	Attribute("action", String, func() {
		Enum("load", "timespent", "progress")
	})
	Attribute("system", System)
	Attribute("user", User)
	Attribute("article", Article)
	Attribute("timespent", TimeSpent)
	Attribute("progress", Progress)

	Required("system", "user", "action")
})

var Commerce = Type("Commerce", func() {
	Description("Commerce is the payload for tracking commerce event")

	Attribute("system", System)
	Attribute("user", User)
	Attribute("article", Article)
	Attribute("step", String, func() {
		Enum("checkout", "payment", "purchase", "refund")
	})
	Attribute("remp_commerce_id", String, "ID of event")
	Attribute("commerce_session_id", String, "ID of session for whole commerce process. Unique identifier should be generated on client-side when user starts commerce process at the checkout step and the client should track the same value for rest of commerce process.")
	Attribute("checkout", CommerceCheckout, "Used when user enters the checkout process (reviews the cart)")
	Attribute("payment", CommercePayment, "Used when user confirmed checkout and was redirected to the payment processor")
	Attribute("purchase", CommercePayment, "Used when payment processor confirms the payment")
	Attribute("refund", CommercePayment, "Used when refund is issued. Revenue should contain refunded amount of money.")

	Required("system", "user", "step")
})

var Event = Type("Event", func() {
	Description("TrackEvent is the payload for tracking generic event")

	Attribute("system", System)
	Attribute("user", User)

	Attribute("category", String, "Category of event (time, video, comment)")
	Attribute("action", String, "Specific action (read, pause, reply)")
	Attribute("value", Int, "Numeric value of event (read 60 seconds, paused after 200 seconds, 3rd comment")
	Attribute("tags", MapOf(String, String), "Custom filtering tags")
	Attribute("fields", MapOf(String, Any), "Additinal key-value data")
	Attribute("remp_event_id", String, "ID of event")
	Attribute("article_id", String, "ID of article this event is linked to. Events linked to the specific article can be later displayed at Beam dashboard timeline and at article detail timeline.")

	Required("system", "category", "action")
})

var Article = Type("Article", func() {
	Description("Article is the payload for tracking article-related data")

	Attribute("id", String, "ID of article")
	Attribute("category", String, "Page category (homepage, world news...")
	Attribute("tags", ArrayOf(String), "List of tags (breaking news, trump...")
	Attribute("author_id", String, "ID of author")
	Attribute("content_type", String, "Content type, e.g. 'article' (by default)")
	Attribute("locked", Boolean, "Flag whether content was locked for the visitor")
	Attribute("variants", MapOf(String, String), "Hash of key-value pairs bearing A/B test variant information (what's A/B-tested / variant label)")

	Required("id")
})

var TimeSpent = Type("TimeSpent", func() {
	Description("Update time spent on page")

	Attribute("seconds", Int, "Number of seconds from previous update of time spent on page")
	Attribute("unload", Boolean, "Flag to indicate last update of time spent on page before unload event")

	Required("seconds")
})

var Progress = Type("Progress", func() {
	Description("Update reading progress on pageview")

	Attribute("page_ratio", Float64, "Whole page reading ratio", func() {
		Minimum(0)
		Maximum(1)
	})
	Attribute("article_ratio", Float64, "Article reading ratio", func() {
		Minimum(0)
		Maximum(1)
	})
	Attribute("unload", Boolean, "Flag to indicate last update of progress on page before unload event")

	Required("page_ratio")
})

var Source = Type("source", func() {
	Description("User-source related data")

	Attribute("rtm_source", String, "Origin of user (e.g. remp_campaign)")
	Attribute("rtm_medium", String, "Medium through which the came (e.g. overlay, inline)")
	Attribute("rtm_campaign", String, "Reference to specific campaign (e.g. campaign ID")
	Attribute("rtm_content", String, "Reference to specific campaign mean (e.g. banner ID)")
	Attribute("rtm_variant", String, "Reference to specific banner variant (e.g. variant ID)")

	// Deprecated, to be removed (replaced with rtm_)
	Attribute("utm_source", String, "[DEPRECATED] Origin of user (e.g. remp_campaign). Deprecated, please use `rtm_source`")
	Attribute("utm_medium", String, "[DEPRECATED] Medium through which the came (e.g. overlay, inline). Deprecated, please use `rtm_medium`")
	Attribute("utm_campaign", String, "[DEPRECATED] Reference to specific campaign (e.g. campaign ID). Deprecated, please use `rtm_campaign`")
	Attribute("utm_content", String, "[DEPRECATED] Reference to specific campaign mean (e.g. banner ID). Deprecated, please use `rtm_content`")
	Attribute("banner_variant", String, "[DEPRECATED] Reference to specific banner variant (e.g. variant ID). . Deprecated, please use `rtm_variant`")
})

var CommerceCheckout = Type("CommerceCheckout", func() {
	Attribute("funnel_id", String, "ID of funnel user is being routed trough")

	Required("funnel_id")
})

var CommercePayment = Type("CommercePayment", func() {
	Attribute("funnel_id", String, "ID of funnel user is being routed trough")
	Attribute("transaction_id", String, "Public ID of transaction (variable symbol)")
	Attribute("product_ids", ArrayOf(String), "Public IDs of selected products")
	Attribute("revenue", Revenue, "Amount of money for given payment")

	Required("revenue", "transaction_id", "product_ids")
})

var Revenue = Type("Revenue", func() {
	Attribute("amount", Float64, "Numeric amount of money")
	Attribute("currency", String, "ISO 4217 representation of currency")

	Required("amount", "currency")
})

var Entity = Type("Entity", func() {
	Attribute("entity_def", func() {
		Attribute("id", String)
		Attribute("name", String)
		Attribute("data", MapOf(String, Any))

		Required("id", "name", "data")
	})
	Attribute("system", System)
	Required("entity_def", "system")
})

var ImpressionData = Type("ImpressionData", func() {
	Description("ImpressionData represents a single impression event with block, type and element identifiers")
	Attribute("bl", String, "Block identifier")
	Attribute("tp", String, "Type identifier")
	Attribute("eid", ArrayOf(String), "Element identifiers")
	Required("bl", "tp", "eid")
})

var Impressions = Type("Impressions", func() {
	Description("Impressions is the payload for tracking impression events")
	Attribute("rpid", String, "REMP pageview ID")
	Attribute("d", ArrayOf(ImpressionData), "Tracked impression data")
	Attribute("t", String, "Time of occurrence", func() {
		Format(FormatDateTime)
	})
	Required("d", "rpid")
})
