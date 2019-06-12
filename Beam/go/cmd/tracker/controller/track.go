package controller

import (
	"encoding/json"
	"fmt"
	"net/url"
	"strings"
	"time"

	"github.com/Shopify/sarama"
	"github.com/avct/uasurfer"
	"github.com/goadesign/goa"
	influxClient "github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
	refererparser "github.com/snowplow/referer-parser/go"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// TrackController implements the track resource.
type TrackController struct {
	*goa.Controller
	EventProducer       sarama.AsyncProducer
	PropertyStorage     model.PropertyStorage
	EntitySchemaStorage model.EntitySchemaStorage
}

// Event represents Influx event structure
type Event struct {
	Action   string                 `json:"action"`
	Category string                 `json:"category"`
	Fields   map[string]interface{} `json:"fields"`
	Value    float64                `json:"value"`
}

// NewTrackController creates a track controller.
func NewTrackController(service *goa.Service, ep sarama.AsyncProducer, ps model.PropertyStorage, ess model.EntitySchemaStorage) *TrackController {
	return &TrackController{
		Controller:          service.NewController("TrackController"),
		EventProducer:       ep,
		PropertyStorage:     ps,
		EntitySchemaStorage: ess,
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

	fields := map[string]interface{}{}

	if ctx.Payload.Article != nil {
		at, av := articleValues(ctx.Payload.Article)
		for key, tag := range at {
			tags[key] = tag
		}
		for key, val := range av {
			fields[key] = val
		}
	}

	switch ctx.Payload.Step {
	case "checkout":
		fields["funnel_id"] = ctx.Payload.Checkout.FunnelID
	case "payment":
		if ctx.Payload.Payment.FunnelID != nil {
			fields["funnel_id"] = *ctx.Payload.Payment.FunnelID
		}
		fields["product_ids"] = strings.Join(ctx.Payload.Payment.ProductIds, ",")
		fields["revenue"] = ctx.Payload.Payment.Revenue.Amount
		fields["transaction_id"] = ctx.Payload.Payment.TransactionID
		tags["currency"] = ctx.Payload.Payment.Revenue.Currency
	case "purchase":
		if ctx.Payload.Purchase.FunnelID != nil {
			fields["funnel_id"] = *ctx.Payload.Purchase.FunnelID
		}
		fields["product_ids"] = strings.Join(ctx.Payload.Purchase.ProductIds, ",")
		fields["revenue"] = ctx.Payload.Purchase.Revenue.Amount
		fields["transaction_id"] = ctx.Payload.Purchase.TransactionID
		tags["currency"] = ctx.Payload.Purchase.Revenue.Currency
	case "refund":
		if ctx.Payload.Refund.FunnelID != nil {
			fields["funnel_id"] = *ctx.Payload.Refund.FunnelID
		}
		fields["product_ids"] = strings.Join(ctx.Payload.Refund.ProductIds, ",")
		fields["revenue"] = ctx.Payload.Refund.Revenue.Amount
		fields["transaction_id"] = ctx.Payload.Refund.TransactionID
		tags["currency"] = ctx.Payload.Refund.Revenue.Currency
	default:
		return fmt.Errorf("unhandled commerce step: %s", ctx.Payload.Step)
	}

	tags, fields = c.payloadToTagsFields(ctx.Payload.System, ctx.Payload.User, tags, fields)
	if err := c.pushInternal(model.TableCommerce, ctx.Payload.System.Time, tags, fields); err != nil {
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

	tags, fields = c.payloadToTagsFields(ctx.Payload.System, ctx.Payload.User, tags, fields)
	if err := c.pushInternal(model.TableEvents, ctx.Payload.System.Time, tags, fields); err != nil {
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
	fields := map[string]interface{}{}

	var measurement string
	switch ctx.Payload.Action {
	case model.ActionPageviewLoad:
		tags["action"] = model.ActionPageviewLoad
		measurement = model.TablePageviews
	case model.ActionPageviewTimespent:
		tags["action"] = model.ActionPageviewTimespent
		measurement = model.TableTimespent
		if ctx.Payload.Timespent != nil {
			fields["timespent"] = ctx.Payload.Timespent.Seconds
			fields["unload"] = false
			if ctx.Payload.Timespent.Unload != nil && *ctx.Payload.Timespent.Unload {
				fields["unload"] = true
			}
		}
	case model.ActionPageviewProgress:
		tags["action"] = model.ActionPageviewProgress
		measurement = model.TableProgress
		if ctx.Payload.Progress != nil {
			fields["page_progress"] = ctx.Payload.Progress.PageRatio
			if ctx.Payload.Progress.ArticleRatio != nil {
				fields["article_progress"] = *ctx.Payload.Progress.ArticleRatio
			}
			fields["unload"] = false
			if ctx.Payload.Progress.Unload != nil && *ctx.Payload.Progress.Unload {
				fields["unload"] = true
			}
		}
	default:
		return ctx.BadRequest(fmt.Errorf("incorrect pageview action [%s]", ctx.Payload.Action))
	}

	if ctx.Payload.Article != nil {
		fields[model.FlagArticle] = true
		at, av := articleValues(ctx.Payload.Article)
		for key, tag := range at {
			tags[key] = tag
		}
		for key, val := range av {
			fields[key] = val
		}
	} else {
		fields[model.FlagArticle] = false
	}

	tags, fields = c.payloadToTagsFields(ctx.Payload.System, ctx.Payload.User, tags, fields)
	if err := c.pushInternal(measurement, ctx.Payload.System.Time, tags, fields); err != nil {
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
	schema, ok, err := c.EntitySchemaStorage.Get(ctx.Payload.EntityDef.Name)
	if err != nil {
		return err
	}
	if !ok {
		return ctx.BadRequest(goa.ErrBadRequest(fmt.Errorf("can't find entity schema for entity: %s", ctx.Payload.EntityDef.Name)))
	}

	// validate entity schema
	err = (*EntitySchema)(schema).Validate(ctx.Payload)
	if err != nil {
		return ctx.BadRequest(goa.ErrBadRequest(errors.Wrap(err, "schema validation failed")))
	}

	fields := ctx.Payload.EntityDef.Data
	fields["remp_entity_id"] = ctx.Payload.EntityDef.ID

	if err := c.pushInternal(model.TableEntities, ctx.Payload.System.Time, nil, fields); err != nil {
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

func (c *TrackController) payloadToTagsFields(system *app.System, user *app.User,
	tags map[string]string, fields map[string]interface{}) (map[string]string, map[string]interface{}) {
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

			// Try to parse URL before being passed to refererparser library (since it produces NPE in case of invalid URL)
			parsedURL, err := url.Parse(*user.Referer)
			if err != nil {
				tags["derived_referer_medium"] = "unknown"
			} else {
				tags["derived_referer_host_with_path"] = fmt.Sprintf("%s://%s%s", parsedURL.Scheme, parsedURL.Host, parsedURL.Path)

				parsedRef := refererparser.Parse(*user.Referer)
				if user.URL != nil {
					parsedRef.SetCurrent(*user.URL)
				}
				tags["derived_referer_medium"] = parsedRef.Medium
				tags["derived_referer_source"] = parsedRef.Referer

				if tags["derived_referer_medium"] == "unknown" {
					tags["derived_referer_medium"] = "external"
				}
				// derived_referer_medium can be also rewritten by user.Source.UtmMedium, see below
			}
		} else {
			tags["derived_referer_medium"] = "direct"
		}

		if user.Source != nil {
			if user.Source.UtmSource != nil {
				tags["utm_source"] = *user.Source.UtmSource
			}
			if user.Source.UtmMedium != nil {
				tags["utm_medium"] = *user.Source.UtmMedium

				// Rewrite referer medium in case of email UTM
				if *user.Source.UtmMedium == "email" {
					tags["derived_referer_medium"] = "email"
				}
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
		if user.ExplicitRefererMedium != nil {
			tags["explicit_referer_medium"] = *user.ExplicitRefererMedium
		}
	} else {
		fields["signed_in"] = false
	}

	return tags, fields
}

// pushInternal pushes new event to the InfluxDB.
func (c *TrackController) pushInternal(measurement string, time time.Time,
	tags map[string]string, fields map[string]interface{}) error {

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

	p, err := influxClient.NewPoint(measurement, nil, data, time)
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
