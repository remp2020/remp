package model

// Exported constants
const (
	TableConcurrents = "concurrents_by_browser"
)

// ConcurrentsStorage is an interface to get data about last concurrent connections.
type ConcurrentsStorage interface {
	// Count returns count of concurrents based on the provided filter options.
	Count(o AggregateOptions) (CountRowCollection, bool, error)
}
