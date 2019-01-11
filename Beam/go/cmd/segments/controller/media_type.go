package controller

import (
	"strings"

	"github.com/pkg/errors"
	"github.com/satori/go.uuid"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// Segment represents segment information stored in storage.
type Segment model.Segment

// SegmentCollection is the collection of Segments.
type SegmentCollection model.SegmentCollection

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

// HistogramItem represent row with date time histogram data.
type HistogramItem model.HistogramItem

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
func (s *Segment) ToMediaType() *app.Segment {
	return &app.Segment{
		Code: s.Code,
		Name: s.Name,
		Group: &app.SegmentGroup{
			Name:    s.Group.Name,
			Sorting: s.Group.Sorting,
		},
	}
}

// ToMediaType converts internal SegmentCollection representation to application one.
func (sc SegmentCollection) ToMediaType() app.SegmentCollection {
	mt := app.SegmentCollection{}
	for _, s := range sc {
		mt = append(mt, (*Segment)(s).ToMediaType())
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
		event.ID = &e.ID
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
		User:    &app.User{},
		Details: &app.CommerceDetails{},
		Source:  &app.Source{},
	}
	if c.ID != "" {
		event.ID = &c.ID
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
	if c.FunnelID != "" {
		event.Details.FunnelID = c.FunnelID
	}
	if c.ProductIDs != "" {
		event.Details.ProductIds = strings.Split(c.ProductIDs, ",")
	}

	if c.Revenue != 0 && c.Currency != "" {
		event.Details.Revenue = &app.Revenue{
			Amount:   c.Revenue,
			Currency: c.Currency,
		}
	}

	return event, nil
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
			RempPageviewID: p.PageviewID,
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
	if p.SocialSource != "" {
		pageview.User.Source.Social = &p.SocialSource
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

// ToMediaType converts internal HistogramItem representation to application one.
func (hi HistogramItem) ToMediaType() *app.TimeHistogram {
	c := float64(hi.Value)

	th := &app.TimeHistogram{
		Time:  hi.Time,
		Value: c,
	}

	return th
}

// ToMediaType converts internal CountRow representation to application one.
func (cr CountRow) ToMediaType() *app.Count {
	coll := app.TimeHistogramCollection{}

	for _, c := range cr.Histogram {
		hi := (HistogramItem)(c).ToMediaType()
		coll = append(coll, hi)
	}

	if len(cr.Tags) == 0 {
		cr.Tags = nil
	}

	mt := &app.Count{
		Count:         cr.Count,
		Tags:          cr.Tags,
		TimeHistogram: coll,
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

	for _, c := range sr.Histogram {
		hi := (HistogramItem)(c).ToMediaType()
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

	for _, c := range ar.Histogram {
		hi := (HistogramItem)(c).ToMediaType()
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
