package controller

import (
	"beam/cmd/segments/app"
	"beam/model"
	"database/sql"
	"encoding/json"
	"fmt"
	"time"

	"github.com/goadesign/goa"
	"github.com/pkg/errors"
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
	SegmentStorage          model.SegmentStorage
	SegmentBlueprintStorage model.SegmentBlueprintStorage
	Config                  SegmentConfig
}

// NewSegmentController creates a segment controller.
func NewSegmentController(
	service *goa.Service,
	segmentStorage model.SegmentStorage,
	segmentBlueprintStorage model.SegmentBlueprintStorage,
	config SegmentConfig,
) *SegmentController {
	return &SegmentController{
		Controller:              service.NewController("SegmentController"),
		SegmentStorage:          segmentStorage,
		SegmentBlueprintStorage: segmentBlueprintStorage,
		Config:                  config,
	}
}

// SegmentConfig represent configuration settings of Segment controller.
type SegmentConfig struct {
	URLEdit string
}

// Get runs the get action.
func (c *SegmentController) Get(ctx *app.GetSegmentsContext) error {
	s, ok, err := c.SegmentStorage.GetByID(ctx.ID)
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}

	response, err := (*Segment)(s).ToSegmenterMediaType()
	if err != nil {
		return err
	}

	return ctx.OK(&app.SegmentersSegment{
		Status:  "ok",
		Segment: response,
	})
}

// List runs the list action.
func (c *SegmentController) List(ctx *app.ListSegmentsContext) error {
	sc, err := c.SegmentStorage.List()
	if err != nil {
		return err
	}

	return ctx.OKTiny((SegmentCollection)(sc).ToTinyMediaType())
}

// Groups runs the groups action.
func (c *SegmentController) Groups(ctx *app.GroupsSegmentsContext) error {
	sgc, err := c.SegmentStorage.Groups()
	if err != nil {
		return err
	}
	return ctx.OK(&app.SegmentGroupsFallback{
		Status: "ok",
		Groups: (SegmentGroupCollection)(sgc).ToMediaType(),
	})
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

// CheckBrowser runs the check_browser action.
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

// Criteria runs the criteria action.
func (c *SegmentController) Criteria(ctx *app.CriteriaSegmentsContext) error {
	sbtc, err := c.SegmentBlueprintStorage.Get()
	if err != nil {
		return err
	}
	mtsbtc := (SegmentBlueprintTableCollection)(sbtc).ToMediaType()

	mtsb := &app.SegmentBlueprint{
		Blueprint: mtsbtc,
	}

	return ctx.OK(mtsb)
}

// CreateOrUpdate runs the create_or_update action.
func (c *SegmentController) CreateOrUpdate(ctx *app.CreateOrUpdateSegmentsContext) error {
	if ctx.ID != nil {
		return c.handleUpdate(ctx)
	}
	return c.handleCreate(ctx)
}

// Count runs the count action.
func (c *SegmentController) Count(ctx *app.CountSegmentsContext) error {
	var usersCount int
	if ctx.Payload.Criteria != nil && len(ctx.Payload.Criteria.Nodes) != 0 && len(ctx.Payload.Criteria.Nodes[0].Nodes) != 0 {
		var s model.Segment

		criteriaJSON, err := json.Marshal(ctx.Payload.Criteria)
		if err != nil {
			return errors.Wrap(err, "unable to marshal segment's criteria payload")
		}

		s.SegmentData = model.SegmentData{
			Criteria: sql.NullString{
				String: string(criteriaJSON),
				Valid:  true,
			},
		}
		sr, ok, err := c.SegmentStorage.BuildRules(&s)
		if err != nil {
			return nil
		}
		if ok {
			s.Rules = sr
			users, err := c.SegmentStorage.Users(&s, time.Now(), model.RuleOverrides{})
			if err != nil {
				return err
			}
			usersCount = len(users)
		}
	} else {
		ca, err := c.SegmentStorage.CountAll()
		if err != nil {
			return err
		}
		usersCount = ca
	}

	return ctx.OK(&app.SegmentCount{
		Count:  usersCount,
		Status: "ok",
	})
}

// Related runs the related action.
func (c *SegmentController) Related(ctx *app.RelatedSegmentsContext) error {
	// scan whole criteria as SegmentCriteria
	cJSON, err := json.Marshal(ctx.Payload.Criteria)
	if err != nil {
		return err
	}
	var criteria model.SegmentCriteria
	err = criteria.Scan(string(cJSON))
	if err != nil {
		return err
	}

	sc, err := c.SegmentStorage.Related(criteria)
	if err != nil {
		return err
	}

	mt := app.SegmentsRelated{
		Segments: (SegmentCollection)(sc).ToExtendedMediaType(c.Config.URLEdit),
	}
	return ctx.OK(&mt)
}

// handleCreate handles creation of Segment.
func (c *SegmentController) handleCreate(ctx *app.CreateOrUpdateSegmentsContext) error {
	p := ctx.Payload

	criteriaJSON, err := json.Marshal(ctx.Payload.Criteria)
	if err != nil {
		return errors.Wrap(err, "unable to marshal segment's criteria payload")
	}

	// TODO: maybe code should be also part of payload? check with CRM
	code, err := model.Webalize(p.Name)
	if err != nil {
		return err
	}

	sd := model.SegmentData{
		Name:           p.Name,
		Code:           code,
		Active:         true,
		SegmentGroupID: p.GroupID,
		Criteria: sql.NullString{
			String: string(criteriaJSON),
			Valid:  true,
		},
	}
	s, err := c.SegmentStorage.Create(sd)
	if err != nil {
		return err
	}

	response, err := (*Segment)(s).ToMediaType()
	if err != nil {
		return err
	}
	return ctx.OK(response)
}

// handleUpdate handles update of Segment.
func (c *SegmentController) handleUpdate(ctx *app.CreateOrUpdateSegmentsContext) error {
	p := ctx.Payload

	criteriaJSON, err := json.Marshal(ctx.Payload.Criteria)
	if err != nil {
		return errors.Wrap(err, "unable to marshal segment's criteria payload")
	}
	sd := model.SegmentData{
		Name:           p.Name,
		Active:         true,
		SegmentGroupID: p.GroupID,
		Criteria: sql.NullString{
			String: string(criteriaJSON),
			Valid:  true,
		},
	}
	s, ok, err := c.SegmentStorage.Update(*ctx.ID, sd)
	if err != nil {
		return err
	}
	if !ok {
		return ctx.NotFound()
	}

	response, err := (*Segment)(s).ToMediaType()
	if err != nil {
		return err
	}
	return ctx.OK(response)
}

// handleCheck determines whether provided identifier is part of segment based on given segment type.
func (c *SegmentController) handleCheck(segmentType SegmentType, segmentCode, identifier string, fields, cache *string) (*app.SegmentCheck, bool, error) {
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

	switch segmentType {
	case BrowserSegment:
		// unset invalidated elements, keeping the cache longer as single-browser doesn't need count syncing that often (no other devices affect the count)
		for id, c := range segmentCache {
			if c.SyncedAt.Before(now.Add(-24 * time.Hour)) {
				delete(segmentCache, id)
			}
		}
		segmentCache, ok, err = c.SegmentStorage.CheckBrowser(s, identifier, now, segmentCache, ro)
	case UserSegment:
		// unset invalidated elements, removing the cache after one hour to sync count which could include other user's devices
		for id, c := range segmentCache {
			if c.SyncedAt.Before(now.Add(-1 * time.Hour)) {
				delete(segmentCache, id)
			}
		}
		segmentCache, ok, err = c.SegmentStorage.CheckUser(s, identifier, now, segmentCache, ro)
	default:
		return nil, false, fmt.Errorf("unhandled segment type: %d", segmentType)
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
