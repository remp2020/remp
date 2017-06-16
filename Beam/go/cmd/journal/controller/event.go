package controller

import (
	"github.com/goadesign/goa"
	"gitlab.com/remp/remp/Beam/go/cmd/journal/app"
	"gitlab.com/remp/remp/Beam/go/cmd/journal/service/datalake"
)

// EventController implements the event resource.
type EventController struct {
	*goa.Controller
	Lake datalake.DataLake
}

// NewEventController creates an event controller.
func NewEventController(service *goa.Service, lake datalake.DataLake) *EventController {
	return &EventController{
		Controller: service.NewController("EventController"),
		Lake:       lake,
	}
}

// Count runs the count action.
func (c *EventController) Count(ctx *app.CountEventsContext) error {
	o := datalake.Options{}
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

	ecr, err := c.Lake.EventCount(o)
	if err != nil {
		return err
	}

	return ctx.OK(&app.EventCount{
		Count: ecr.Count,
	})
}

// List runs the list action.
func (c *EventController) List(ctx *app.ListEventsContext) error {
	return ctx.OK(&app.EventList{})
}
