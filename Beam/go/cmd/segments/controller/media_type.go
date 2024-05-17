package controller

import (
	"beam/cmd/segments/gen/commerce"
	"beam/cmd/segments/gen/concurrents"
	"beam/cmd/segments/gen/events"
	"beam/cmd/segments/gen/pageviews"
	"beam/cmd/segments/gen/segments"
	"beam/model"
	"encoding/json"
	"fmt"
	"strconv"
	"strings"
	"time"

	"github.com/gofrs/uuid"
	"github.com/pkg/errors"
)

// Segment represents segment information stored in storage.
type Segment model.Segment

// SegmentCollection is the collection of Segments.
type SegmentCollection model.SegmentCollection

// SegmentGroup represents segment group information stored in storage.
type SegmentGroup model.SegmentGroup

// SegmentGroupCollection is the collection of SegmentsGroups.
type SegmentGroupCollection model.SegmentGroupCollection

// SegmentBlueprintTable represents blueprint of one segment table.
type SegmentBlueprintTable model.SegmentBlueprintTable

// SegmentBlueprintTableCollection is the collection of SegmentBlueprintTable.
type SegmentBlueprintTableCollection model.SegmentBlueprintTableCollection

// SegmentBlueprintTableCriterion represents criterion of one field of table available for segment.
type SegmentBlueprintTableCriterion model.SegmentBlueprintTableCriterion

// SegmentBlueprintTableCriterionCollection is the collection of SegmentBlueprintTableCriterion.
type SegmentBlueprintTableCriterionCollection model.SegmentBlueprintTableCriterionCollection

// Event represent tracked generic events data.
type Event model.Event

// EventRowCollection is the collection of Events bucketed by tags.
type EventRowCollection model.EventRowCollection

// Commerce represent tracked commerce events data.
type Commerce model.Commerce

// CommerceRowCollection is the collection of Commerce events bucketed by tags.
type CommerceRowCollection model.CommerceRowCollection

// Pageview represent tracked Pageview events data.
type Pageview model.Pageview

// PageviewRow represent tracked pageview data with tags.
type PageviewRow model.PageviewRow

// PageviewRowCollection is the collection of PageviewRow events.
type PageviewRowCollection model.PageviewRowCollection

// SegmentCache represent cache object for count of events of SegmentRules.
type SegmentCache model.SegmentCache

// TimeHistogramItem represent row with date time histogram data.
type TimeHistogramItem model.TimeHistogramItem

// CountHistogramItem represent row with count-based histogram data.
type CountHistogramItem model.CountHistogramItem

// CountRow represent row with count result.
type CountRow model.CountRow

// CountRowCollection is the collection of count rows.
type CountRowCollection model.CountRowCollection

// SumRow represent row with sum result.
type SumRow model.SumRow

// SumRowCollection is the collection of sum rows.
type SumRowCollection model.SumRowCollection

// AvgRow represent row with sum result.
type AvgRow model.AvgRow

// AvgRowCollection is the collection of sum rows.
type AvgRowCollection model.AvgRowCollection

// ToMediaType converts internal Segment representation to application one.
func (s *Segment) ToMediaType() (*segments.Segment, error) {
	mt := &segments.Segment{
		ID:    s.ID,
		Code:  s.Code,
		Name:  s.Name,
		Group: (*SegmentGroup)(s.Group).ToMediaType(),
	}

	if s.Criteria.Valid {
		err := json.Unmarshal([]byte(s.Criteria.String), &mt.Criteria)
		if err != nil {
			return nil, errors.Wrap(err, "unable to unmarshal segments `criteria`")
		}
	}

	return mt, nil
}

// ToMediaType converts internal SegmentCollection representation to application one.
func (sc SegmentCollection) ToMediaType() (segments.SegmentCollection, error) {
	mt := segments.SegmentCollection{}
	for _, s := range sc {
		smt, err := (*Segment)(s).ToMediaType()
		if err != nil {
			return nil, errors.Wrap(err, fmt.Sprintf("unable to unmarshal segment with ID [%d]", s.ID))
		}
		mt = append(mt, smt)
	}
	return mt, nil
}

// ToTinyMediaType converts internal Segment representation to tiny view of application segment.
func (s *Segment) ToTinyMediaType() *segments.Segment {
	mt := &segments.Segment{
		ID:    s.ID,
		Code:  s.Code,
		Name:  s.Name,
		Group: (*SegmentGroup)(s.Group).ToMediaType(),
	}
	return mt
}

// ToExtendedMediaType converts internal Segment representation to extended view of application segment.
func (s *Segment) ToExtendedMediaType(segmentURL string) *segments.Segment {
	url := strings.Replace(segmentURL, "{segment_id}", strconv.Itoa(s.ID), -1)
	mt := &segments.Segment{
		ID:    s.ID,
		Code:  s.Code,
		Name:  s.Name,
		Group: (*SegmentGroup)(s.Group).ToMediaType(),
		URL:   &url,
	}
	return mt
}

// ToSegmenterMediaType converts internal Segment representation to segmenter view of application segment.
func (s *Segment) ToSegmenterMediaType() (*segments.Segment, error) {
	tn := "users"
	mt := &segments.Segment{
		ID:        s.ID,
		Code:      s.Code,
		Name:      s.Name,
		TableName: &tn,
		Group:     (*SegmentGroup)(s.Group).ToMediaType(),
		GroupID:   &s.Group.ID,
	}
	if s.Criteria.Valid {
		err := json.Unmarshal([]byte(s.Criteria.String), &mt.Criteria)
		if err != nil {
			return nil, errors.Wrap(err, "unable to unmarshal segments `criteria`")
		}
	}
	return mt, nil
}

// ToTinyMediaType converts internal SegmentCollection representation to tiny view of application segment collection.
func (sc SegmentCollection) ToTinyMediaType() segments.SegmentCollection {
	mt := segments.SegmentCollection{}
	for _, s := range sc {
		mt = append(mt, (*Segment)(s).ToTinyMediaType())
	}
	return mt
}

// ToExtendedMediaType converts internal SegmentCollection representation to extended view of application segment collection.
func (sc SegmentCollection) ToExtendedMediaType(segmentURL string) segments.SegmentCollection {
	mt := segments.SegmentCollection{}
	for _, s := range sc {
		mt = append(mt, (*Segment)(s).ToExtendedMediaType(segmentURL))
	}
	return mt
}

// ToMediaType converts internal Segment representation to application one.
func (sg *SegmentGroup) ToMediaType() *segments.SegmentGroup {
	return &segments.SegmentGroup{
		ID:      sg.ID,
		Name:    sg.Name,
		Sorting: sg.Sorting,
	}
}

// ToMediaType converts internal SegmentCollection representation to application one.
func (sgc SegmentGroupCollection) ToMediaType() segments.SegmentGroupCollection {
	mt := segments.SegmentGroupCollection{}
	for _, sg := range sgc {
		mt = append(mt, (*SegmentGroup)(sg).ToMediaType())
	}
	return mt
}

// ToMediaType converts internal SegmentBlueprint representation to application one.
func (sbt *SegmentBlueprintTable) ToMediaType() *segments.SegmentBlueprintTable {
	return &segments.SegmentBlueprintTable{
		Table:    sbt.Table,
		Fields:   sbt.Fields,
		Criteria: (SegmentBlueprintTableCriterionCollection)(sbt.Criteria).ToMediaType(),
	}
}

// ToMediaType converts internal SegmentBlueprintTableCollection representation to application one.
func (sbtc SegmentBlueprintTableCollection) ToMediaType() segments.SegmentBlueprintTableCollection {
	mt := segments.SegmentBlueprintTableCollection{}
	for _, sbt := range sbtc {
		mt = append(mt, (*SegmentBlueprintTable)(sbt).ToMediaType())
	}

	return mt
}

// ToMediaType converts internal SegmentBlueprintTableCriterion representation to application one.
func (sbc *SegmentBlueprintTableCriterion) ToMediaType() *segments.SegmentBlueprintTableCriterion {
	return &segments.SegmentBlueprintTableCriterion{
		Key:    sbc.Key,
		Label:  sbc.Label,
		Params: sbc.Params,
		Fields: sbc.Fields,
	}
}

// ToMediaType converts internal SegmentBlueprintTableCriterionCollection representation to application one.
func (sbcc SegmentBlueprintTableCriterionCollection) ToMediaType() segments.SegmentBlueprintTableCriterionCollection {
	mt := segments.SegmentBlueprintTableCriterionCollection{}
	for _, sbc := range sbcc {
		mt = append(mt, (*SegmentBlueprintTableCriterion)(sbc).ToMediaType())
	}

	return mt
}

// ToMediaType converts internal Event representation to application one.
func (e *Event) ToMediaType() (*events.Event, error) {
	token, err := uuid.FromString(e.Token)
	if err != nil {
		return nil, errors.Wrap(err, "unable to parse property token as UUID")
	}
	event := &events.Event{
		Category: e.Category,
		Action:   e.Action,
		System: &events.System{
			Time:          e.Time.Format(time.RFC3339),
			PropertyToken: token.String(),
		},
		User: &events.User{},
	}
	if e.ID != "" {
		event.ID = e.ID
	}
	if e.IP != "" {
		event.User.IPAddress = &e.IP
	}
	if e.UserID != "" {
		event.User.ID = &e.UserID
	}
	if e.URL != "" {
		event.User.URL = &e.URL
	}
	if e.ArticleID != "" {
		event.ArticleID = &e.ArticleID
	}
	if e.UserAgent != "" {
		event.User.UserAgent = &e.UserAgent
	}

	if e.RtmSource != "" {
		event.RtmSource = &e.RtmSource
		event.UtmSource = &e.RtmSource
	} else if e.UtmSource != "" { // Deprecated, will be removed
		event.UtmSource = &e.UtmSource
		event.RtmSource = &e.UtmSource
	}

	if e.RtmCampaign != "" {
		event.RtmCampaign = &e.RtmCampaign
		event.UtmCampaign = &e.RtmCampaign
	} else if e.UtmCampaign != "" { // Deprecated, will be removed
		event.UtmCampaign = &e.UtmCampaign
		event.RtmCampaign = &e.UtmCampaign
	}

	if e.RtmMedium != "" {
		event.RtmMedium = &e.RtmMedium
		event.UtmMedium = &e.RtmMedium
	} else if e.UtmMedium != "" { // Deprecated, will be removed
		event.UtmMedium = &e.UtmMedium
		event.RtmMedium = &e.UtmMedium
	}

	if e.RtmContent != "" {
		event.RtmContent = &e.RtmContent
		event.UtmContent = &e.RtmContent
	} else if e.UtmContent != "" { // Deprecated, will be removed
		event.UtmContent = &e.UtmContent
		event.RtmContent = &e.UtmContent
	}
	return event, nil
}

// ToMediaType converts internal EventCollection representation to application one.
func (erc EventRowCollection) ToMediaType() (events.EventsCollection, error) {
	mt := events.EventsCollection{}
	for _, er := range erc {
		ev := &events.Events{
			Tags: er.Tags,
		}
		for _, p := range er.Events {
			event, err := (*Event)(p).ToMediaType()
			if err != nil {
				return nil, err
			}
			ev.Events = append(ev.Events, event)
		}
		mt = append(mt, ev)
	}
	return mt, nil
}

// ToMediaType converts internal Commerce representation to application one.
func (c *Commerce) ToMediaType() (*commerce.Commerce, error) {
	token, err := uuid.FromString(c.Token)
	if err != nil {
		return nil, errors.Wrap(err, "unable to parse property token as UUID")
	}
	event := &commerce.Commerce{
		Step: c.Step,
		System: &commerce.System{
			Time:          c.Time.Format(time.RFC3339),
			PropertyToken: token.String(),
		},
		User: &commerce.User{},
		//Details: &app.CommerceDetails{},
		Source: &commerce.Source{},
	}
	if c.ID != "" {
		event.ID = c.ID
	}
	if c.IP != "" {
		event.User.IPAddress = &c.IP
	}
	if c.BrowserID != "" {
		event.User.BrowserID = &c.BrowserID
	}
	if c.UserID != "" {
		event.User.ID = &c.UserID
	}
	if c.URL != "" {
		event.User.URL = &c.URL
	}
	if c.UserAgent != "" {
		event.User.UserAgent = &c.UserAgent
	}

	// Note: During the deprecation period, API returns both utm_ and rtm_parameters

	if c.RtmCampaign != "" {
		event.Source.RtmCampaign = &c.RtmCampaign
		event.Source.UtmCampaign = &c.RtmCampaign
	} else if c.UtmCampaign != "" { // Deprecated, will be removed
		event.Source.UtmCampaign = &c.UtmCampaign
		event.Source.RtmCampaign = &c.UtmCampaign
	}

	if c.RtmContent != "" {
		event.Source.RtmContent = &c.RtmContent
		event.Source.UtmContent = &c.RtmContent
	} else if c.UtmContent != "" { // Deprecated, will be removed
		event.Source.UtmContent = &c.UtmContent
		event.Source.RtmContent = &c.UtmContent
	}

	if c.RtmMedium != "" {
		event.Source.RtmMedium = &c.RtmMedium
		event.Source.UtmMedium = &c.RtmMedium
	} else if c.UtmMedium != "" { // Deprecated, will be removed
		event.Source.UtmMedium = &c.UtmMedium
		event.Source.RtmMedium = &c.UtmMedium
	}

	if c.RtmSource != "" {
		event.Source.RtmSource = &c.RtmSource
		event.Source.UtmSource = &c.RtmSource
	} else if c.UtmSource != "" { // Deprecated, will be removed
		event.Source.UtmSource = &c.UtmSource
		event.Source.RtmSource = &c.UtmSource
	}

	// Commerce details
	if c.Step == "checkout" {
		event.Checkout = &commerce.CommerceCheckout{
			FunnelID: c.FunnelID,
		}
	} else if c.Step == "payment" {
		event.Payment = fillCommercePayment(c)
	} else if c.Step == "purchase" {
		event.Purchase = fillCommercePayment(c)
	} else if c.Step == "refund" {
		event.Refund = fillCommercePayment(c)
	}

	return event, nil
}

func fillCommercePayment(c *Commerce) *commerce.CommercePayment {
	cp := &commerce.CommercePayment{
		FunnelID: c.FunnelID,
	}
	if c.ProductIDs != "" {
		cp.ProductIds = strings.Split(c.ProductIDs, ",")
	}
	if c.Revenue != 0 && c.Currency != "" {
		cp.Revenue = &commerce.Revenue{
			Amount:   c.Revenue,
			Currency: c.Currency,
		}
	}
	return cp
}

// ToMediaType converts internal CommerceCollection representation to application one.
func (crc CommerceRowCollection) ToMediaType() (commerce.CommercesCollection, error) {
	mt := commerce.CommercesCollection{}
	for _, cr := range crc {
		commerces := &commerce.Commerces{
			Tags: cr.Tags,
		}
		for _, p := range cr.Commerces {
			cm, err := (*Commerce)(p).ToMediaType()
			if err != nil {
				return nil, err
			}
			commerces.Commerces = append(commerces.Commerces, cm)
		}
		mt = append(mt, commerces)
	}
	return mt, nil
}

// ToMediaType converts internal PageviewRow representation to application one.
func (p *Pageview) ToMediaType() (*pageviews.Pageview, error) {
	token, err := uuid.FromString(p.Token)
	if err != nil {
		return nil, errors.Wrap(err, "unable to parse property token as UUID")
	}

	// PageviewRow, generic user and system data
	pageview := &pageviews.Pageview{
		System: &pageviews.System{
			Time:          p.Time.Format(time.RFC3339),
			PropertyToken: token.String(),
		},
		User: &pageviews.User{
			Source:         &pageviews.Source{},
			RempPageviewID: p.ID,
		},
	}

	if p.ID != "" {
		pageview.ID = &p.ID
	}

	// article data
	if p.ArticleID != "" {
		pageview.Article = &pageviews.Article{
			ID:     p.ArticleID,
			Locked: &p.ArticleLocked,
		}
		if p.AuthorID != "" {
			pageview.Article.AuthorID = &p.AuthorID
		}
		if p.ContentType != "" {
			pageview.Article.ContentType = &p.ContentType
		}
	}

	// user data
	if p.UserID != "" {
		pageview.User.ID = &p.UserID
	}

	// optional tracked params
	// Note: During deprecation period, both RTM and UTM parameters are returned
	if p.RtmSource != "" {
		pageview.User.Source.RtmSource = &p.RtmSource
		pageview.User.Source.UtmSource = &p.RtmSource
	} else if p.UTMSource != "" { // Deprecated, will be removed
		pageview.User.Source.UtmSource = &p.UTMSource
		pageview.User.Source.RtmSource = &p.UTMSource
	}

	if p.RtmMedium != "" {
		pageview.User.Source.RtmMedium = &p.RtmMedium
		pageview.User.Source.UtmMedium = &p.RtmMedium
	} else if p.UTMMedium != "" { // Deprecated, will be removed
		pageview.User.Source.UtmMedium = &p.UTMMedium
		pageview.User.Source.RtmMedium = &p.UTMMedium
	}

	if p.RtmCampaign != "" {
		pageview.User.Source.RtmCampaign = &p.RtmCampaign
		pageview.User.Source.UtmCampaign = &p.RtmCampaign
	} else if p.UTMCampaign != "" { // Deprecated, will be removed
		pageview.User.Source.UtmCampaign = &p.UTMCampaign
		pageview.User.Source.RtmCampaign = &p.UTMCampaign
	}

	if p.RtmContent != "" {
		pageview.User.Source.RtmContent = &p.RtmContent
		pageview.User.Source.UtmContent = &p.RtmContent
	} else if p.UTMContent != "" { // Deprecated, will be removed
		pageview.User.Source.UtmContent = &p.UTMContent
		pageview.User.Source.RtmContent = &p.UTMContent
	}

	if p.IP != "" {
		pageview.User.IPAddress = &p.IP
	}
	if p.URL != "" {
		pageview.User.URL = &p.URL
	}
	if p.CanonicalURL != "" {
		pageview.User.CanonicalURL = &p.CanonicalURL
	}
	if p.Referer != "" {
		pageview.User.Referer = &p.Referer
	}
	if p.DerivedRefererMedium != "" {
		pageview.User.DerivedRefererMedium = &p.DerivedRefererMedium
	}
	if p.DerivedRefererSource != "" {
		pageview.User.DerivedRefererSource = &p.DerivedRefererSource
	}
	if p.DerivedRefererHostWithPath != "" {
		pageview.User.DerivedRefererHostWithPath = &p.DerivedRefererHostWithPath
	}
	if p.BrowserID != "" {
		pageview.User.BrowserID = &p.BrowserID
	}
	if p.SessionID != "" {
		pageview.User.RempSessionID = &p.SessionID
	}
	if p.UserAgent != "" {
		pageview.User.UserAgent = &p.UserAgent
	}
	if p.Timespent != 0 {
		pageview.User.Timespent = &p.Timespent
	}
	if p.PageProgress != 0.0 {
		pageview.User.PageProgress = &p.PageProgress
	}
	if p.ArticleProgress != 0.0 {
		pageview.User.ArticleProgress = &p.ArticleProgress
	}

	return pageview, nil
}

// ToMediaType converts internal PageviewRowCollection representation to application one.
func (prc PageviewRowCollection) ToMediaType() (pageviews.PageviewsCollection, error) {
	mt := pageviews.PageviewsCollection{}
	for _, rc := range prc {
		pr := &pageviews.Pageviews{
			Tags: rc.Tags,
		}
		for _, p := range rc.Pageviews {
			pageview, err := (*Pageview)(p).ToMediaType()
			if err != nil {
				return nil, err
			}
			pr.Pageviews = append(pr.Pageviews, pageview)
		}
		mt = append(mt, pr)
	}
	return mt, nil
}

// ToMediaType converts internal PageviewRowCollection representation to application one.
func (sc SegmentCache) ToMediaType() map[int]*segments.SegmentRuleCache {
	mt := make(map[int]*segments.SegmentRuleCache)
	for key, c := range sc {
		mt[key] = &segments.SegmentRuleCache{
			S: c.SyncedAt.Format(time.RFC3339),
			C: c.Count,
		}
	}
	return mt
}

// ToMediaType converts internal TimeHistogramItem representation to application one.
func (thi TimeHistogramItem) ToMediaType() *pageviews.TimeHistogram {
	th := &pageviews.TimeHistogram{
		Time:  thi.Time.Format(time.RFC3339),
		Value: thi.Value,
	}
	return th
}

// ToEventsMediaType converts internal TimeHistogramItem representation to application one.
func (thi TimeHistogramItem) ToEventsMediaType() *events.TimeHistogram {
	th := &events.TimeHistogram{
		Time:  thi.Time.Format(time.RFC3339),
		Value: thi.Value,
	}
	return th
}

// ToConcurrentsMediaType converts internal TimeHistogramItem representation to application one.
func (thi TimeHistogramItem) ToConcurrentsMediaType() *concurrents.TimeHistogram {
	th := &concurrents.TimeHistogram{
		Time:  thi.Time.Format(time.RFC3339),
		Value: thi.Value,
	}
	return th
}

// ToCommerceMediaType converts internal TimeHistogramItem representation to application one.
func (thi TimeHistogramItem) ToCommerceMediaType() *commerce.TimeHistogram {
	th := &commerce.TimeHistogram{
		Time:  thi.Time.Format(time.RFC3339),
		Value: thi.Value,
	}
	return th
}

// ToEventsMediaType converts internal CountHistogramItem representation to application one.
func (chi CountHistogramItem) ToEventsMediaType() *events.CountHistogram {
	th := &events.CountHistogram{
		BucketKey: chi.BucketKey,
		Value:     chi.Value,
	}
	return th
}

// ToConcurrentsMediaType converts internal CountHistogramItem representation to application one.
func (chi CountHistogramItem) ToConcurrentsMediaType() *concurrents.CountHistogram {
	th := &concurrents.CountHistogram{
		BucketKey: chi.BucketKey,
		Value:     chi.Value,
	}
	return th
}

// ToCommerceMediaType converts internal CountHistogramItem representation to application one.
func (chi CountHistogramItem) ToCommerceMediaType() *commerce.CountHistogram {
	th := &commerce.CountHistogram{
		BucketKey: chi.BucketKey,
		Value:     chi.Value,
	}
	return th
}

// ToMediaType converts internal CountHistogramItem representation to application one.
func (chi CountHistogramItem) ToMediaType() *pageviews.CountHistogram {
	th := &pageviews.CountHistogram{
		BucketKey: chi.BucketKey,
		Value:     chi.Value,
	}
	return th
}

// ToMediaType converts internal CountRow representation to application one.
func (cr CountRow) ToMediaType() *pageviews.Count {
	thc := pageviews.TimeHistogramCollection{}
	for _, c := range cr.TimeHistogram {
		thi := (TimeHistogramItem)(c).ToMediaType()
		thc = append(thc, thi)
	}

	chc := pageviews.CountHistogramCollection{}
	for _, c := range cr.CountHistogram {
		chi := (CountHistogramItem)(c).ToMediaType()
		chc = append(chc, chi)
	}

	if len(cr.Tags) == 0 {
		cr.Tags = nil
	}

	mt := &pageviews.Count{
		Count:          cr.Count,
		Tags:           cr.Tags,
		TimeHistogram:  thc,
		CountHistogram: chc,
	}

	return mt
}

// ToEventsMediaType converts internal CountRow representation to application one.
func (cr CountRow) ToEventsMediaType() *events.Count {
	thc := events.TimeHistogramCollection{}
	for _, c := range cr.TimeHistogram {
		thi := (TimeHistogramItem)(c).ToEventsMediaType()
		thc = append(thc, thi)
	}

	chc := events.CountHistogramCollection{}
	for _, c := range cr.CountHistogram {
		chi := (CountHistogramItem)(c).ToEventsMediaType()
		chc = append(chc, chi)
	}

	if len(cr.Tags) == 0 {
		cr.Tags = nil
	}

	mt := &events.Count{
		Count:          cr.Count,
		Tags:           cr.Tags,
		TimeHistogram:  thc,
		CountHistogram: chc,
	}

	return mt
}

func (cr CountRow) ToConcurrentsMediaType() *concurrents.Count {
	thc := concurrents.TimeHistogramCollection{}
	for _, c := range cr.TimeHistogram {
		thi := (TimeHistogramItem)(c).ToConcurrentsMediaType()
		thc = append(thc, thi)
	}

	chc := concurrents.CountHistogramCollection{}
	for _, c := range cr.CountHistogram {
		chi := (CountHistogramItem)(c).ToConcurrentsMediaType()
		chc = append(chc, chi)
	}

	if len(cr.Tags) == 0 {
		cr.Tags = nil
	}

	mt := &concurrents.Count{
		Count:          cr.Count,
		Tags:           cr.Tags,
		TimeHistogram:  thc,
		CountHistogram: chc,
	}

	return mt
}

// ToCommerceMediaType converts internal CountRow representation to application one.
func (cr CountRow) ToCommerceMediaType() *commerce.Count {
	thc := commerce.TimeHistogramCollection{}
	for _, c := range cr.TimeHistogram {
		thi := (TimeHistogramItem)(c).ToCommerceMediaType()
		thc = append(thc, thi)
	}

	chc := commerce.CountHistogramCollection{}
	for _, c := range cr.CountHistogram {
		chi := (CountHistogramItem)(c).ToCommerceMediaType()
		chc = append(chc, chi)
	}

	if len(cr.Tags) == 0 {
		cr.Tags = nil
	}

	mt := &commerce.Count{
		Count:          cr.Count,
		Tags:           cr.Tags,
		TimeHistogram:  thc,
		CountHistogram: chc,
	}

	return mt
}

// ToMediaType converts internal CountRowCollection representation to application one.
func (crc CountRowCollection) ToMediaType() pageviews.CountCollection {
	mt := pageviews.CountCollection{}
	for _, c := range crc {
		mtc := (CountRow)(c).ToMediaType()
		mt = append(mt, mtc)
	}
	return mt
}

// ToEventsMediaType converts internal CountRowCollection representation to application one.
func (crc CountRowCollection) ToEventsMediaType() events.CountCollection {
	mt := events.CountCollection{}
	for _, c := range crc {
		mtc := (CountRow)(c).ToEventsMediaType()
		mt = append(mt, mtc)
	}
	return mt
}

// ToCommerceMediaType converts internal CountRowCollection representation to application one.
func (crc CountRowCollection) ToCommerceMediaType() commerce.CountCollection {
	mt := commerce.CountCollection{}
	for _, c := range crc {
		mtc := (CountRow)(c).ToCommerceMediaType()
		mt = append(mt, mtc)
	}
	return mt
}

// ToConcurrentsMediaType converts internal CountRowCollection representation to application one.
func (crc CountRowCollection) ToConcurrentsMediaType() concurrents.CountCollection {
	mt := concurrents.CountCollection{}
	for _, c := range crc {
		mtc := (CountRow)(c).ToConcurrentsMediaType()
		mt = append(mt, mtc)
	}
	return mt
}

// ToMediaType converts internal SumRow representation to application one.
func (sr SumRow) ToMediaType() *pageviews.Sum {
	thc := pageviews.TimeHistogramCollection{}

	for _, c := range sr.TimeHistogram {
		hi := (TimeHistogramItem)(c).ToMediaType()
		thc = append(thc, hi)
	}

	if len(sr.Tags) == 0 {
		sr.Tags = nil
	}

	mt := &pageviews.Sum{
		Sum:           sr.Sum,
		Tags:          sr.Tags,
		TimeHistogram: thc,
	}
	return mt
}

func (sr SumRow) ToCommerceMediaType() *commerce.Sum {
	thc := commerce.TimeHistogramCollection{}

	for _, c := range sr.TimeHistogram {
		hi := (TimeHistogramItem)(c).ToCommerceMediaType()
		thc = append(thc, hi)
	}

	if len(sr.Tags) == 0 {
		sr.Tags = nil
	}

	mt := &commerce.Sum{
		Sum:           sr.Sum,
		Tags:          sr.Tags,
		TimeHistogram: thc,
	}
	return mt
}

// ToMediaType converts internal SumRowCollection representation to application one.
func (src SumRowCollection) ToMediaType() pageviews.SumCollection {
	mt := pageviews.SumCollection{}
	for _, c := range src {
		mtc := (SumRow)(c).ToMediaType()
		mt = append(mt, mtc)
	}
	return mt
}

// ToCommerceMediaType converts internal SumRowCollection representation to application one.
func (src SumRowCollection) ToCommerceMediaType() commerce.SumCollection {
	mt := commerce.SumCollection{}
	for _, c := range src {
		mtc := (SumRow)(c).ToCommerceMediaType()
		mt = append(mt, mtc)
	}
	return mt
}

// ToMediaType converts internal AvgRow representation to application one.
func (ar AvgRow) ToMediaType() *pageviews.Avg {
	thc := pageviews.TimeHistogramCollection{}

	for _, c := range ar.TimeHistogram {
		hi := (TimeHistogramItem)(c).ToMediaType()
		thc = append(thc, hi)
	}

	if len(ar.Tags) == 0 {
		ar.Tags = nil
	}

	mt := &pageviews.Avg{
		Avg:           ar.Avg,
		Tags:          ar.Tags,
		TimeHistogram: thc,
	}
	return mt
}

// ToMediaType converts internal AvgRowCollection representation to application one.
func (arc AvgRowCollection) ToMediaType() pageviews.AvgCollection {
	mt := pageviews.AvgCollection{}
	for _, c := range arc {
		mtc := (AvgRow)(c).ToMediaType()
		mt = append(mt, mtc)
	}
	return mt
}
