package model

import (
	"time"
)

// Exported constants for services writing to EventStorage indirectly (e.g. Kafka).
const (
	TableEvents = "events_v2"
)

// EventOptions represent filter options for event-related calls.
type EventOptions struct {
	UserID     string
	Action     string
	Category   string
	TimeAfter  time.Time
	TimeBefore time.Time
}

// Event represents event data.
type Event struct {
	Category  string
	Action    string
	Token     string
	Time      time.Time
	Host      string
	IP        string
	UserID    string
	URL       string
	UserAgent string
}

// EventCollection is collection of events.
type EventCollection []*Event

// EventStorage is an interface to get generic event related data.
type EventStorage interface {
	// Count returns number of events matching the filter defined by EventOptions.
	Count(o AggregateOptions) (CountRowCollection, bool, error)
	// List returns list of all events based on given EventOptions.
	List(o EventOptions) (EventCollection, error)
	// Categories lists all tracked categories.
	Categories() ([]string, error)
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all tracked actions under the given category.
	Actions(category string) ([]string, error)
	// Users lists all tracked users.
	Users() ([]string, error)
}
