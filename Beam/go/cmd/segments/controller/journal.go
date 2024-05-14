package controller

import (
	"beam/cmd/segments/gen/journal"
	"beam/model"
	"context"
)

// JournalController implements the journal resource.
type JournalController struct {
	EventStorage    model.EventStorage
	CommerceStorage model.CommerceStorage
	PageviewStorage model.PageviewStorage
}

// NewJournalController creates an journal controller.
func NewJournalController(es model.EventStorage, cs model.CommerceStorage,
	ps model.PageviewStorage) journal.Service {
	return &JournalController{
		EventStorage:    es,
		CommerceStorage: cs,
		PageviewStorage: ps,
	}
}

// FlagsEndpoint lists of all available flags
func (c *JournalController) FlagsEndpoint(ctx context.Context) (res *journal.Flags, err error) {
	return &journal.Flags{
		Pageviews: c.PageviewStorage.Flags(),
		Commerce:  c.CommerceStorage.Flags(),
		Events:    c.EventStorage.Flags(),
	}, nil
}
