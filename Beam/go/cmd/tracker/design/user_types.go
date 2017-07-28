package design

import . "github.com/goadesign/goa/design"
import . "github.com/goadesign/goa/design/apidsl"

var User = Type("User", func() {
	Attribute("url", String, "URL", func() {
		Format("uri")
	})
	Attribute("user_agent", String, "User agent of client")
	Attribute("ip_address", String, "IP address of client", func() {
		Format("ip")
	})
	Attribute("user_id", String, "ID of reader")
	Required("url", "user_agent", "ip_address")
})

var System = Type("System", func() {
	Attribute("property_token", UUID, "Property token")
	Attribute("time", DateTime, "Time of occurrence")
	Required("property_token", "time")
})

var Pageview = Type("Pageview", func() {
	Description("Pageview is the payload for tracking pageview event")

	Attribute("system", System)
	Attribute("user", User)
	Attribute("article", PageviewArticle)

	Required("system", "user", "article")
})

var Commerce = Type("Commerce", func() {
	Description("Commerce is the payload for tracking commerce event")

	Attribute("system", System)
	Attribute("user", User)
	Attribute("type", String, func() {
		Enum("checkout", "payment", "purchase", "refund")
	})
	Attribute("checkout", CommerceCheckout)
	Attribute("payment", CommercePayment, "Used when user confirmed checkout and was redirected to the payment processor")
	Attribute("purchase", CommercePayment, "Used when payment processor confirms the payment")
	Attribute("refund", CommercePayment, "Used when refund is issued. Revenue should contain refunded amount of money.")

	Required("system", "user", "type")
})

var Event = Type("Event", func() {
	Description("TrackEvent is the payload for tracking generic event")

	Attribute("system", System)
	Attribute("user", User)

	Attribute("category", String, "Category of event (time, video, comment)")
	Attribute("action", String, "Specific action (read, pause, reply)")
	Attribute("value", Number, "Numeric value of event (read 60 seconds, paused after 200 seconds, 3rd comment")
	Attribute("fields", HashOf(String, Any), "Custom filtering fields")

	Required("system", "category", "action")
})

var PageviewArticle = Type("PageviewArticle", func() {
	Description("PageviewArticle is the payload for tracking article-related data during pageview")

	Attribute("id", String, "ID of article")
	Attribute("category", String, "Page category (homepage, world news...")
	Attribute("tags", ArrayOf(String), "List of tags (breaking news, trump...")
	Attribute("author_id", String, "ID of author")
	Attribute("campaign_id", String, "ID of campaign")

	Required("id")
})

var CommerceCheckout = Type("CommerceCheckout", func() {
	Attribute("funnel_id", String)

	Required("funnel_id")
})

var CommercePayment = Type("CommercePayment", func() {
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
