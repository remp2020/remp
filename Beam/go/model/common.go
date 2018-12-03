package model

import (
	"time"
)

// ListOptions represent select and filter options for listing-related calls.
type ListOptions struct {
	AggregateOptions
	SelectFields []string
}

// ListPageviewsOptions represents select and filter options for listing of pageviews
type ListPageviewsOptions struct {
	AggregateOptions
	SelectFields  []string
	LoadTimespent bool
}

// AggregateOptions represent filter options for aggregate-related calls.
type AggregateOptions struct {
	Category      string
	Action        string
	Step          string
	FilterBy      []*FilterBy
	GroupBy       []string
	TimeAfter     time.Time
	TimeBefore    time.Time
	TimeHistogram *TimeHistogram
}

// TimeHistogram is used to split response to buckets
type TimeHistogram struct {
	Interval string
	Offset   string
}

// FilterBy represents tag and values used to filter results of count-related calls.
type FilterBy struct {
	Tag    string
	Values []string
}
