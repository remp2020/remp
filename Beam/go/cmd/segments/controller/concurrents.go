package controller

import (
	"beam/cmd/segments/gen/concurrents"
	"beam/model"
	"context"
	"time"
)

// ConcurrentsController implements the event resource.
type ConcurrentsController struct {
	ConcurrentsStorage model.ConcurrentsStorage
}

// NewConcurrentsController creates a concurrent controller.
func NewConcurrentsController(cs model.ConcurrentsStorage) concurrents.Service {
	return &ConcurrentsController{cs}
}

// CountEndpoint runs the count action.
func (c *ConcurrentsController) CountEndpoint(ctx context.Context, p *concurrents.ConcurrentsOptionsPayload) (res concurrents.CountCollection, err error) {
	o, err := aggregateOptionsFromConcurrentOptions(p)
	if err != nil {
		return nil, err
	}

	crc, ok, err := c.ConcurrentsStorage.Count(o)
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

	cmt := CountRowCollection(crc).ToConcurrentsMediaType()
	return cmt, nil
}

// aggregateOptionsFromConcurrentOptions converts payload data to AggregateOptions.
func aggregateOptionsFromConcurrentOptions(payload *concurrents.ConcurrentsOptionsPayload) (model.AggregateOptions, error) {
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

	return o, nil
}
