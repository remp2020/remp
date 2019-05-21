package controller

import (
	"github.com/goadesign/goa"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// ConcurrentsController implements the event resource.
type ConcurrentsController struct {
	*goa.Controller
	ConcurrentsStorage model.ConcurrentsStorage
}

// NewConcurrentsController creates a concurrent controller.
func NewConcurrentsController(service *goa.Service, cs model.ConcurrentsStorage) *ConcurrentsController {
	return &ConcurrentsController{
		Controller:         service.NewController("ConcurrentController"),
		ConcurrentsStorage: cs,
	}
}

// Count runs the count action.
func (c *ConcurrentsController) Count(ctx *app.CountConcurrentsContext) error {
	o := aggregateOptionsFromConcurrentOptions(ctx.Payload)

	crc, ok, err := c.ConcurrentsStorage.Count(o)
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

// aggregateOptionsFromConcurrentOptions converts payload data to AggregateOptions.
func aggregateOptionsFromConcurrentOptions(payload *app.ConcurrentsOptionsPayload) model.AggregateOptions {
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

	return o
}
