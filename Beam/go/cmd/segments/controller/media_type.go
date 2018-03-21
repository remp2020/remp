package controller

import (
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

// EventCollection is the collection of Events.
type EventCollection model.EventCollection

// Commerce represent tracked commerce events data.
type Commerce model.Commerce

// CommerceCollection is the collection of Commerce events.
type CommerceCollection model.CommerceCollection

// Pageview represent tracked Pageview events data.
type Pageview model.Pageview

// PageviewRow represent tracked pageview data with tags.
type PageviewRow model.PageviewRow

// PageviewRowCollection is the collection of PageviewRow events.
type PageviewRowCollection model.PageviewRowCollection

// SegmentCache represent cache object for count of events of SegmentRules.
type SegmentCache model.SegmentCache

// CountRow represent row with count result.
type CountRow model.CountRow

// CountRowCollection is the collection of count rows.
type CountRowCollection model.CountRowCollection

// SumRow represent row with sum result.
type SumRow model.SumRow

// SumRowCollection is the collection of sum rows.
type SumRowCollection model.SumRowCollection

// ToMediaType converts internal Segment representation to application one.
func (s *Segment) ToMediaType() *app.Segment {
	return &app.Segment{
		Code: s.Code,
		Name: s.Name,
		Group: &app.SegmentGroup{
			Name:    "REMP segments",
			Sorting: 100,
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
	return event, nil
}

// ToMediaType converts internal EventCollection representation to application one.
func (ec EventCollection) ToMediaType() (app.EventCollection, error) {
	mt := app.EventCollection{}
	for _, e := range ec {
		event, err := (*Event)(e).ToMediaType()
		if err != nil {
			return nil, err
		}
		mt = append(mt, event)
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
	return event, nil
}

// ToMediaType converts internal CommerceCollection representation to application one.
func (cc CommerceCollection) ToMediaType() (app.CommerceCollection, error) {
	mt := app.CommerceCollection{}
	for _, c := range cc {
		event, err := (*Commerce)(c).ToMediaType()
		if err != nil {
			return nil, err
		}
		mt = append(mt, event)
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
	if p.Referer != "" {
		pageview.User.Referer = &p.Referer
	}
	if p.BrowserID != "" {
		pageview.User.BrowserID = &p.BrowserID
	}
	if p.SessionID != "" {
		pageview.User.RempSessionID = &p.SessionID
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

// ToMediaType converts internal CountRow representation to application one.
func (cr CountRow) ToMediaType() *app.Count {
	mt := &app.Count{
		Count: cr.Count,
		Tags:  cr.Tags,
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
	mt := &app.Sum{
		Sum:  sr.Sum,
		Tags: sr.Tags,
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
