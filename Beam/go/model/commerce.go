package model

import (
	"time"
)

// Exported constants for services writing to EventStorage indirectly (e.g. Kafka).
const (
	CategoryCommerce = "commerce"
	TableCommerce    = "commerce"
)

// CommerceOptions represent filter options for commerce-related calls.
type CommerceOptions struct {
	IDs        []string
	FilterBy   FilterType
	Group      bool
	Step       string
	TimeAfter  time.Time
	TimeBefore time.Time
}

// Commerce represents commerce event data.
type Commerce struct {
	Step      string
	Token     string
	Time      time.Time
	Host      string
	IP        string
	UserID    string
	URL       string
	UserAgent string
}

// CommerceRow represents one row of grouped list.
type CommerceRow struct {
	Commerces []*Commerce
	Tags      map[string]string
}

// CommerceRowCollection represents collection of rows of grouped list.
type CommerceRowCollection []*CommerceRow

// CommerceStorage is an interface to get commerce event related data.
type CommerceStorage interface {
	// Count returns count of events based on the provided filter options.
	Count(o AggregateOptions) (CountRowCollection, bool, error)
	// Sum returns sum of events based on the provided filter options.
	Sum(o AggregateOptions) (SumRowCollection, bool, error)
	// List returns list of all events based on given CommerceOptions.
	List(o ListOptions) (CommerceRowCollection, error)
	// Categories lists all available categories.
	Categories() []string
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all available actions under the given category.
	Actions(category string) ([]string, error)
}
