package controller

import (
	"encoding/json"
	"fmt"
	"net/url"
	"strings"

	"github.com/Shopify/sarama"
	"github.com/avct/uasurfer"
	"github.com/goadesign/goa"
	influxClient "github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
	"github.com/snowplow/referer-parser/go"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// TrackController implements the track resource.
type TrackController struct {
	*goa.Controller
	EventProducer   sarama.AsyncProducer
	PropertyStorage model.PropertyStorage
	Entities        model.Entities
}

// Event represents Influx event structure
type Event struct {
	Action   string                 `json:"action"`
	Category string                 `json:"category"`
	Fields   map[string]interface{} `json:"fields"`
	Value    float64                `json:"value"`
}

// NewTrackController creates a track controller.
func NewTrackController(service *goa.Service, ep sarama.AsyncProducer, ps model.PropertyStorage, e model.Entities) *TrackController {
	return &TrackController{
		Controller:      service.NewController("TrackController"),
		EventProducer:   ep,
		PropertyStorage: ps,
		Entities:        e,
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
	if ctx.Payload.RempCommerceID != nil {
		tags["remp_commerce_id"] = *ctx.Payload.RempCommerceID
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
	if ctx.Payload.RempEventID != nil {
		tags["remp_event_id"] = *ctx.Payload.RempEventID
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
			values["unload"] = false
			if ctx.Payload.Timespent.Unload != nil && *ctx.Payload.Timespent.Unload {
				values["unload"] = true
			}
		}
	default:
		return ctx.BadRequest(fmt.Errorf("incorrect pageview action [%s]", ctx.Payload.Action))
	}

	if ctx.Payload.Article != nil {
		values[model.FlagArticle] = true
		at, av := articleValues(ctx.Payload.Article)
		for key, tag := range at {
			tags[key] = tag
		}
		for key, val := range av {
			values[key] = val
		}
	} else {
		values[model.FlagArticle] = false
	}

	if err := c.pushInternal(ctx.Payload.System, ctx.Payload.User, measurement, tags, values); err != nil {
		return err
	}
	return ctx.Accepted()
}

// Entity runs the entity action.
func (c *TrackController) Entity(ctx *app.EntityTrackContext) error {
	_, ok, err := c.PropertyStorage.Get(ctx.Payload.System.PropertyToken.String())
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}

	// try to get entity schema
	entityName := *ctx.Payload.Entity.Name
	schema, ok, err := c.Entities.Get(entityName)
	if err != nil {
		return err
	}
	if !ok {
		return ctx.BadRequest(fmt.Errorf("can't find entity schema for entity '%v'", entityName))
	}

	// validate entity schema
	err = c.Entities.Validate(schema, ctx.Payload)
	if err != nil {
		return err
	}

	data := ctx.Payload.Entity.Data
	data["remp_entity_id"] = ctx.Payload.Entity.ID

	json, err := json.Marshal(data)
	if err != nil {
		return err
	}

	fields := make(map[string]interface{})
	fields["_json"] = string(json)

	tags := make(map[string]string)
	tags["remp_entity_id"] = ctx.Payload.Entity.ID

	// create point
	p, err := influxClient.NewPoint("entities", tags, fields)
	if err != nil {
		return err
	}

	c.EventProducer.Input() <- &sarama.ProducerMessage{
		Topic: "beam_events",
		Value: sarama.StringEncoder(p.String()),
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
			values["locked"] = true
		} else {
			values["locked"] = false
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

			ua := uasurfer.Parse(*user.UserAgent)
			fields["derived_ua_device"] = strings.TrimPrefix(ua.DeviceType.String(), "Device")
			fields["derived_ua_os"] = strings.TrimPrefix(ua.OS.Name.String(), "OS")
			fields["derived_ua_os_version"] = fmt.Sprintf("%d.%d", ua.OS.Version.Major, ua.OS.Version.Minor)
			fields["derived_ua_platform"] = strings.TrimPrefix(ua.OS.Platform.String(), "Platform")
			fields["derived_ua_browser"] = strings.TrimPrefix(ua.Browser.Name.String(), "Browser")
			fields["derived_ua_browser_version"] = fmt.Sprintf("%d.%d", ua.Browser.Version.Major, ua.Browser.Version.Minor)
		}

		if user.Referer != nil && len(*user.Referer) > 0 {
			fields["referer"] = *user.Referer
			parsedRef := refererparser.Parse(*user.Referer)
			if user.URL != nil {
				parsedRef.SetCurrent(*user.URL)
			}
			tags["derived_referer_medium"] = parsedRef.Medium
			tags["derived_referer_source"] = parsedRef.Referer

			if tags["derived_referer_medium"] == "unknown" {
				tags["derived_referer_medium"] = "external"
			}

			parsedURL, err := url.Parse(*user.Referer)
			if err == nil {
				tags["derived_referer_host_with_path"] = fmt.Sprintf("%s://%s%s", parsedURL.Scheme, parsedURL.Host, parsedURL.Path)
			}
		} else {
			tags["derived_referer_medium"] = "direct"
		}

		if user.Adblock != nil {
			fields["adblock"] = *user.Adblock
		}
		if user.WindowHeight != nil {
			fields["window_height"] = *user.WindowHeight
		}
		if user.WindowWidth != nil {
			fields["window_width"] = *user.WindowWidth
		}
		if user.Cookies != nil {
			fields["cookies"] = *user.Cookies
		}
		if user.Websockets != nil {
			fields["websockets"] = *user.Websockets
		}
		if user.ID != nil {
			tags["user_id"] = *user.ID
			fields["signed_in"] = true
		} else {
			fields["signed_in"] = false
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
			fields["subscriber"] = *user.Subscriber
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
			if user.Source.BannerVariant != nil {
				tags["banner_variant"] = *user.Source.BannerVariant
			}
		}
	} else {
		fields["signed_in"] = false
	}

	collected := make(map[string]interface{})
	for key, tag := range tags {
		collected[key] = tag
	}
	for key, field := range fields {
		collected[key] = field
	}
	json, err := json.Marshal(collected)
	if err != nil {
		return err
	}
	data := make(map[string]interface{})
	data["_json"] = string(json)

	p, err := influxClient.NewPoint(measurement, nil, data, system.Time)
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
