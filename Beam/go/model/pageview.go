package model

import (
	"time"
)

// Exported constants for services writing to EventStorage indirectly (e.g. Kafka) and reading from enumerated values.
const (
	CategoryPageview        = "pageview"
	ActionPageviewLoad      = "load"
	ActionPageviewTimespent = "timespent"
	ActionPageviewProgress  = "progress"
	UniqueCountBrowsers     = "browsers"
	UniqueCountUsers        = "users"
	TablePageviews          = "pageviews"
	TableTimespent          = "pageviews_time_spent"
	TableProgress           = "pageviews_progress"
	TableImpressions        = "impressions"
	FlagArticle             = "is_article"
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
	ID            string
	ArticleID     string `json:"article_id"`
	ArticleLocked bool   `json:"locked"`
	TitleVariant  string `json:"title_variant"`
	ImageVariant  string `json:"image_variant"`
	AuthorID      string `json:"author_id"`
	ContentType   string `json:"content_type"`

	RtmSource   string `json:"rtm_source"`
	RtmCampaign string `json:"rtm_campaign"`
	RtmMedium   string `json:"rtm_medium"`
	RtmContent  string `json:"rtm_content"`

	// UTM is deprecated, will be removed
	UTMSource   string `json:"utm_source"`
	UTMCampaign string `json:"utm_campaign"`
	UTMMedium   string `json:"utm_medium"`
	UTMContent  string `json:"utm_content"`

	Token           string    `json:"token"`
	Time            time.Time `json:"time"`
	IP              string    `json:"ip"`
	UserID          string    `json:"user_id"`
	URL             string    `json:"url"`
	CanonicalURL    string    `json:"canonical_url"`
	UserAgent       string    `json:"user_agent"`
	BrowserID       string    `json:"browser_id"`
	SessionID       string    `json:"remp_session_id"`
	Referer         string    `json:"referer"`
	Cookies         bool      `json:"cookies"`
	SignedIn        bool      `json:"signed_in"`
	Subscriber      bool      `json:"subscriber"`
	SubscriptionIDs []string  `json:"subscription_ids"`
	WindowWidth     int       `json:"window_width"`
	WindowHeight    int       `json:"window_height"`
	Timespent       int       `json:"timespent"`
	PageProgress    float32   `json:"page_progress"`
	ArticleProgress float32   `json:"article_progress"`

	DerivedRefererMedium       string `json:"derived_referer_medium"`
	DerivedRefererHostWithPath string `json:"derived_referer_host_with_path"`
	DerivedRefererSource       string `json:"derived_referer_source"`
}

// PageviewRow represents one row of grouped list.
type PageviewRow struct {
	Tags      map[string]string
	Pageviews []*Pageview
}

// PageviewRowCollection represents collection of rows of grouped list.
type PageviewRowCollection []*PageviewRow

// ListPageviewsOptions represents select and filter options for listing of pageviews
type ListPageviewsOptions struct {
	AggregateOptions
	SelectFields  []string
	LoadTimespent bool
	LoadProgress  bool
}

// PageviewStorage is an interface to get pageview events related data.
type PageviewStorage interface {
	// Count returns count of pageviews based on the provided filter options.
	Count(o AggregateOptions) (CountRowCollection, bool, error)
	// Sum returns sum of pageviews based on the provided filter options.
	Sum(o AggregateOptions) (SumRowCollection, bool, error)
	// Avg returns average of pageviews based on the provided filter options.
	Avg(o AggregateOptions) (AvgRowCollection, bool, error)
	// Unique returns unique count of given item based on the provided filter options.
	Unique(o AggregateOptions, item string) (CountRowCollection, bool, error)
	// List returns list of all pageviews based on given PageviewOptions.
	List(o ListPageviewsOptions) (PageviewRowCollection, error)
	// Categories lists all tracked categories.
	Categories() ([]string, error)
	// Flags lists all available flags.
	Flags() []string
	// Actions lists all tracked actions under the given category.
	Actions(category string) ([]string, error)
}
