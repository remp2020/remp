package controller

import (
	"github.com/goadesign/goa"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
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
	o := aggregateOptionsFromPageviewOptions(ctx.Payload)
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
	o := aggregateOptionsFromPageviewOptions(ctx.Payload)
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

// List runs the list action.
func (c *PageviewController) List(ctx *app.ListPageviewsContext) error {
	aggOptions := aggregateOptionsFromPageviewOptions(ctx.Payload.Conditions)
	o := model.ListOptions{
		AggregateOptions: aggOptions,
		SelectFields:     ctx.Payload.SelectFields,
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
	categories := c.PageviewStorage.Categories()
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
func aggregateOptionsFromPageviewOptions(payload *app.PageviewOptionsPayload) model.AggregateOptions {
	var o model.AggregateOptions

	for _, val := range payload.FilterBy {
		fb := &model.FilterBy{
			Tag:    val.Tag,
			Values: val.Values,
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

	return o
}
