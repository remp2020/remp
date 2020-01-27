package controller

import (
	"encoding/json"
	"fmt"
	"strconv"
	"strings"

	"github.com/pkg/errors"
	uuid "github.com/satori/go.uuid"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
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
func (s *Segment) ToMediaType() (*app.Segment, error) {
	mt := &app.Segment{
		ID:    s.ID,
		Code:  s.Code,
		Name:  s.Name,
		Group: (*SegmentGroup)(&s.Group).ToMediaType(),
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
func (sc SegmentCollection) ToMediaType() (app.SegmentCollection, error) {
	mt := app.SegmentCollection{}
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
func (s *Segment) ToTinyMediaType() *app.SegmentTiny {
	return &app.SegmentTiny{
		ID:    s.ID,
		Code:  s.Code,
		Name:  s.Name,
		Group: (*SegmentGroup)(&s.Group).ToMediaType(),
	}
}

// ToExtendedMediaType converts internal Segment representation to extended view of application segment.
func (s *Segment) ToExtendedMediaType(segmentURL string) *app.SegmentExtended {
	url := strings.Replace(segmentURL, "{segment_id}", strconv.Itoa(s.ID), -1)
	return &app.SegmentExtended{
		ID:    s.ID,
		Code:  s.Code,
		Name:  s.Name,
		Group: (*SegmentGroup)(&s.Group).ToMediaType(),
		URL:   &url,
	}
}

// ToSegmenterMediaType converts internal Segment representation to segmenter view of application segment.
func (s *Segment) ToSegmenterMediaType() (*app.SegmentSegmenter, error) {
	tn := "users"
	mt := app.SegmentSegmenter{
		ID:        s.ID,
		Code:      s.Code,
		Name:      s.Name,
		TableName: &tn,
		Group:     (*SegmentGroup)(&s.Group).ToMediaType(),
		GroupID:   &s.Group.ID,
	}

	if s.Criteria.Valid {
		err := json.Unmarshal([]byte(s.Criteria.String), &mt.Criteria)
		if err != nil {
			return nil, errors.Wrap(err, "unable to unmarshal segments `criteria`")
		}
	}

	return &mt, nil
}

// ToTinyMediaType converts internal SegmentCollection representation to tiny view of application segment collection.
func (sc SegmentCollection) ToTinyMediaType() app.SegmentTinyCollection {
	mt := app.SegmentTinyCollection{}
	for _, s := range sc {
		mt = append(mt, (*Segment)(s).ToTinyMediaType())
	}
	return mt
}

// ToExtendedMediaType converts internal SegmentCollection representation to extended view of application segment collection.
func (sc SegmentCollection) ToExtendedMediaType(segmentURL string) app.SegmentExtendedCollection {
	mt := app.SegmentExtendedCollection{}
	for _, s := range sc {
		mt = append(mt, (*Segment)(s).ToExtendedMediaType(segmentURL))
	}
	return mt
}

// ToMediaType converts internal Segment representation to application one.
func (sg *SegmentGroup) ToMediaType() *app.SegmentGroup {
	return &app.SegmentGroup{
		ID:      sg.ID,
		Name:    sg.Name,
		Sorting: sg.Sorting,
	}
}

// ToMediaType converts internal SegmentCollection representation to application one.
func (sgc SegmentGroupCollection) ToMediaType() app.SegmentGroupCollection {
	mt := app.SegmentGroupCollection{}
	for _, sg := range sgc {
		mt = append(mt, (*SegmentGroup)(sg).ToMediaType())
	}
	return mt
}

// ToMediaType converts internal SegmentBlueprint representation to application one.
func (sbt *SegmentBlueprintTable) ToMediaType() *app.SegmentBlueprintTable {
	return &app.SegmentBlueprintTable{
		Table:    sbt.Table,
		Fields:   sbt.Fields,
		Criteria: (SegmentBlueprintTableCriterionCollection)(sbt.Criteria).ToMediaType(),
	}
}

// ToMediaType converts internal SegmentBlueprintTableCollection representation to application one.
func (sbtc SegmentBlueprintTableCollection) ToMediaType() app.SegmentBlueprintTableCollection {
	mt := app.SegmentBlueprintTableCollection{}
	for _, sbt := range sbtc {
		mt = append(mt, (*SegmentBlueprintTable)(sbt).ToMediaType())
	}

	return mt
}

// ToMediaType converts internal SegmentBlueprintTableCriterion representation to application one.
func (sbc *SegmentBlueprintTableCriterion) ToMediaType() *app.SegmentBlueprintTableCriterion {
	return &app.SegmentBlueprintTableCriterion{
		Key:    sbc.Key,
		Label:  sbc.Label,
		Params: sbc.Params,
		Fields: sbc.Fields,
	}
}

// ToMediaType converts internal SegmentBlueprintTableCriterionCollection representation to application one.
func (sbcc SegmentBlueprintTableCriterionCollection) ToMediaType() app.SegmentBlueprintTableCriterionCollection {
	mt := app.SegmentBlueprintTableCriterionCollection{}
	for _, sbc := range sbcc {
		mt = append(mt, (*SegmentBlueprintTableCriterion)(sbc).ToMediaType())
	}

	return mt
}

// ToMediaType converts internal Event representation to application one.
func (e *Event) ToMediaType() (*app.Event, error) {
	token, err := uuid.FromString(e.Token)
	if err != nil {
		return nil, errors.Wrap(err, "unable to parse property token as UUID")
	}
	event := &app.Event{
		Category: e.Category,
		Action:   e.Action,
		System: &app.System{
			Time:          e.Time,
			PropertyToken: token,
		},
		User: &app.User{},
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
	if e.UtmSource != "" {
		event.UtmSource = &e.UtmSource
	}
	if e.UtmCampaign != "" {
		event.UtmCampaign = &e.UtmCampaign
	}
	if e.UtmMedium != "" {
		event.UtmMedium = &e.UtmMedium
	}
	if e.UtmContent != "" {
		event.UtmContent = &e.UtmContent
	}
	return event, nil
}

// ToMediaType converts internal EventCollection representation to application one.
func (erc EventRowCollection) ToMediaType() (app.EventsCollection, error) {
	mt := app.EventsCollection{}
	for _, er := range erc {
		events := &app.Events{
			Tags: er.Tags,
		}
		for _, p := range er.Events {
			event, err := (*Event)(p).ToMediaType()
			if err != nil {
				return nil, err
			}
			events.Events = append(events.Events, event)
		}
		mt = append(mt, events)
	}
	return mt, nil
}

// ToMediaType converts internal Commerce representation to application one.
func (c *Commerce) ToMediaType() (*app.Commerce, error) {
	token, err := uuid.FromString(c.Token)
	if err != nil {
		return nil, errors.Wrap(err, "unable to parse property token as UUID")
	}
	event := &app.Commerce{
		Step: c.Step,
		System: &app.System{
			Time:          c.Time,
			PropertyToken: token,
		},
		User: &app.User{},
		// Details: &app.CommerceDetails{},
		Source: &app.Source{},
	}
	if c.ID != "" {
		event.ID = c.ID
	}
	if c.IP != "" {
		event.User.IPAddress = &c.IP
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

	// UTM
	if c.UtmCampaign != "" {
		event.Source.UtmCampaign = &c.UtmCampaign
	}
	if c.UtmContent != "" {
		event.Source.UtmContent = &c.UtmContent
	}
	if c.UtmMedium != "" {
		event.Source.UtmMedium = &c.UtmMedium
	}
	if c.UtmSource != "" {
		event.Source.UtmSource = &c.UtmSource
	}

	// Commerce details
	if c.Step == "checkout" {
		event.Checkout = &app.CommerceCheckout{
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

func fillCommercePayment(c *Commerce) *app.CommercePayment {
	cp := &app.CommercePayment{
		FunnelID: c.FunnelID,
	}
	if c.ProductIDs != "" {
		cp.ProductIds = strings.Split(c.ProductIDs, ",")
	}
	if c.Revenue != 0 && c.Currency != "" {
		cp.Revenue = &app.Revenue{
			Amount:   c.Revenue,
			Currency: c.Currency,
		}
	}
	return cp
}

// ToMediaType converts internal CommerceCollection representation to application one.
func (crc CommerceRowCollection) ToMediaType() (app.CommercesCollection, error) {
	mt := app.CommercesCollection{}
	for _, cr := range crc {
		commerces := &app.Commerces{
			Tags: cr.Tags,
		}
		for _, p := range cr.Commerces {
			commerce, err := (*Commerce)(p).ToMediaType()
			if err != nil {
				return nil, err
			}
			commerces.Commerces = append(commerces.Commerces, commerce)
		}
		mt = append(mt, commerces)
	}
	return mt, nil
}

// ToMediaType converts internal PageviewRow representation to application one.
func (p *Pageview) ToMediaType() (*app.Pageview, error) {
	token, err := uuid.FromString(p.Token)
	if err != nil {
		return nil, errors.Wrap(err, "unable to parse property token as UUID")
	}

	// PageviewRow, generic user and system data
	pageview := &app.Pageview{
		System: &app.System{
			Time:          p.Time,
			PropertyToken: token,
		},
		User: &app.User{
			Source:         &app.Source{},
			RempPageviewID: p.ID,
		},
	}

	if p.ID != "" {
		pageview.ID = &p.ID
	}

	// article data
	if p.ArticleID != "" {
		pageview.Article = &app.Article{
			ID:     p.ArticleID,
			Locked: &p.ArticleLocked,
		}
		if p.AuthorID != "" {
			pageview.Article.AuthorID = &p.AuthorID
		}
	}

	// user data
	if p.UserID != "" {
		pageview.User.ID = &p.UserID
	}

	// optional tracked params
	if p.UTMSource != "" {
		pageview.User.Source.UtmSource = &p.UTMSource
	}
	if p.UTMMedium != "" {
		pageview.User.Source.UtmMedium = &p.UTMMedium
	}
	if p.UTMCampaign != "" {
		pageview.User.Source.UtmCampaign = &p.UTMCampaign
	}
	if p.UTMContent != "" {
		pageview.User.Source.UtmContent = &p.UTMContent
	}

	if p.IP != "" {
		pageview.User.IPAddress = &p.IP
	}
	if p.URL != "" {
		pageview.User.URL = &p.URL
	}
	if p.Referer != "" {
		pageview.User.Referer = &p.Referer
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

	return pageview, nil
}

// ToMediaType converts internal PageviewRowCollection representation to application one.
func (prc PageviewRowCollection) ToMediaType() (app.PageviewsCollection, error) {
	mt := app.PageviewsCollection{}
	for _, rc := range prc {
		pr := &app.Pageviews{
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
func (sc SegmentCache) ToMediaType() map[int]*app.SegmentRuleCache {
	mt := make(map[int]*app.SegmentRuleCache)
	for key, c := range sc {
		mt[key] = &app.SegmentRuleCache{
			S: c.SyncedAt,
			C: c.Count,
		}
	}
	return mt
}

// ToMediaType converts internal TimeHistogramItem representation to application one.
func (thi TimeHistogramItem) ToMediaType() *app.TimeHistogram {
	th := &app.TimeHistogram{
		Time:  thi.Time,
		Value: thi.Value,
	}
	return th
}

// ToMediaType converts internal CountHistogramItem representation to application one.
func (chi CountHistogramItem) ToMediaType() *app.CountHistogram {
	th := &app.CountHistogram{
		BucketKey: chi.BucketKey,
		Value:     chi.Value,
	}
	return th
}

// ToMediaType converts internal CountRow representation to application one.
func (cr CountRow) ToMediaType() *app.Count {
	thc := app.TimeHistogramCollection{}
	for _, c := range cr.TimeHistogram {
		thi := (TimeHistogramItem)(c).ToMediaType()
		thc = append(thc, thi)
	}

	chc := app.CountHistogramCollection{}
	for _, c := range cr.CountHistogram {
		chi := (CountHistogramItem)(c).ToMediaType()
		chc = append(chc, chi)
	}

	if len(cr.Tags) == 0 {
		cr.Tags = nil
	}

	mt := &app.Count{
		Count:          cr.Count,
		Tags:           cr.Tags,
		TimeHistogram:  thc,
		CountHistogram: chc,
	}

	return mt
}

// ToMediaType converts internal CountRowCollection representation to application one.
func (crc CountRowCollection) ToMediaType() app.CountCollection {
	mt := app.CountCollection{}
	for _, c := range crc {
		mtc := (CountRow)(c).ToMediaType()
		mt = append(mt, mtc)
	}
	return mt
}

// ToMediaType converts internal SumRow representation to application one.
func (sr SumRow) ToMediaType() *app.Sum {
	thc := app.TimeHistogramCollection{}

	for _, c := range sr.TimeHistogram {
		hi := (TimeHistogramItem)(c).ToMediaType()
		thc = append(thc, hi)
	}

	if len(sr.Tags) == 0 {
		sr.Tags = nil
	}

	mt := &app.Sum{
		Sum:           sr.Sum,
		Tags:          sr.Tags,
		TimeHistogram: thc,
	}
	return mt
}

// ToMediaType converts internal SumRowCollection representation to application one.
func (src SumRowCollection) ToMediaType() app.SumCollection {
	mt := app.SumCollection{}
	for _, c := range src {
		mtc := (SumRow)(c).ToMediaType()
		mt = append(mt, mtc)
	}
	return mt
}

// ToMediaType converts internal AvgRow representation to application one.
func (ar AvgRow) ToMediaType() *app.Avg {
	thc := app.TimeHistogramCollection{}

	for _, c := range ar.TimeHistogram {
		hi := (TimeHistogramItem)(c).ToMediaType()
		thc = append(thc, hi)
	}

	if len(ar.Tags) == 0 {
		ar.Tags = nil
	}

	mt := &app.Avg{
		Avg:           ar.Avg,
		Tags:          ar.Tags,
		TimeHistogram: thc,
	}
	return mt
}

// ToMediaType converts internal AvgRowCollection representation to application one.
func (arc AvgRowCollection) ToMediaType() app.AvgCollection {
	mt := app.AvgCollection{}
	for _, c := range arc {
		mtc := (AvgRow)(c).ToMediaType()
		mt = append(mt, mtc)
	}
	return mt
}
