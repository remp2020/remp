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
	o := model.CommerceOptions{
		Step: ctx.Step,
	}
	if ctx.FilterBy != nil {
		ft, err := model.NewFilterType(*ctx.FilterBy)
		if err != nil {
			return err
		}
		o.FilterBy = ft

		if ctx.Group != nil && *ctx.Group {
			o.Group = true
		}
		o.IDs = ctx.Ids
	}
	if ctx.TimeAfter != nil {
		o.TimeAfter = *ctx.TimeAfter
	}
	if ctx.TimeBefore != nil {
		o.TimeBefore = *ctx.TimeBefore
	}

	cc, err := c.CommerceStorage.Count(o)
	if err != nil {
		return err
	}

	return ctx.OK(&app.GrouppedCounts{
		Counts: cc,
	})
}

// List runs the list action.
func (c *CommerceController) List(ctx *app.ListCommerceContext) error {
	return ctx.OK(&app.CommerceList{})
}

// Sum runs the sum action.
func (c *CommerceController) Sum(ctx *app.SumCommerceContext) error {
	o := model.CommerceOptions{
		Step: ctx.Step,
	}
	if ctx.FilterBy != nil {
		ft, err := model.NewFilterType(*ctx.FilterBy)
		if err != nil {
			return err
		}
		o.FilterBy = ft

		if ctx.Group != nil && *ctx.Group {
			o.Group = true
		}
		o.IDs = ctx.Ids
	}
	if ctx.TimeAfter != nil {
		o.TimeAfter = *ctx.TimeAfter
	}
	if ctx.TimeBefore != nil {
		o.TimeBefore = *ctx.TimeBefore
	}

	cs, err := c.CommerceStorage.Sum(o)
	if err != nil {
		return err
	}

	return ctx.OK(&app.GrouppedSums{
		Sums: cs,
	})
}
