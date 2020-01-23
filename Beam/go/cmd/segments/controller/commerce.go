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
	acrc, err := processCount(c, aggregateOptionsFromCommerceOptions(ctx.Payload))
	if err != nil {
		return err
	}
	return ctx.OK(acrc)
}

// CountStep runs the count action with step parameter
func (c *CommerceController) CountStep(ctx *app.CountStepCommerceContext) error {
	o := aggregateOptionsFromCommerceOptions(ctx.Payload)
	o.Step = ctx.Step
	acrc, err := processCount(c, o)
	if err != nil {
		return err
	}
	return ctx.OK(acrc)
}

func processCount(c *CommerceController, ao model.AggregateOptions) (app.CountCollection, error) {
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

	return CountRowCollection(crc).ToMediaType(), nil
}

// List runs the list action.
func (c *CommerceController) List(ctx *app.ListCommerceContext) error {
	aggOptions := aggregateOptionsFromCommerceOptions(ctx.Payload.Conditions)
	o := model.ListOptions{
		AggregateOptions: aggOptions,
		SelectFields:     ctx.Payload.SelectFields,
	}

	crc, err := c.CommerceStorage.List(o)
	if err != nil {
		return err
	}
	mt, err := CommerceRowCollection(crc).ToMediaType()
	if err != nil {
		return err
	}
	return ctx.OK(mt)
}

// SumStep runs the sum action for particular step
func (c *CommerceController) SumStep(ctx *app.SumStepCommerceContext) error {
	o := aggregateOptionsFromCommerceOptions(ctx.Payload)
	o.Step = ctx.Step

	asrc, err := processSum(c, o)
	if err != nil {
		return err
	}
	return ctx.OK(asrc)
}

// Sum runs the sum action
func (c *CommerceController) Sum(ctx *app.SumCommerceContext) error {
	asrc, err := processSum(c, aggregateOptionsFromCommerceOptions(ctx.Payload))
	if err != nil {
		return err
	}
	return ctx.OK(asrc)
}

func processSum(c *CommerceController, ao model.AggregateOptions) (app.SumCollection, error) {
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

	return SumRowCollection(src).ToMediaType(), nil
}

// Categories runs the categories action.
func (c *CommerceController) Categories(ctx *app.CategoriesCommerceContext) error {
	categories, err := c.CommerceStorage.Categories()
	if err != nil {
		return err
	}
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

	if payload.Step != nil {
		o.Step = *payload.Step
	}

	if payload.TimeHistogram != nil {
		o.TimeHistogram = &model.TimeHistogram{
			Interval: payload.TimeHistogram.Interval,
			Offset:   payload.TimeHistogram.Offset,
		}
	}

	return o

}
