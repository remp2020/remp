package controller

import (
	"github.com/goadesign/goa"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// EventController implements the event resource.
type EventController struct {
	*goa.Controller
	EventStorage model.EventStorage
}

// NewEventController creates an event controller.
func NewEventController(service *goa.Service, es model.EventStorage) *EventController {
	return &EventController{
		Controller:   service.NewController("EventController"),
		EventStorage: es,
	}
}

// Count runs the count action.
func (c *EventController) Count(ctx *app.CountEventsContext) error {
	o := model.EventOptions{}
	if ctx.Action != nil {
		o.Action = *ctx.Action
	}
	if ctx.Category != nil {
		o.Category = *ctx.Category
	}
	if ctx.TimeAfter != nil {
		o.TimeAfter = *ctx.TimeAfter
	}
	if ctx.TimeBefore != nil {
		o.TimeBefore = *ctx.TimeBefore
	}
	if ctx.UserID != nil {
		o.UserID = *ctx.UserID
	}

	ec, err := c.EventStorage.Count(o)
	if err != nil {
		return err
	}

	return ctx.OK(&app.Count{
		Count: ec,
	})
}

// List runs the list action.
func (c *EventController) List(ctx *app.ListEventsContext) error {
	o := model.EventOptions{}
	if ctx.Action != nil {
		o.Action = *ctx.Action
	}
	if ctx.Category != nil {
		o.Category = *ctx.Category
	}
	if ctx.TimeAfter != nil {
		o.TimeAfter = *ctx.TimeAfter
	}
	if ctx.TimeBefore != nil {
		o.TimeBefore = *ctx.TimeBefore
	}
	if ctx.UserID != nil {
		o.UserID = *ctx.UserID
	}

	ec, err := c.EventStorage.List(o)
	if err != nil {
		return err
	}
	return ctx.OK(EventCollection(ec).ToMediaType())
}

// Categories runs the categories action.
func (c *EventController) Categories(ctx *app.CategoriesEventsContext) error {
	categories, err := c.EventStorage.Categories()
	if err != nil {
		return err
	}
	return ctx.OK(categories)
}

// Actions runs the action action. :)
func (c *EventController) Actions(ctx *app.ActionsEventsContext) error {
	actions, err := c.EventStorage.Actions(ctx.Category)
	if err != nil {
		return err
	}
	return ctx.OK(actions)
}
