package controller

import (
	"github.com/goadesign/goa"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// CommerceController implements the event resource.
type CommerceController struct {
	*goa.Controller
	CommerceStorage model.CommerceStorage
}

// NewCommerceController creates a commerce controller.
func NewCommerceController(service *goa.Service, cs model.CommerceStorage) *CommerceController {
	return &CommerceController{
		Controller:      service.NewController("CommerceController"),
		CommerceStorage: cs,
	}
}

// Count runs the count action.
func (c *CommerceController) Count(ctx *app.CountCommerceContext) error {
	o := model.AggregateOptions{
		Step: ctx.Step,
	}

	for _, val := range ctx.Payload.FilterBy {
		fb := &model.FilterBy{
			Tag:    val.Tag,
			Values: val.Values,
		}
		o.FilterBy = append(o.FilterBy, fb)
	}

	o.GroupBy = ctx.Payload.GroupBy
	if ctx.Payload.TimeAfter != nil {
		o.TimeAfter = *ctx.Payload.TimeAfter
	}
	if ctx.Payload.TimeBefore != nil {
		o.TimeBefore = *ctx.Payload.TimeBefore
	}

	crc, ok, err := c.CommerceStorage.Count(o)
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

// List runs the list action.
func (c *CommerceController) List(ctx *app.ListCommerceContext) error {
	o := aggregateOptionsFromCommerceOptions(ctx.Payload)
	o.Step = ctx.Step

	cc, err := c.CommerceStorage.List(o)
	if err != nil {
		return err
	}
	mt, err := CommerceCollection(cc).ToMediaType()
	if err != nil {
		return err
	}
	return ctx.OK(mt)
}

// Sum runs the sum action.
func (c *CommerceController) Sum(ctx *app.SumCommerceContext) error {
	o := aggregateOptionsFromCommerceOptions(ctx.Payload)
	o.Step = ctx.Step

	src, ok, err := c.CommerceStorage.Sum(o)
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

// Categories runs the categories action.
func (c *CommerceController) Categories(ctx *app.CategoriesCommerceContext) error {
	categories := c.CommerceStorage.Categories()
	return ctx.OK(categories)
}

// Actions runs the action action. :)
func (c *CommerceController) Actions(ctx *app.ActionsCommerceContext) error {
	actions, err := c.CommerceStorage.Actions(ctx.Category)
	if err != nil {
		return err
	}
	return ctx.OK(actions)
}

// aggregateOptionsFromCommerceOptions converts payload data to AggregateOptions.
func aggregateOptionsFromCommerceOptions(payload *app.CommerceOptionsPayload) model.AggregateOptions {
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
