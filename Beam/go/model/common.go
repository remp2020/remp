package model

import (
	"regexp"
	"strings"
	"time"
)

// ListOptions represent select and filter options for listing-related calls.
type ListOptions struct {
	AggregateOptions
	SelectFields []string
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
	Tag     string
	Values  []string
	Inverse bool
}

// Webalize replaces all spaces with dash and removes all non-alphanumerical characters.
func Webalize(s string) (string, error) {
	// replace strings
	s = strings.Replace(s, " ", "-", -1)
	// remove all non-alphanumerical characters (except dash)
	reg, err := regexp.Compile("[^a-zA-Z0-9-]+")
	if err != nil {
		return "", err
	}
	return reg.ReplaceAllString(s, ""), nil
}
