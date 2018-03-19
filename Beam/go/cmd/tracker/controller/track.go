package controller

import (
	"encoding/json"
	"fmt"
	"strings"

	"github.com/Shopify/sarama"
	"github.com/goadesign/goa"
	influxClient "github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
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
	_, ok, err := c.PropertyStorage.Get(ctx.Payload.System.PropertyToken.String())
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}

	tags := map[string]string{
		"step": ctx.Payload.Step,
	}
	values := map[string]interface{}{}

	if ctx.Payload.Article != nil {
		at, av := articleValues(ctx.Payload.Article)
		for key, tag := range at {
			tags[key] = tag
		}
		for key, val := range av {
			values[key] = val
		}
	}

	switch ctx.Payload.Step {
	case "checkout":
		values["funnel_id"] = ctx.Payload.Checkout.FunnelID
	case "payment":
		if ctx.Payload.Payment.FunnelID != nil {
			values["funnel_id"] = *ctx.Payload.Payment.FunnelID
		}
		values["product_ids"] = strings.Join(ctx.Payload.Payment.ProductIds, ",")
		values["revenue"] = ctx.Payload.Payment.Revenue.Amount
		values["transaction_id"] = ctx.Payload.Payment.TransactionID
		tags["currency"] = ctx.Payload.Payment.Revenue.Currency
	case "purchase":
		if ctx.Payload.Purchase.FunnelID != nil {
			values["funnel_id"] = *ctx.Payload.Purchase.FunnelID
		}
		values["product_ids"] = strings.Join(ctx.Payload.Purchase.ProductIds, ",")
		values["revenue"] = ctx.Payload.Purchase.Revenue.Amount
		values["transaction_id"] = ctx.Payload.Purchase.TransactionID
		tags["currency"] = ctx.Payload.Purchase.Revenue.Currency
	case "refund":
		if ctx.Payload.Refund.FunnelID != nil {
			values["funnel_id"] = *ctx.Payload.Refund.FunnelID
		}
		values["product_ids"] = strings.Join(ctx.Payload.Refund.ProductIds, ",")
		values["revenue"] = ctx.Payload.Refund.Revenue.Amount
		values["transaction_id"] = ctx.Payload.Refund.TransactionID
		tags["currency"] = ctx.Payload.Refund.Revenue.Currency
	default:
		return fmt.Errorf("unhandled commerce step: %s", ctx.Payload.Step)
	}

	if err := c.pushInternal(ctx.Payload.System, ctx.Payload.User, model.TableCommerce, tags, values); err != nil {
		return err
	}

	topic := fmt.Sprintf("%s_%s", "commerce", ctx.Payload.Step)
	value, err := json.Marshal(ctx.Payload)
	if err != nil {
		return errors.Wrap(err, "unable to marshal payload for kafka")
	}
	c.pushPublic(topic, value)

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
	for key, val := range ctx.Payload.Tags {
		tags[key] = val
	}
	for key, val := range ctx.Payload.Fields {
		fields[key] = val
	}
	if err := c.pushInternal(ctx.Payload.System, ctx.Payload.User, model.TableEvents, tags, fields); err != nil {
		return err
	}

	// push public

	topic := fmt.Sprintf("%s_%s", ctx.Payload.Category, ctx.Payload.Action)
	value, err := json.Marshal(ctx.Payload)
	if err != nil {
		return errors.Wrap(err, "unable to marshal payload for kafka")
	}
	c.pushPublic(topic, value)

	return ctx.Accepted()
}

// Pageview runs the pageview action.
func (c *TrackController) Pageview(ctx *app.PageviewTrackContext) error {
	_, ok, err := c.PropertyStorage.Get(ctx.Payload.System.PropertyToken.String())
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}

	tags := map[string]string{
		"category": model.CategoryPageview,
	}
	values := map[string]interface{}{}

	var measurement string
	switch ctx.Payload.Action {
	case model.ActionPageviewLoad:
		tags["action"] = model.ActionPageviewLoad
		measurement = model.TablePageviews
	case model.ActionPageviewTimespent:
		tags["action"] = model.ActionPageviewTimespent
		measurement = model.TableTimespent
		if ctx.Payload.Timespent != nil {
			values["timespent"] = ctx.Payload.Timespent.Seconds
			tags["unload"] = "0"
			if ctx.Payload.Timespent.Unload != nil && *ctx.Payload.Timespent.Unload {
				tags["unload"] = "1"
			}
		}
	default:
		return ctx.BadRequest(fmt.Errorf("incorrect pageview action [%s]", ctx.Payload.Action))
	}

	if ctx.Payload.Article != nil {
		tags[model.FlagArticle] = "1"
		at, av := articleValues(ctx.Payload.Article)
		for key, tag := range at {
			tags[key] = tag
		}
		for key, val := range av {
			values[key] = val
		}
	} else {
		tags[model.FlagArticle] = "0"
	}

	if err := c.pushInternal(ctx.Payload.System, ctx.Payload.User, measurement, tags, values); err != nil {
		return err
	}
	return ctx.Accepted()
}

func articleValues(article *app.Article) (map[string]string, map[string]interface{}) {
	tags := map[string]string{
		"article_id": article.ID,
	}
	values := map[string]interface{}{}
	if article.AuthorID != nil {
		tags["author_id"] = *article.AuthorID
	}
	if article.Category != nil {
		tags["category"] = *article.Category
	}
	if article.Locked != nil {
		if *article.Locked {
			tags["locked"] = "1"
		} else {
			tags["locked"] = "0"
		}
	}
	for key, variant := range article.Variants {
		tags[fmt.Sprintf("%s_variant", key)] = variant
	}
	if article.Tags != nil {
		values["tags"] = strings.Join(article.Tags, ",")
	}
	return tags, values
}

// pushInternal pushes new event to the InfluxDB.
func (c *TrackController) pushInternal(system *app.System, user *app.User,
	measurement string, tags map[string]string, fields map[string]interface{}) error {
	fields["token"] = system.PropertyToken

	if user != nil {
		if user.IPAddress != nil {
			fields["ip"] = *user.IPAddress
		}
		if user.URL != nil {
			fields["url"] = *user.URL
		}
		if user.UserAgent != nil {
			fields["user_agent"] = *user.UserAgent
		}
		if user.Referer != nil {
			fields["referer"] = *user.Referer
		}
		if user.Adblock != nil {
			if *user.Adblock {
				tags["adblock"] = "1"
			} else {
				tags["adblock"] = "0"
			}
		}
		if user.WindowHeight != nil {
			fields["window_height"] = *user.WindowHeight
		}
		if user.WindowWidth != nil {
			fields["window_width"] = *user.WindowWidth
		}
		if user.Cookies != nil {
			if *user.Cookies {
				tags["cookies"] = "1"
			} else {
				tags["cookies"] = "0"
			}
		}
		if user.Websockets != nil {
			if *user.Websockets {
				tags["websockets"] = "1"
			} else {
				tags["websockets"] = "0"
			}
		}
		if user.ID != nil {
			tags["user_id"] = *user.ID
			tags["signed_in"] = "1"
		} else {
			tags["signed_in"] = "0"
		}
		if user.BrowserID != nil {
			tags["browser_id"] = *user.BrowserID
		}
		if user.RempSessionID != nil {
			tags["remp_session_id"] = *user.RempSessionID
		}
		if user.RempPageviewID != nil {
			tags["remp_pageview_id"] = *user.RempPageviewID
		}
		if user.Subscriber != nil {
			if *user.Subscriber {
				tags["subscriber"] = "1"
			} else {
				tags["subscriber"] = "0"
			}
		}

		if user.Source != nil {
			if user.Source.Social != nil {
				tags["social"] = *user.Source.Social
			}
			if user.Source.Ref != nil {
				tags["ref_source"] = *user.Source.Ref
			}
			if user.Source.UtmSource != nil {
				tags["utm_source"] = *user.Source.UtmSource
			}
			if user.Source.UtmMedium != nil {
				tags["utm_medium"] = *user.Source.UtmMedium
			}
			if user.Source.UtmCampaign != nil {
				tags["utm_campaign"] = *user.Source.UtmCampaign
			}
			if user.Source.UtmContent != nil {
				tags["utm_content"] = *user.Source.UtmContent
			}
		}
	} else {
		tags["signed_in"] = "0"
	}

	p, err := influxClient.NewPoint(measurement, tags, fields, system.Time)
	if err != nil {
		return err
	}
	c.EventProducer.Input() <- &sarama.ProducerMessage{
		Topic: "beam_events",
		Value: sarama.StringEncoder(p.String()),
	}
	return nil
}

func (c *TrackController) pushPublic(topic string, value []byte) {
	c.EventProducer.Input() <- &sarama.ProducerMessage{
		Topic: topic,
		Value: sarama.ByteEncoder(value),
	}
}
