package model

import (
	"time"
)

// Exported constants for services writing to EventStorage indirectly (e.g. Kafka) and reading from enumerated values.
const (
	CategoryPageview         = "pageview"
	ActionPageviewLoad       = "load"
	ActionPageviewTimespent  = "timespent"
	TablePageviews           = "pageviews"
	TableTimespent           = "pageviews_time_spent"
	TableTimespentAggregated = "pageviews_time_spent_hourly"
	TableTimespentRP         = "timespent_rp"
	FlagArticle              = "_article"
)

// PageviewOptions represent filter options for pageview-related calls.
type PageviewOptions struct {
	Action     string
	IDs        []string
	FilterBy   string
	GroupBy    []string
	TimeAfter  time.Time
	TimeBefore time.Time
}

// Pageview represents pageview data.
type Pageview struct {
	ArticleID     string `json:"article_id"`
	ArticleLocked bool   `json:"locked"`
	TitleVariant  string `json:"title_variant"`
	ImageVariant  string `json:"image_variant"`
	AuthorID      string `json:"author_id"`
	UTMSource     string `json:"utm_source"`
	UTMCampaign   string `json:"utm_campaign"`
	UTMMedium     string `json:"utm_medium"`
	UTMContent    string `json:"utm_content"`
	SocialSource  string `json:"social"`

	Token        string    `json:"token"`
	Time         time.Time `json:"time"`
	IP           string    `json:"ip"`
	UserID       string    `json:"user_id"`
	URL          string    `json:"url"`
	UserAgent    string    `json:"user_agent"`
	BrowserID    string    `json:"browser_id"`
	SessionID    string    `json:"remp_session_id"`
	PageviewID   string    `json:"remp_pageview_id"`
	Referer      string    `json:"referer"`
	Cookies      bool      `json:"cookies"`
	SignedIn     bool      `json:"signed_in"`
	Subscriber   bool      `json:"subscriber"`
	WindowWidth  int       `json:"window_width"`
	WindowHeight int       `json:"window_height"`
}

// PageviewRow represents one row of grouped list.
type PageviewRow struct {
	Tags      map[string]string
	Pageviews []*Pageview
}

// PageviewRowCollection represents collection of rows of grouped list.
type PageviewRowCollection []*PageviewRow

// PageviewStorage is an interface to get pageview events related data.
type PageviewStorage interface {
	// Count returns count of pageviews based on the provided filter options.
	Count(o AggregateOptions) (CountRowCollection, bool, error)
	// Sum returns sum of pageviews based on the provided filter options.
	Sum(o AggregateOptions) (SumRowCollection, bool, error)
	// List returns list of all pageviews based on given PageviewOptions.
	List(o ListOptions) (PageviewRowCollection, error)
	// Categories lists all tracked categories.
	Categories() []string
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all tracked actions under the given category.
	Actions(category string) ([]string, error)
}
