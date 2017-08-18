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
