package controller

import (
	"encoding/json"
	"fmt"
	"time"

	"github.com/goadesign/goa"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/segments/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// SegmentType represents type of segment (source of data used for segment)
type SegmentType int

// Enum of available segment types
const (
	UserSegment SegmentType = iota + 1
	BrowserSegment
)

// SegmentController implements the segment resource.
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

// CheckUser runs the check_user action.
func (c *SegmentController) CheckUser(ctx *app.CheckUserSegmentsContext) error {
	sc, ok, err := c.handleCheck(UserSegment, ctx.SegmentCode, ctx.UserID, ctx.Fields, ctx.Cache)
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}
	return ctx.OK(sc)
}

// CheckBrowser runs the check_user action.
func (c *SegmentController) CheckBrowser(ctx *app.CheckBrowserSegmentsContext) error {
	sc, ok, err := c.handleCheck(BrowserSegment, ctx.SegmentCode, ctx.BrowserID, ctx.Fields, ctx.Cache)
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}
	return ctx.OK(sc)
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
	ro := model.RuleOverrides{}
	if ctx.Fields != nil {
		overrides := make(map[string]string)
		if err := json.Unmarshal([]byte(*ctx.Fields), &overrides); err != nil {
			return errors.Wrap(err, "invalid format of fields JSON string")
		}
		ro.Fields = overrides
	}
	uc, err := c.SegmentStorage.Users(s, time.Now(), ro)
	if err != nil {
		return err
	}
	return ctx.OK(uc)
}

// handleCheck determines whether provided identifier is part of segment based on given segment type.
func (c *SegmentController) handleCheck(st SegmentType, segmentCode, identifier string, fields, cache *string) (*app.SegmentCheck, bool, error) {
	s, ok, err := c.SegmentStorage.Get(segmentCode)
	if err != nil {
		return nil, false, err
	}
	if !ok {
		return nil, false, nil
	}
	now := time.Now()

	// unmarshal fields and cache
	var ro model.RuleOverrides
	if fields != nil {
		overrides := make(map[string]string)
		if err := json.Unmarshal([]byte(*fields), &overrides); err != nil {
			return nil, false, errors.Wrap(err, "invalid format of fields JSON string")
		}
		ro.Fields = overrides
	}
	var segmentCache model.SegmentCache
	if cache != nil {
		if err := json.Unmarshal([]byte(*cache), &segmentCache); err != nil {
			return nil, false, errors.Wrap(err, "invalid format of cache JSON string")
		}
	}

	// unset invalidated elements
	for id, c := range segmentCache {
		if c.SyncedAt.Before(now.Add(-1 * time.Hour)) {
			delete(segmentCache, id)
		}
	}

	switch st {
	case BrowserSegment:
		segmentCache, ok, err = c.SegmentStorage.CheckBrowser(s, identifier, now, segmentCache, ro)
	case UserSegment:
		segmentCache, ok, err = c.SegmentStorage.CheckUser(s, identifier, now, segmentCache, ro)
	default:
		return nil, false, fmt.Errorf("unhandled segment type: %d", st)
	}

	if err != nil {
		return nil, false, err
	}
	er := c.SegmentStorage.EventRules()
	of := c.SegmentStorage.OverridableFields()
	flags := c.SegmentStorage.Flags()

	return &app.SegmentCheck{
		Check:             ok,
		Cache:             (SegmentCache(segmentCache)).ToMediaType(),
		EventRules:        er,
		OverridableFields: of,
		Flags:             flags,
	}, true, nil
}
