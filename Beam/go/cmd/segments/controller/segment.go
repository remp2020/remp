package controller

import (
	"time"

	"github.com/goadesign/goa"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// EventController implements the event resource.
type SegmentController struct {
	*goa.Controller
	SegmentStorage model.SegmentStorage
}

// NewSegmentController creates a segment controller.
func NewSegmentController(service *goa.Service, segmentStorage model.SegmentStorage) *SegmentController {
	return &SegmentController{
		Controller:     service.NewController("SegmentController"),
		SegmentStorage: segmentStorage,
	}
}

// List runs the list action.
func (c *SegmentController) List(ctx *app.ListSegmentsContext) error {
	sc, err := c.SegmentStorage.List()
	if err != nil {
		return err
	}
	return ctx.OK((SegmentCollection)(sc).ToMediaType())
}

// Check runs the check action.
func (c *SegmentController) Check(ctx *app.CheckSegmentsContext) error {
	s, ok, err := c.SegmentStorage.Get(ctx.SegmentCode)
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}
	ro := model.RuleOverrides{
		Fields: ctx.Payload.Fields,
	}
	ok, err = c.SegmentStorage.Check(s, ctx.UserID, time.Now(), ro)
	if err != nil {
		return err
	}
	return ctx.OK(&app.SegmentCheck{
		Check: ok,
	})
}

// Users runs the users action.
func (c *SegmentController) Users(ctx *app.UsersSegmentsContext) error {
	s, ok, err := c.SegmentStorage.Get(ctx.SegmentCode)
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}
	ro := model.RuleOverrides{
		Fields: ctx.Payload.Fields,
	}
	uc, err := c.SegmentStorage.Users(s, time.Now(), ro)
	if err != nil {
		return err
	}
	return ctx.OK(uc)
}
