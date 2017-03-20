package controller

import (
	"fmt"
	"strings"

	"github.com/Shopify/sarama"
	"github.com/goadesign/goa"
	influxClient "github.com/influxdata/influxdb/client/v2"
	"gitlab.com/remp/remp/go/cmd/beam/app"
)

// TrackController implements the track resource.
type TrackController struct {
	*goa.Controller
	EventProducer sarama.AsyncProducer
}

// Event represents Influx event structure
type Event struct {
	Action   string                 `json:"action"`
	Category string                 `json:"category"`
	Fields   map[string]interface{} `json:"fields"`
	Value    float64                `json:"value"`
}

// NewTrackController creates a track controller.
func NewTrackController(service *goa.Service, ep sarama.AsyncProducer) *TrackController {
	return &TrackController{
		Controller:    service.NewController("TrackController"),
		EventProducer: ep,
	}
}

// Commerce runs the commerce action.
func (c *TrackController) Commerce(ctx *app.CommerceTrackContext) error {
	tags := map[string]string{
		"step": ctx.Payload.Type,
	}
	values := map[string]interface{}{}

	switch ctx.Payload.Type {
	case "checkout":
		values["funnel_id"] = ctx.Payload.Checkout.FunnelID
	case "payment", "purchase", "refund":
		values["product_ids"] = strings.Join(ctx.Payload.Payment.ProductIds, ",")
		values["revenue"] = ctx.Payload.Payment.Revenue.Amount
		values["transaction_id"] = ctx.Payload.Payment.TransactionID
		tags["currency"] = ctx.Payload.Payment.Revenue.Currency
	default:
		return fmt.Errorf("unhandled commerce type: %s", ctx.Payload.Type)
	}

	if err := c.pushEvent(ctx.Payload.System, "commerce", tags, values); err != nil {
		return err
	}
	return ctx.Accepted()
}

// Event runs the event action.
func (c *TrackController) Event(ctx *app.EventTrackContext) error {
	tags := map[string]string{
		"category": ctx.Payload.Category,
		"action":   ctx.Payload.Action,
	}
	values := map[string]interface{}{}
	if ctx.Payload.Value != nil {
		values["value"] = *ctx.Payload.Value
	}
	if err := c.pushEvent(ctx.Payload.System, "events", tags, values); err != nil {
		return err
	}
	return ctx.Accepted()
}

// Pageview runs the pageview action.
func (c *TrackController) Pageview(ctx *app.PageviewTrackContext) error {
	tags := map[string]string{}
	values := map[string]interface{}{
		"article_id": ctx.Payload.ArticleID,
	}
	if ctx.Payload.AuthorID != nil {
		values["author_id"] = *ctx.Payload.AuthorID
	}
	if ctx.Payload.CampaignID != nil {
		values["campaign_id"] = *ctx.Payload.CampaignID
	}
	if ctx.Payload.Category != nil {
		values["category"] = *ctx.Payload.Category
	}
	if err := c.pushEvent(ctx.Payload.System, "pageviews", tags, values); err != nil {
		return err
	}
	return ctx.Accepted()
}

func (c *TrackController) pushEvent(system *app.TrackSystem, name string, tags map[string]string, values map[string]interface{}) error {
	values["ip"] = system.IPAddress
	values["url"] = system.URL
	values["user_agent"] = system.UserAgent
	if system.UserID != nil {
		values["user_id"] = *system.UserID
	}

	p, err := influxClient.NewPoint(name, tags, values, system.Time)
	if err != nil {
		return err
	}
	c.EventProducer.Input() <- &sarama.ProducerMessage{
		Topic: "access_log",
		Key:   sarama.StringEncoder(system.APIKey),
		Value: sarama.StringEncoder(p.String()),
	}
	return nil
}
