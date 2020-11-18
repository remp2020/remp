package controller

import (
	"beam/cmd/segments/app"
	"beam/model"
	"time"

	"github.com/goadesign/goa"
)

// PageviewController implements the event resource.
type PageviewController struct {
	*goa.Controller
	PageviewStorage model.PageviewStorage
}

// NewPageviewController creates a pageview controller.
func NewPageviewController(service *goa.Service, ps model.PageviewStorage) *PageviewController {
	return &PageviewController{
		Controller:      service.NewController("PageviewController"),
		PageviewStorage: ps,
	}
}

// Count runs the count action.
func (c *PageviewController) Count(ctx *app.CountPageviewsContext) error {
	o, err := aggregateOptionsFromPageviewOptions(ctx.Payload)
	if err != nil {
		return err
	}
	o.Action = ctx.Action

	crc, ok, err := c.PageviewStorage.Count(o)
	if err != nil {
		return err
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
	return ctx.OK(acrc)
}

// Sum runs the sum action.
func (c *PageviewController) Sum(ctx *app.SumPageviewsContext) error {
	o, err := aggregateOptionsFromPageviewOptions(ctx.Payload)
	if err != nil {
		return err
	}
	o.Action = ctx.Action

	src, ok, err := c.PageviewStorage.Sum(o)
	if err != nil {
		return err
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
	return ctx.OK(asrc)
}

// Avg runs the avg action.
func (c *PageviewController) Avg(ctx *app.AvgPageviewsContext) error {
	o, err := aggregateOptionsFromPageviewOptions(ctx.Payload)
	if err != nil {
		return err
	}
	o.Action = ctx.Action

	src, ok, err := c.PageviewStorage.Avg(o)
	if err != nil {
		return err
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
	return ctx.OK(asrc)
}

// Unique runs the cardinality count action.
func (c *PageviewController) Unique(ctx *app.UniquePageviewsContext) error {
	o, err := aggregateOptionsFromPageviewOptions(ctx.Payload)
	if err != nil {
		return err
	}
	o.Action = ctx.Action

	src, ok, err := c.PageviewStorage.Unique(o, ctx.Item)
	if err != nil {
		return err
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
	return ctx.OK(asrc)
}

// List runs the list action.
func (c *PageviewController) List(ctx *app.ListPageviewsContext) error {
	aggOptions, err := aggregateOptionsFromPageviewOptions(ctx.Payload.Conditions)
	if err != nil {
		return err
	}
	o := model.ListPageviewsOptions{
		AggregateOptions: aggOptions,
		SelectFields:     ctx.Payload.SelectFields,
		LoadTimespent:    ctx.Payload.LoadTimespent,
	}

	prc, err := c.PageviewStorage.List(o)
	if err != nil {
		return err
	}
	mt, err := PageviewRowCollection(prc).ToMediaType()
	if err != nil {
		return err
	}
	return ctx.OK(mt)
}

// Categories runs the categories action.
func (c *PageviewController) Categories(ctx *app.CategoriesPageviewsContext) error {
	categories, err := c.PageviewStorage.Categories()
	if err != nil {
		return err
	}
	return ctx.OK(categories)
}

// Actions runs the action action. :)
func (c *PageviewController) Actions(ctx *app.ActionsPageviewsContext) error {
	actions, err := c.PageviewStorage.Actions(ctx.Category)
	if err != nil {
		return err
	}
	return ctx.OK(actions)
}

// aggregateOptionsFromPageviewOptions converts payload data to AggregateOptions.
func aggregateOptionsFromPageviewOptions(payload *app.PageviewOptionsPayload) (model.AggregateOptions, error) {
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
		o.TimeAfter = *payload.TimeAfter
	}
	if payload.TimeBefore != nil {
		o.TimeBefore = *payload.TimeBefore
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

	return o, nil
}
