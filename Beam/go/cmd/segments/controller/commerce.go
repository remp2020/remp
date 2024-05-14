package controller

import (
	"beam/cmd/segments/gen/commerce"
	"beam/model"
	"context"
	"time"
)

// CommerceController implements the event resource.
type CommerceController struct {
	CommerceStorage model.CommerceStorage
}

// NewCommerceController creates a commerce controller.
func NewCommerceController(cs model.CommerceStorage) commerce.Service {
	return &CommerceController{cs}
}

// CountStep runs the count action with step parameter
func (c *CommerceController) CountStep(ctx context.Context, p *commerce.CommerceOptionsPayload) (res commerce.CountCollection, err error) {
	o, err := aggregateOptionsFromCommerceOptions(p)
	if err != nil {
		return nil, err
	}
	o.Step = *p.Step
	acrc, err := processCount(c, o)
	if err != nil {
		return nil, err
	}
	return acrc, nil
}

// CountEndpoint runs the count action.
func (c *CommerceController) CountEndpoint(ctx context.Context, p *commerce.CommerceOptionsPayload) (res commerce.CountCollection, err error) {
	o, err := aggregateOptionsFromCommerceOptions(p)
	if err != nil {
		return nil, err
	}
	acrc, err := processCount(c, o)
	if err != nil {
		return nil, err
	}
	return acrc, nil
}

// SumStep runs the sum action for particular step
func (c *CommerceController) SumStep(ctx context.Context, p *commerce.CommerceOptionsPayload) (res commerce.SumCollection, err error) {
	o, err := aggregateOptionsFromCommerceOptions(p)
	if err != nil {
		return nil, err
	}
	asrc, err := processSum(c, o)
	if err != nil {
		return nil, err
	}
	return asrc, nil
}

// SumEndpoint runs the sum action
func (c *CommerceController) SumEndpoint(ctx context.Context, p *commerce.CommerceOptionsPayload) (res commerce.SumCollection, err error) {
	o, err := aggregateOptionsFromCommerceOptions(p)
	if err != nil {
		return nil, err
	}
	asrc, err := processSum(c, o)
	if err != nil {
		return nil, err
	}
	return asrc, nil
}

// List runs the list action.
func (c *CommerceController) List(ctx context.Context, p *commerce.ListCommerceOptionsPayload) (res commerce.CommercesCollection, err error) {
	aggOptions, err := aggregateOptionsFromCommerceOptions(p.Conditions)
	if err != nil {
		return nil, err
	}

	o := model.ListOptions{
		AggregateOptions: aggOptions,
		SelectFields:     p.SelectFields,
	}

	crc, err := c.CommerceStorage.List(o)
	if err != nil {
		return nil, err
	}
	mt, err := CommerceRowCollection(crc).ToMediaType()
	if err != nil {
		return nil, err
	}
	return mt, nil
}

// Categories runs the categories action.
func (c *CommerceController) Categories(ctx context.Context) (res []string, err error) {
	return c.CommerceStorage.Categories()
}

// Actions runs the action action. :)
func (c *CommerceController) Actions(ctx context.Context, p *commerce.ActionsPayload) (res []string, err error) {
	return c.CommerceStorage.Actions(*p.Category)
}

// aggregateOptionsFromCommerceOptions converts payload data to AggregateOptions.
func aggregateOptionsFromCommerceOptions(payload *commerce.CommerceOptionsPayload) (model.AggregateOptions, error) {
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

	if payload.Step != nil {
		o.Step = *payload.Step
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

	return o, nil
}

func processCount(c *CommerceController, ao model.AggregateOptions) (commerce.CountCollection, error) {
	crc, ok, err := c.CommerceStorage.Count(ao)
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

	return CountRowCollection(crc).ToCommerceMediaType(), nil
}

func processSum(c *CommerceController, ao model.AggregateOptions) (commerce.SumCollection, error) {
	src, ok, err := c.CommerceStorage.Sum(ao)
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

	return SumRowCollection(src).ToCommerceMediaType(), nil
}
