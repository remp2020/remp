package controller

import (
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// Segment represents segment information stored in storage.
type Segment model.Segment

// SegmentCollection is the collection of Segments.
type SegmentCollection model.SegmentCollection

// User represent user information stored in events.
type User model.User

// UserCollection is the collection of Users.
type UserCollection model.UserCollection

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

// ToMediaType converts internal User representation to application one.
func (u *User) ToMediaType() *app.User {
	return &app.User{
		ID: u.ID,
	}
}

// ToMediaType converts internal UserCollection representation to application one.
func (uc UserCollection) ToMediaType() app.UserCollection {
	mt := app.UserCollection{}
	for _, u := range uc {
		mt = append(mt, (*User)(u).ToMediaType())
	}
	return mt
}

// ToMediaType converts internal Event representation to application one.
func (e *Event) ToMediaType() *app.Event {
	return &app.Event{
		Category:  e.Category,
		Action:    e.Action,
		Time:      e.Time,
		Host:      e.Host,
		IP:        e.IP,
		Token:     e.Token,
		UserID:    e.UserID,
		URL:       e.URL,
		UserAgent: e.UserAgent,
	}
}

// ToMediaType converts internal EventCollection representation to application one.
func (ec EventCollection) ToMediaType() app.EventCollection {
	mt := app.EventCollection{}
	for _, e := range ec {
		mt = append(mt, (*Event)(e).ToMediaType())
	}
	return mt
}
