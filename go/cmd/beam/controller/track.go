package controller

import (
	"github.com/goadesign/goa"
	"gitlab.com/remp/remp/go/cmd/beam/app"
)

// TrackController implements the track resource.
type TrackController struct {
	*goa.Controller
}

// NewTrackController creates a track controller.
func NewTrackController(service *goa.Service) *TrackController {
	return &TrackController{Controller: service.NewController("TrackController")}
}

// Commerce runs the commerce action.
func (c *TrackController) Commerce(ctx *app.CommerceTrackContext) error {
	return ctx.Accepted()
}

// Event runs the event action.
func (c *TrackController) Event(ctx *app.EventTrackContext) error {
	return ctx.Accepted()
}

// Pageview runs the pageview action.
func (c *TrackController) Pageview(ctx *app.PageviewTrackContext) error {
	return ctx.Accepted()
}
