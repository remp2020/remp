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

	return ctx.OK(&app.GroupedCounts{
		Counts: cc,
	})
}

// List runs the list action.
func (c *CommerceController) List(ctx *app.ListCommerceContext) error {
	var o model.CommerceOptions
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
	if ctx.Step != nil {
		o.Step = *ctx.Step
	}
	if ctx.TimeAfter != nil {
		o.TimeAfter = *ctx.TimeAfter
	}
	if ctx.TimeBefore != nil {
		o.TimeBefore = *ctx.TimeBefore
	}

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

	return ctx.OK(&app.GroupedSums{
		Sums: cs,
	})
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
