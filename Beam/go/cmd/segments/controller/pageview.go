package controller

import (
	"beam/cmd/segments/gen/pageviews"
	"beam/model"
	"context"
	"time"
)

// PageviewController implements the event resource.
type PageviewController struct {
	PageviewStorage model.PageviewStorage
}

// NewPageviewController creates a pageview controller.
func NewPageviewController(ps model.PageviewStorage) pageviews.Service {
	return &PageviewController{ps}
}

// CountEndpoint runs the count action.
func (c *PageviewController) CountEndpoint(ctx context.Context, p *pageviews.PageviewOptionsPayload) (res pageviews.CountCollection, err error) {
	o, err := aggregateOptionsFromPageviewOptions(p)
	if err != nil {
		return nil, err
	}

	crc, ok, err := c.PageviewStorage.Count(o)
	if err != nil {
		return nil, err
	}

	if !ok {
		cr := model.CountRow{
			Tags:  make(map[string]string),
			Count: 0,
		}
		crc = model.CountRowCollection{}
		crc = append(crc, cr)
	}

	acrc := CountRowCollection(crc).ToMediaType()
	return acrc, nil
}

// SumEndpoint runs the sum action.
func (c *PageviewController) SumEndpoint(ctx context.Context, p *pageviews.PageviewOptionsPayload) (res pageviews.SumCollection, err error) {
	o, err := aggregateOptionsFromPageviewOptions(p)
	if err != nil {
		return nil, err
	}

	src, ok, err := c.PageviewStorage.Sum(o)
	if err != nil {
		return nil, err
	}

	if !ok {
		sr := model.SumRow{
			Tags: make(map[string]string),
			Sum:  0,
		}
		src = model.SumRowCollection{}
		src = append(src, sr)
	}

	asrc := SumRowCollection(src).ToMediaType()
	return asrc, nil
}

// AvgEndpoint runs the avg action.
func (c *PageviewController) AvgEndpoint(ctx context.Context, p *pageviews.PageviewOptionsPayload) (res pageviews.AvgCollection, err error) {
	o, err := aggregateOptionsFromPageviewOptions(p)
	if err != nil {
		return nil, err
	}

	src, ok, err := c.PageviewStorage.Avg(o)
	if err != nil {
		return nil, err
	}

	if !ok {
		sr := model.AvgRow{
			Tags: make(map[string]string),
			Avg:  0,
		}
		src = model.AvgRowCollection{}
		src = append(src, sr)
	}

	asrc := AvgRowCollection(src).ToMediaType()
	return asrc, nil
}

// Unique runs the cardinality count action.
func (c *PageviewController) Unique(ctx context.Context, p *pageviews.PageviewOptionsPayload) (res pageviews.CountCollection, err error) {
	o, err := aggregateOptionsFromPageviewOptions(p)
	if err != nil {
		return nil, err
	}

	src, ok, err := c.PageviewStorage.Unique(o, *p.Item)
	if err != nil {
		return nil, err
	}

	if !ok {
		sr := model.CountRow{
			Tags:  make(map[string]string),
			Count: 0,
		}
		src = model.CountRowCollection{}
		src = append(src, sr)
	}

	asrc := CountRowCollection(src).ToMediaType()
	return asrc, nil
}

// List runs the list action.
func (c *PageviewController) List(ctx context.Context, p *pageviews.ListPageviewOptionsPayload) (res pageviews.PageviewsCollection, err error) {
	aggOptions, err := aggregateOptionsFromPageviewOptions(p.Conditions)
	if err != nil {
		return nil, err
	}
	o := model.ListPageviewsOptions{
		AggregateOptions: aggOptions,
		SelectFields:     p.SelectFields,
		LoadTimespent:    p.LoadTimespent,
		LoadProgress:     p.LoadProgress,
	}

	prc, err := c.PageviewStorage.List(o)
	if err != nil {
		return nil, err
	}
	mt, err := PageviewRowCollection(prc).ToMediaType()
	if err != nil {
		return nil, err
	}
	return mt, nil
}

// Categories runs the categories action.
func (c *PageviewController) Categories(ctx context.Context) (res []string, err error) {
	return c.PageviewStorage.Categories()
}

// Actions runs the action action. :)
func (c *PageviewController) Actions(ctx context.Context, p *pageviews.ActionsPayload) (res []string, err error) {
	return c.PageviewStorage.Actions(*p.Category)
}

// aggregateOptionsFromPageviewOptions converts payload data to AggregateOptions.
func aggregateOptionsFromPageviewOptions(payload *pageviews.PageviewOptionsPayload) (model.AggregateOptions, error) {
	var o model.AggregateOptions

	for _, val := range payload.FilterBy {
		fb := &model.FilterBy{
			Tag:     val.Tag,
			Values:  val.Values,
			Inverse: false,
		}
		if val.Inverse != nil {
			fb.Inverse = *val.Inverse
		}
		o.FilterBy = append(o.FilterBy, fb)
	}

	o.GroupBy = payload.GroupBy
	if payload.TimeAfter != nil {
		t, err := time.Parse(time.RFC3339, *payload.TimeAfter)
		if err != nil {
			return o, err
		}
		o.TimeAfter = t
	}
	if payload.TimeBefore != nil {
		t, err := time.Parse(time.RFC3339, *payload.TimeBefore)
		if err != nil {
			return o, err
		}
		o.TimeBefore = t
	}

	if payload.TimeHistogram != nil {
		o.TimeHistogram = &model.TimeHistogram{
			Interval: payload.TimeHistogram.Interval,
		}

		if payload.TimeHistogram.TimeZone != nil {
			location, err := time.LoadLocation(*payload.TimeHistogram.TimeZone)
			if err != nil {
				return o, err
			}
			o.TimeHistogram.TimeZone = location
		}
	}

	if payload.CountHistogram != nil {
		o.CountHistogram = &model.CountHistogram{
			Field:    payload.CountHistogram.Field,
			Interval: payload.CountHistogram.Interval,
		}
	}

	if payload.Action != nil {
		o.Action = *payload.Action
	}

	return o, nil
}
