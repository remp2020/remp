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
	ID         string
	Step       string
	Token      string
	Time       time.Time
	Host       string
	IP         string
	BrowserID  string  `json:"browser_id"`
	UserID     string  `json:"user_id"`
	URL        string  `json:"url"`
	UserAgent  string  `json:"user_agent"`
	FunnelID   string  `json:"funnel_id"`
	ProductIDs string  `json:"product_ids"`
	Revenue    float64 `json:"revenue"`
	Currency   string  `json:"currency"`

	RtmCampaign string `json:"rtm_campaign"`
	RtmContent  string `json:"rtm_content"`
	RtmMedium   string `json:"rtm_medium"`
	RtmSource   string `json:"rtm_source"`

	// Deprecated, will be removed in favor of Rtm
	UtmCampaign string `json:"utm_campaign"`
	UtmContent  string `json:"utm_content"`
	UtmMedium   string `json:"utm_medium"`
	UtmSource   string `json:"utm_source"`
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
	Categories() ([]string, error)
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all available actions under the given category.
	Actions(category string) ([]string, error)
}
