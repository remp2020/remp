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
	var o model.PageviewOptions
	o.Action = ctx.Action
	if ctx.FilterBy != nil {
		o.FilterBy = *ctx.FilterBy
		o.IDs = ctx.Ids
	}
	o.GroupBy = ctx.GroupBy
	if ctx.TimeAfter != nil {
		o.TimeAfter = *ctx.TimeAfter
	}
	if ctx.TimeBefore != nil {
		o.TimeBefore = *ctx.TimeBefore
	}

	crc, ok, err := c.PageviewStorage.Count(o)
	if err != nil {
		return err
	}
	if !ok {
		cr := model.CountRow{
			Tags:  nil,
			Count: 0,
		}
		crc := model.CountRowCollection{}
		crc = append(crc, cr)
	}

	acrc := CountRowCollection(crc).ToMediaType()
	return ctx.OK(acrc)
}

// List runs the list action.
func (c *PageviewController) List(ctx *app.ListPageviewsContext) error {
	var o model.PageviewOptions
	if ctx.Action != nil {
		o.Action = *ctx.Action
	}
	if ctx.FilterBy != nil {
		o.FilterBy = *ctx.FilterBy
		o.IDs = ctx.Ids
	}
	if ctx.TimeAfter != nil {
		o.TimeAfter = *ctx.TimeAfter
	}
	if ctx.TimeBefore != nil {
		o.TimeBefore = *ctx.TimeBefore
	}

	pc, err := c.PageviewStorage.List(o)
	if err != nil {
		return err
	}
	mt, err := PageviewCollection(pc).ToMediaType()
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
