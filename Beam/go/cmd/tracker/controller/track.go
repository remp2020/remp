package controller

import (
	"fmt"
	"strings"

	"github.com/Shopify/sarama"
	"github.com/goadesign/goa"
	influxClient "github.com/influxdata/influxdb/client/v2"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// TrackController implements the track resource.
type TrackController struct {
	*goa.Controller
	EventProducer   sarama.AsyncProducer
	PropertyStorage model.PropertyStorage
}

// Event represents Influx event structure
type Event struct {
	Action   string                 `json:"action"`
	Category string                 `json:"category"`
	Fields   map[string]interface{} `json:"fields"`
	Value    float64                `json:"value"`
}

// NewTrackController creates a track controller.
func NewTrackController(service *goa.Service, ep sarama.AsyncProducer, ps model.PropertyStorage) *TrackController {
	return &TrackController{
		Controller:      service.NewController("TrackController"),
		EventProducer:   ep,
		PropertyStorage: ps,
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

	if err := c.pushEvent(ctx.Payload.System, ctx.Payload.User, "commerce", tags, values); err != nil {
		return err
	}
	return ctx.Accepted()
}

// Event runs the event action.
func (c *TrackController) Event(ctx *app.EventTrackContext) error {
	_, ok, err := c.PropertyStorage.Get(ctx.Payload.System.PropertyToken.String())
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}

	tags := map[string]string{
		"category": ctx.Payload.Category,
		"action":   ctx.Payload.Action,
	}
	fields := map[string]interface{}{}
	if ctx.Payload.Value != nil {
		fields["value"] = *ctx.Payload.Value
	}
	for key, val := range ctx.Payload.Fields {
		fields[key] = val
	}
	if err := c.pushEvent(ctx.Payload.System, ctx.Payload.User, "events", tags, fields); err != nil {
		return err
	}
	return ctx.Accepted()
}

// Pageview runs the pageview action.
func (c *TrackController) Pageview(ctx *app.PageviewTrackContext) error {
	tags := map[string]string{}
	values := map[string]interface{}{
		"article_id": ctx.Payload.Article.ID,
	}

	if ctx.Payload.Article.AuthorID != nil {
		values["author_id"] = *ctx.Payload.Article.AuthorID
	}
	if ctx.Payload.Article.CampaignID != nil {
		values["campaign_id"] = *ctx.Payload.Article.CampaignID
	}
	if ctx.Payload.Article.Category != nil {
		values["category"] = *ctx.Payload.Article.Category
	}
	if err := c.pushEvent(ctx.Payload.System, ctx.Payload.User, "pageviews", tags, values); err != nil {
		return err
	}
	return ctx.Accepted()
}

// pushEvent pushes new event to the InfluxDB.
func (c *TrackController) pushEvent(system *app.System, user *app.User,
	name string, tags map[string]string, fields map[string]interface{}) error {
	fields["token"] = system.PropertyToken
	if user != nil {
		fields["ip"] = user.IPAddress
		fields["url"] = user.URL
		fields["user_agent"] = user.UserAgent
		if user.UserID != nil {
			tags["user_id"] = *user.UserID
		}
	}

	p, err := influxClient.NewPoint(name, tags, fields, system.Time)
	if err != nil {
		return err
	}
	c.EventProducer.Input() <- &sarama.ProducerMessage{
		Topic: "beam_events",
		Value: sarama.StringEncoder(p.String()),
	}
	return nil
}
