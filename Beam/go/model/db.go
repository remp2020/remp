package model

import (
	"fmt"
)

var fts = map[string]string{
	"articles": "article_id",
	"authors":  "author_id",
	"users":    "user_id",
}

// FilterType represents special enum layer for data filtering.
type FilterType string

// NewFilterType validates support for provided type and returns FilterType instance.
func NewFilterType(t string) (FilterType, error) {
	_, ok := fts[t]
	if !ok {
		return "", fmt.Errorf("unsupported FilterType: %s", t)
	}
	return FilterType(t), nil
}

func (gt FilterType) column() string {
	return fts[string(gt)]
}

// CountRow represents one row of grouped count.
type CountRow struct {
	Tags  map[string]string
	Count int
}

// CountRowCollection represents collection of rows of grouped count.
type CountRowCollection []CountRow

// SumRow represents one row of grouped sum.
type SumRow struct {
	Tags map[string]string
	Sum  float64
}

// SumRowCollection represents collection of rows of grouped sum.
type SumRowCollection []SumRow
