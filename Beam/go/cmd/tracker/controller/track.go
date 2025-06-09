package controller

import (
	"beam/cmd/tracker/gen/track"
	"beam/cmd/tracker/refererparser"
	"beam/model"
	"context"
	"encoding/json"
	"fmt"
	"net/url"
	"strings"
	"time"

	"github.com/avct/uasurfer"
	"github.com/google/uuid"
	"github.com/pkg/errors"
)

// TrackController implements the track resource.
type TrackController struct {
	EventProducer       EventProducer
	PropertyStorage     model.PropertyStorage
	EntitySchemaStorage model.EntitySchemaStorage
	InternalHosts       []string
	TimespentLimit      int
}

// Event represents Influx event structure
type Event struct {
	Action   string                 `json:"action"`
	Category string                 `json:"category"`
	Fields   map[string]interface{} `json:"fields"`
	Value    float64                `json:"value"`
}

// NewTrackController returns the track service implementation.
func NewTrackController(ep EventProducer, ps model.PropertyStorage, ess model.EntitySchemaStorage, ih []string, tl int) track.Service {
	return &TrackController{
		EventProducer:       ep,
		PropertyStorage:     ps,
		EntitySchemaStorage: ess,
		InternalHosts:       ih,
		TimespentLimit:      tl,
	}
}

func (c *TrackController) checkPropertyToken(s *track.System) (err error) {
	_, ok, err := c.PropertyStorage.Get(s.PropertyToken)
	if err != nil {
		return err
	}
	if !ok {
		return track.MakeNotFound(errors.New(fmt.Sprintf("unable to find property: %s", s.PropertyToken)))
	}

	return nil
}

// Commerce runs the commerce action.
func (c *TrackController) Commerce(ctx context.Context, p *track.Commerce2) (err error) {
	err = c.checkPropertyToken(p.System)
	if err != nil {
		return err
	}

	tags := map[string]string{
		"step": p.Step,
	}
	if p.RempCommerceID != nil {
		tags["remp_commerce_id"] = *p.RempCommerceID
	}

	if p.CommerceSessionID != nil {
		tags["commerce_session_id"] = *p.CommerceSessionID
	}

	fields := map[string]interface{}{}

	if p.Article != nil {
		at, av := articleValues(p.Article)
		for key, tag := range at {
			tags[key] = tag
		}
		for key, val := range av {
			fields[key] = val
		}
	}

	switch p.Step {
	case "checkout":
		fields["funnel_id"] = p.Checkout.FunnelID
	case "payment":
		if p.Payment.FunnelID != nil {
			fields["funnel_id"] = *p.Payment.FunnelID
		}
		fields["product_ids"] = strings.Join(p.Payment.ProductIds, ",")
		fields["revenue"] = p.Payment.Revenue.Amount
		fields["transaction_id"] = p.Payment.TransactionID
		tags["currency"] = p.Payment.Revenue.Currency
	case "purchase":
		if p.Purchase.FunnelID != nil {
			fields["funnel_id"] = *p.Purchase.FunnelID
		}
		fields["product_ids"] = strings.Join(p.Purchase.ProductIds, ",")
		fields["revenue"] = p.Purchase.Revenue.Amount
		fields["transaction_id"] = p.Purchase.TransactionID
		tags["currency"] = p.Purchase.Revenue.Currency
	case "refund":
		if p.Refund.FunnelID != nil {
			fields["funnel_id"] = *p.Refund.FunnelID
		}
		fields["product_ids"] = strings.Join(p.Refund.ProductIds, ",")
		fields["revenue"] = p.Refund.Revenue.Amount
		fields["transaction_id"] = p.Refund.TransactionID
		tags["currency"] = p.Refund.Revenue.Currency
	default:
		return fmt.Errorf("unhandled commerce step: %s", p.Step)
	}

	t, err := time.Parse(time.RFC3339, p.System.Time)
	if err != nil {
		return err
	}

	tags, fields = c.payloadToTagsFields(p.System, p.User, tags, fields)
	if err = c.pushInternal(ctx, model.TableCommerce, t, tags, fields); err != nil {
		return err
	}

	return nil
}

// Event runs the event action.
func (c *TrackController) Event(ctx context.Context, p *track.Event2) (err error) {
	err = c.checkPropertyToken(p.System)
	if err != nil {
		return err
	}

	tags := map[string]string{}
	if p.RempEventID != nil {
		tags["remp_event_id"] = *p.RempEventID
	} else {
		// remp_event_id is required, if not provided, generate one
		tags["remp_event_id"] = uuid.New().String()
	}
	if p.ArticleID != nil {
		tags["article_id"] = *p.ArticleID
	}
	fields := map[string]interface{}{}
	if p.Value != nil {
		fields["value"] = *p.Value
	}
	for key, val := range p.Tags {
		tags[key] = val
	}
	for key, val := range p.Fields {
		fields[key] = val
	}

	tags, fields = c.payloadToTagsFields(p.System, p.User, tags, fields)

	tags["category"] = p.Category
	tags["action"] = p.Action

	t, err := time.Parse(time.RFC3339, p.System.Time)
	if err != nil {
		return err
	}

	if err = c.pushInternal(ctx, model.TableEvents, t, tags, fields); err != nil {
		return err
	}

	return nil
}

// Pageview runs the pageview action.
func (c *TrackController) Pageview(ctx context.Context, p *track.Pageview2) (err error) {
	err = c.checkPropertyToken(p.System)
	if err != nil {
		return err
	}

	tags := map[string]string{}
	fields := map[string]interface{}{}

	var table string
	switch p.Action {
	case model.ActionPageviewLoad:
		tags["action"] = model.ActionPageviewLoad
		table = model.TablePageviews
	case model.ActionPageviewTimespent:
		tags["action"] = model.ActionPageviewTimespent
		table = model.TableTimespent
		if p.Timespent != nil {
			fields["timespent"] = p.Timespent.Seconds
			// limit maximum timespent; filters out broken tracking of open articles (forgotten browser window on different workspace/monitor)
			if c.TimespentLimit > 0 && p.Timespent.Seconds > c.TimespentLimit {
				fields["timespent"] = c.TimespentLimit
			}
			fields["unload"] = false
			if p.Timespent.Unload != nil && *p.Timespent.Unload {
				fields["unload"] = true
			}
		}
	case model.ActionPageviewProgress:
		tags["action"] = model.ActionPageviewProgress
		table = model.TableProgress
		if p.Progress != nil {
			fields["page_progress"] = p.Progress.PageRatio
			if p.Progress.ArticleRatio != nil {
				fields["article_progress"] = *p.Progress.ArticleRatio
			}
			fields["unload"] = false
			if p.Progress.Unload != nil && *p.Progress.Unload {
				fields["unload"] = true
			}
		}
	default:
		return track.MakeBadRequest(fmt.Errorf("incorrect pageview action [%s]", p.Action))
	}

	if p.Article != nil {
		at, av := articleValues(p.Article)
		for key, tag := range at {
			tags[key] = tag
		}
		for key, val := range av {
			fields[key] = val
		}
	} else {
		fields[model.FlagArticle] = false
	}

	tags["category"] = model.CategoryPageview

	t, err := time.Parse(time.RFC3339, p.System.Time)
	if err != nil {
		return err
	}

	tags, fields = c.payloadToTagsFields(p.System, p.User, tags, fields)
	if err = c.pushInternal(ctx, table, t, tags, fields); err != nil {
		return err
	}

	return nil
}

// Entity runs the entity action.
func (c *TrackController) Entity(ctx context.Context, p *track.Entity2) (err error) {
	err = c.checkPropertyToken(p.System)
	if err != nil {
		return err
	}

	// try to get entity schema
	schema, ok, err := c.EntitySchemaStorage.Get(p.EntityDef.Name)
	if err != nil {
		return err
	}
	if !ok {
		return track.MakeBadRequest(fmt.Errorf("can't find entity schema for entity: %s", p.EntityDef.Name))
	}

	// validate entity schema
	err = (*EntitySchema)(schema).Validate(p)
	if err != nil {
		return track.MakeBadRequest(errors.Wrap(err, "schema validation failed"))
	}

	fields := p.EntityDef.Data
	fields["remp_entity_id"] = p.EntityDef.ID

	t, err := time.Parse(time.RFC3339, p.System.Time)
	if err != nil {
		return err
	}

	if err = c.pushInternal(ctx, model.TableEntities, t, nil, fields); err != nil {
		return err
	}

	return nil
}

// Impressions runs the impressions action.
func (c *TrackController) Impressions(ctx context.Context, p *track.Impressions2) (err error) {
	var t time.Time
	if p.T == nil {
		t = time.Now()
	} else {
		t, err = time.Parse(time.RFC3339, *p.T)
		if err != nil {
			return err
		}
	}

	for _, impressionData := range p.D {
		tags := map[string]string{}
		fields := map[string]interface{}{}

		// Add impression-specific data
		tags["block"] = impressionData.Bl
		tags["type"] = impressionData.Tp
		tags["remp_pageview_id"] = p.Rpid
		fields["element_ids"] = impressionData.Eid

		// Add concatenated tag, serving as ID
		tags["remp_pageview_id_block_type"] = fmt.Sprintf("%s_%s_%s", p.Rpid, impressionData.Bl, impressionData.Tp)

		if err = c.pushInternal(ctx, model.TableImpressions, t, tags, fields); err != nil {
			return err
		}
	}

	return nil
}

func articleValues(article *track.Article) (map[string]string, map[string]interface{}) {
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

	if article.ContentType != nil {
		values["content_type"] = *article.ContentType
	} else {
		values["content_type"] = "article"
	}
	values[model.FlagArticle] = values["content_type"] == "article"

	return tags, values
}

func (c *TrackController) payloadToTagsFields(system *track.System, user *track.User,
	tags map[string]string, fields map[string]interface{}) (map[string]string, map[string]interface{}) {

	fields["token"] = system.PropertyToken

	if user != nil {
		if user.IPAddress != nil {
			fields["ip"] = *user.IPAddress
		}
		if user.URL != nil {
			fields["url"] = *user.URL
		}
		if user.CanonicalURL != nil {
			fields["canonical_url"] = *user.CanonicalURL
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
			parsedRefererURL, err := url.Parse(*user.Referer)
			if err != nil {
				tags["derived_referer_medium"] = "unknown"
			} else {
				tags["derived_referer_host_with_path"] = fmt.Sprintf("%s://%s%s", parsedRefererURL.Scheme, parsedRefererURL.Host, parsedRefererURL.Path)

				parsedRef := refererparser.Parse(*user.Referer)

				refResolver := RefererResolver{
					Referer:       parsedRef,
					InternalHosts: c.InternalHosts,
				}
				// Check for internal traffic
				if user.URL != nil {
					refResolver.SetCurrent(*user.URL)
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

			// utm_ parameters parsing is deprecated and will be removed in the future

			if user.Source.RtmSource != nil {
				tags["rtm_source"] = *user.Source.RtmSource
			} else if user.Source.UtmSource != nil { // deprecated, will be removed in the future
				tags["rtm_source"] = *user.Source.UtmSource
			}

			if user.Source.RtmMedium != nil {
				tags["rtm_medium"] = *user.Source.RtmMedium

				// Rewrite referer medium in case of email RTM medium
				if *user.Source.RtmMedium == "email" {
					tags["derived_referer_medium"] = "email"
				}
			} else if user.Source.UtmMedium != nil { // deprecated, will be removed in the future
				tags["rtm_medium"] = *user.Source.UtmMedium

				// Rewrite referer medium in case of email UTM
				if *user.Source.UtmMedium == "email" {
					tags["derived_referer_medium"] = "email"
				}
			}

			if user.Source.RtmCampaign != nil {
				tags["rtm_campaign"] = *user.Source.RtmCampaign
			} else if user.Source.UtmCampaign != nil { // deprecated, will be removed in the future
				tags["rtm_campaign"] = *user.Source.UtmCampaign
			}

			if user.Source.RtmContent != nil {
				tags["rtm_content"] = *user.Source.RtmContent
			} else if user.Source.UtmContent != nil { // deprecated, will be removed in the future
				tags["rtm_content"] = *user.Source.UtmContent
			}

			if user.Source.RtmVariant != nil {
				tags["rtm_variant"] = *user.Source.RtmVariant
			} else if user.Source.BannerVariant != nil { // deprecated, will be removed in the future
				tags["rtm_variant"] = *user.Source.BannerVariant
			}
		}

		// If explicit referer medium is set, override implicit referer medium
		if user.ExplicitRefererMedium != nil {
			tags["derived_referer_medium"] = *user.ExplicitRefererMedium
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
		if user.SubscriptionIds != nil {
			fields["subscription_ids"] = user.SubscriptionIds
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
	} else {
		fields["signed_in"] = false
	}

	return tags, fields
}

// pushInternal pushes new event to message broker
func (c *TrackController) pushInternal(ctx context.Context, table string, time time.Time, tags map[string]string, fields map[string]interface{}) error {

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

	c.EventProducer.Produce(ctx, table, time, data)

	return nil
}
