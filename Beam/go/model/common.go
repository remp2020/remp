package model

import (
	"fmt"
	"time"

	"gitlab.com/remp/remp/Beam/go/influxquery"
)

// ListOptions represent select and filter options for listing-related calls.
type ListOptions struct {
	AggregateOptions
	SelectFields []string
}

// AggregateOptions represent filter options for aggregate-related calls.
type AggregateOptions struct {
	Category   string
	Action     string
	Step       string
	FilterBy   []*FilterBy
	GroupBy    []string
	TimeAfter  time.Time
	TimeBefore time.Time
}

// FilterBy represents tag and values used to filter results of count-related calls.
type FilterBy struct {
	Tag    string
	Values []string
}

func addAggregateQueryFilters(builder influxquery.Builder, o AggregateOptions) influxquery.Builder {
	builder = addQueryFilterFilterBy(builder, o.FilterBy)
	builder = addQueryFilterCategory(builder, o.Category)
	builder = addQueryFilterAction(builder, o.Action)
	builder = addQueryFilterStep(builder, o.Step)
	builder = addQueryFilterTimeAfter(builder, o.TimeAfter)
	builder = addQueryFilterTimeBefore(builder, o.TimeBefore)
	builder = addQueryFilterGroupBy(builder, o.GroupBy)

	return builder
}

func addQueryFilterFilterBy(builder influxquery.Builder, filterBy []*FilterBy) influxquery.Builder {
	if len(filterBy) > 0 {
		cond := ""
		for _, val := range filterBy {
			if len(val.Values) == 0 {
				continue
			}
			if cond != "" {
				cond += ") AND ("
			}

			// TODO: replace OR with IN when it's implemented into influxDB
			// https://github.com/influxdata/influxdb/issues/2157
			for i, v := range val.Values {
				if i > 0 {
					cond += " OR "
				}
				cond = fmt.Sprintf("%s%s = '%s'", cond, val.Tag, v)
			}
		}

		if cond != "" {
			builder = builder.Where(fmt.Sprintf("(%s)", cond))
		}
	}

	return builder
}

func addQueryFilterCategory(builder influxquery.Builder, category string) influxquery.Builder {
	if category != "" {
		builder = builder.Where(fmt.Sprintf("category = '%s'", category))
	}
	return builder
}

func addQueryFilterAction(builder influxquery.Builder, action string) influxquery.Builder {
	if action != "" {
		builder = builder.Where(fmt.Sprintf("action = '%s'", action))
	}
	return builder
}

func addQueryFilterStep(builder influxquery.Builder, step string) influxquery.Builder {
	if step != "" {
		builder = builder.Where(fmt.Sprintf("step = '%s'", step))
	}
	return builder
}

func addQueryFilterTimeAfter(builder influxquery.Builder, timeAfter time.Time) influxquery.Builder {
	if !timeAfter.IsZero() {
		builder = builder.Where(fmt.Sprintf("time >= %d", timeAfter.UnixNano()))
	}
	return builder
}

func addQueryFilterTimeBefore(builder influxquery.Builder, timeBefore time.Time) influxquery.Builder {
	if !timeBefore.IsZero() {
		builder = builder.Where(fmt.Sprintf("time < %d", timeBefore.UnixNano()))
	}
	return builder
}

func addQueryFilterGroupBy(builder influxquery.Builder, groupBy []string) influxquery.Builder {
	condGroupBy := ""
	for _, val := range groupBy {
		if val == "" {
			continue
		}
		if condGroupBy != "" {
			condGroupBy = fmt.Sprintf(`%s, "%s"`, condGroupBy, val)
		} else {
			condGroupBy = fmt.Sprintf(`"%s"`, val)
		}
	}
	if condGroupBy != "" {
		builder = builder.GroupBy(condGroupBy)
	}

	return builder
}
