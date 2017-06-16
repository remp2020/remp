package datalake

import "time"

// DataLake represents source of stored events.
type DataLake interface {
	// EventCount returns count of events based on filters in options.
	EventCount(o Options) (*EventCountResult, error)
}

// EventCountResult represent result of EventCount job.
type EventCountResult struct {
	Count int
}

// Options represent filter options for event-related calls.
type Options struct {
	UserID     string
	Action     string
	Category   string
	TimeAfter  time.Time
	TimeBefore time.Time
}
