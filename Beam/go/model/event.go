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
	ID        string
	Category  string    `json:"category"`
	Action    string    `json:"action"`
	Token     string    `json:"token"`
	Time      time.Time `json:"time"`
	Host      string    `json:"host"`
	IP        string    `json:"ip"`
	UserID    string    `json:"user_id"`
	URL       string    `json:"url"`
	UserAgent string    `json:"user_agent"`
	ArticleID string    `json:"article_id"`

	RtmSource   string `json:"rtm_source"`
	RtmCampaign string `json:"rtm_campaign"`
	RtmContent  string `json:"rtm_content"`
	RtmMedium   string `json:"rtm_medium"`

	// Deprecated, will be removed in favor of Rtm
	UtmSource   string `json:"utm_source"`
	UtmCampaign string `json:"utm_campaign"`
	UtmContent  string `json:"utm_content"`
	UtmMedium   string `json:"utm_medium"`
}

// EventRow represents one row of grouped list.
type EventRow struct {
	Tags   map[string]string
	Events []*Event
}

// EventRowCollection represents collection of rows of grouped list.
type EventRowCollection []*EventRow

// EventStorage is an interface to get generic event related data.
type EventStorage interface {
	// Count returns number of events matching the filter defined by EventOptions.
	Count(o AggregateOptions) (CountRowCollection, bool, error)
	// List returns list of all events based on given EventOptions.
	List(o ListOptions) (EventRowCollection, error)
	// Categories lists all tracked categories.
	Categories() ([]string, error)
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all tracked actions under the given category.
	Actions(category string) ([]string, error)
	// Users lists all tracked users.
	Users() ([]string, error)
	// Cache creates internal cache of available categories and actions so they're not polled repeatedly.
	Cache() error
}
