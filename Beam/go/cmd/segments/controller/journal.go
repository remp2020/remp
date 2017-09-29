package controller

import (
	"github.com/goadesign/goa"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// JournalController implements the journal resource.
type JournalController struct {
	*goa.Controller
	EventStorage    model.EventStorage
	CommerceStorage model.CommerceStorage
	PageviewStorage model.PageviewStorage
}

// NewJournalController creates an journal controller.
func NewJournalController(service *goa.Service, es model.EventStorage, cs model.CommerceStorage,
	ps model.PageviewStorage) *JournalController {
	return &JournalController{
		Controller:      service.NewController("JournalController"),
		EventStorage:    es,
		CommerceStorage: cs,
		PageviewStorage: ps,
	}
}

// Flags runs the flags action.
func (c *JournalController) Flags(ctx *app.FlagsJournalContext) error {
	return ctx.OK(&app.Flags{
		Pageviews: c.PageviewStorage.Flags(),
		Commerce:  c.CommerceStorage.Flags(),
		Events:    c.EventStorage.Flags(),
	})
}
