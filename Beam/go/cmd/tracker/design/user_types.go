package design

import (
	. "github.com/goadesign/goa/design"
	. "github.com/goadesign/goa/design/apidsl"
)

var User = Type("User", func() {
	Attribute("id", String, "ID of logged user")
	Attribute("subscriber", Boolean, "Flag whether user is subscriber (has paid for access)")
	Attribute("browser_id", String, "Anonymized ID of user's browser")
	Attribute("url", String, "URL of the content/conversion point", func() {
		Format("uri")
	})
	Attribute("adblock", Boolean, "Flag whether user has adblock enabled")
	Attribute("window_height", Number, "Height of the users browser window")
	Attribute("window_width", Number, "Width of the users browser window")
	Attribute("cookies", Boolean, "Flag whether user has cookies enabled")
	Attribute("websockets", Boolean, "Flag whether user has websockets supported browser")
	Attribute("user_agent", String, "User agent of client")
	Attribute("ip_address", String, "IP address of client", func() {
		Format("ip")
	})
	Attribute("source", Source, "UTM and social source metadata")
	Attribute("remp_session_id", String, "ID of reader's session")
	Attribute("remp_pageview_id", String, "ID of pageview")
	Attribute("referer", String, "Value of HTTP referer header (if present)")
})

var System = Type("System", func() {
	Attribute("property_token", UUID, "Property token")
	Attribute("time", DateTime, "Time of occurrence")
	Required("property_token", "time")
})

var Pageview = Type("Pageview", func() {
	Description("Pageview is the payload for tracking pageview event")

	Attribute("action", String, func() {
		Enum("load", "timespent")
	})
	Attribute("system", System)
	Attribute("user", User)
	Attribute("article", Article)
	Attribute("timespent", TimeSpent)

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
	Attribute("value", Number, "Numeric value of event (read 60 seconds, paused after 200 seconds, 3rd comment")
	Attribute("tags", HashOf(String, String), "Custom filtering tags")
	Attribute("fields", HashOf(String, Any), "Additinal key-value data")

	Required("system", "category", "action")
})

var Article = Type("Article", func() {
	Description("Article is the payload for tracking article-related data")

	Attribute("id", String, "ID of article")
	Attribute("category", String, "Page category (homepage, world news...")
	Attribute("tags", ArrayOf(String), "List of tags (breaking news, trump...")
	Attribute("author_id", String, "ID of author")
	Attribute("locked", Boolean, "Flag whether content was locked for the visitor")
	Attribute("variants", HashOf(String, String), "Hash of key-value pairs bearing A/B test variant information (what's A/B-tested / variant label)")

	Required("id")
})

var TimeSpent = Type("TimeSpent", func() {
	Description("Update time spent on page")

	Attribute("seconds", Integer, "Number of seconds from previous update of time spent on page")
	Attribute("unload", Boolean, "Flag to indicate last update of time spent on page before unload event")

	Required("seconds")
})

var Source = Type("source", func() {
	Description("User-source related data")

	Attribute("utm_source", String, "Origin of user (e.g. remp_campaign)")
	Attribute("utm_medium", String, "Medium through which the came (e.g. overlay, inline)")
	Attribute("utm_campaign", String, "Reference to specific campaign (e.g. campaign ID")
	Attribute("utm_content", String, "Reference to specific campaign mean (e.g. banner ID)")
	Attribute("social", String, "Social source if available")
	Attribute("ref", String, "Direct referrer source if available")
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
	Attribute("amount", Number, "Numeric amount of money")
	Attribute("currency", String, "ISO 4217 representation of currency")

	Required("amount", "currency")
})
