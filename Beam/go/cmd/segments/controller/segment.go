package controller

import (
	"beam/cmd/segments/gen/segments"
	"beam/model"
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"github.com/pkg/errors"
	"time"
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
	SegmentStorage          model.SegmentStorage
	SegmentBlueprintStorage model.SegmentBlueprintStorage
	Config                  SegmentConfig
}

// SegmentConfig represent configuration settings of Segment controller.
type SegmentConfig struct {
	URLEdit string
}

// NewSegmentController creates a segment controller.
func NewSegmentController(
	segmentStorage model.SegmentStorage,
	segmentBlueprintStorage model.SegmentBlueprintStorage,
	config SegmentConfig,
) *SegmentController {
	return &SegmentController{
		SegmentStorage:          segmentStorage,
		SegmentBlueprintStorage: segmentBlueprintStorage,
		Config:                  config,
	}
}

// Get segment
func (c *SegmentController) Get(ctx context.Context, p *segments.GetPayload) (res *segments.SegmentersSegment, err error) {
	s, ok, err := c.SegmentStorage.GetByID(p.ID)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, segments.MakeNotFound(errors.New(fmt.Sprintf("Segment [%d] not found", p.ID)))
	}

	response, err := (*Segment)(s).ToSegmenterMediaType()
	if err != nil {
		return nil, err
	}

	return &segments.SegmentersSegment{
		Status:  "ok",
		Segment: response,
	}, nil
}

// List runs the list action.
func (c *SegmentController) List(ctx context.Context) (res segments.SegmentCollection, err error) {
	sc, err := c.SegmentStorage.List()
	if err != nil {
		return nil, err
	}
	return (SegmentCollection)(sc).ToTinyMediaType(), nil
}

// Groups runs the groups action.
func (c *SegmentController) Groups(ctx context.Context) (res *segments.SegmentGroupsFallback, err error) {
	sgc, err := c.SegmentStorage.Groups()
	if err != nil {
		return nil, err
	}
	return &segments.SegmentGroupsFallback{
		Status: "ok",
		Groups: (SegmentGroupCollection)(sgc).ToMediaType(),
	}, nil
}

// CheckUser runs the check_user action.
func (c *SegmentController) CheckUser(ctx context.Context, p *segments.CheckUserPayload) (res *segments.SegmentCheck, err error) {
	sc, ok, err := c.handleCheck(UserSegment, *p.SegmentCode, *p.UserID, p.Fields, p.Cache)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, segments.MakeNotFound(errors.New(fmt.Sprintf("Segment with code [%s] not found", *p.SegmentCode)))
	}
	return sc, nil
}

// CheckBrowser runs the check_browser action.
func (c *SegmentController) CheckBrowser(ctx context.Context, p *segments.CheckBrowserPayload) (res *segments.SegmentCheck, err error) {
	sc, ok, err := c.handleCheck(BrowserSegment, *p.SegmentCode, *p.BrowserID, p.Fields, p.Cache)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, segments.MakeNotFound(errors.New(fmt.Sprintf("Segment with code [%s] not found", *p.SegmentCode)))
	}
	return sc, nil
}

// Users runs the users action.
func (c *SegmentController) Users(ctx context.Context, p *segments.UsersPayload) (res []string, err error) {
	s, ok, err := c.SegmentStorage.Get(*p.SegmentCode)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, segments.MakeNotFound(errors.New(fmt.Sprintf("Segment with code [%s] not found", *p.SegmentCode)))
	}
	ro := model.RuleOverrides{}
	if p.Fields != nil {
		overrides := make(map[string]string)
		if err := json.Unmarshal([]byte(*p.Fields), &overrides); err != nil {
			return nil, errors.Wrap(err, "invalid format of fields JSON string")
		}
		ro.Fields = overrides
	}
	uc, err := c.SegmentStorage.Users(s, time.Now(), ro)
	if err != nil {
		return nil, err
	}
	return uc, nil
}

// Criteria runs the criteria action.
func (c *SegmentController) Criteria(ctx context.Context) (res *segments.SegmentBlueprint, err error) {
	sbtc, err := c.SegmentBlueprintStorage.Get()
	if err != nil {
		return nil, err
	}
	mtsbtc := (SegmentBlueprintTableCollection)(sbtc).ToMediaType()

	mtsb := &segments.SegmentBlueprint{
		Blueprint: mtsbtc,
	}

	return mtsb, nil
}

// CreateOrUpdate runs the create_or_update action.
func (c *SegmentController) CreateOrUpdate(ctx context.Context, p *segments.SegmentPayload) (res *segments.Segment, view string, err error) {
	if p.ID != nil {
		segment, err := c.handleUpdate(p)
		return segment, "default", err
	}
	segment, err := c.handleCreate(p)
	return segment, "default", err
}

// Count runs the count action.
func (c *SegmentController) Count(ctx context.Context, p *segments.SegmentTinyPayload) (res *segments.SegmentCount, err error) {
	var usersCount int
	if p.Criteria != nil && len(p.Criteria.Nodes) != 0 && len(p.Criteria.Nodes[0].Nodes) != 0 {
		var s model.Segment

		criteriaJSON, err := json.Marshal(p.Criteria)
		if err != nil {
			return nil, errors.Wrap(err, "unable to marshal segment's criteria payload")
		}

		s.SegmentData = model.SegmentData{
			Criteria: sql.NullString{
				String: string(criteriaJSON),
				Valid:  true,
			},
		}
		sr, ok, err := c.SegmentStorage.BuildRules(&s)
		if err != nil {
			return nil, err
		}
		if ok {
			s.Rules = sr
			users, err := c.SegmentStorage.Users(&s, time.Now(), model.RuleOverrides{})
			if err != nil {
				return nil, err
			}
			usersCount = len(users)
		}
	} else {
		ca, err := c.SegmentStorage.CountAll()
		if err != nil {
			return nil, err
		}
		usersCount = ca
	}

	return &segments.SegmentCount{
		Count:  usersCount,
		Status: "ok",
	}, nil
}

// Returns segments with same or similar criteria
func (c *SegmentController) Related(ctx context.Context, p *segments.SegmentTinyPayload) (res *segments.SegmentsRelated, err error) {
	// scan whole criteria as SegmentCriteria
	cJSON, err := json.Marshal(p.Criteria)
	if err != nil {
		return nil, err
	}
	var criteria model.SegmentCriteria
	err = criteria.Scan(string(cJSON))
	if err != nil {
		return nil, err
	}

	sc, err := c.SegmentStorage.Related(criteria)
	if err != nil {
		return nil, err
	}

	mt := segments.SegmentsRelated{
		Segments: (SegmentCollection)(sc).ToExtendedMediaType(c.Config.URLEdit),
	}
	return &mt, nil
}

// handleCreate handles creation of Segment.
func (c *SegmentController) handleCreate(p *segments.SegmentPayload) (*segments.Segment, error) {

	criteriaJSON, err := json.Marshal(p.Criteria)
	if err != nil {
		return nil, errors.Wrap(err, "unable to marshal segment's criteria payload")
	}

	// TODO: maybe code should be also part of payload? check with CRM
	code, err := model.Webalize(p.Name)
	if err != nil {
		return nil, err
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
		return nil, err
	}

	response, err := (*Segment)(s).ToMediaType()
	if err != nil {
		return nil, err
	}
	return response, nil
}

// handleUpdate handles update of Segment.
func (c *SegmentController) handleUpdate(p *segments.SegmentPayload) (*segments.Segment, error) {
	criteriaJSON, err := json.Marshal(p.Criteria)
	if err != nil {
		return nil, errors.Wrap(err, "unable to marshal segment's criteria payload")
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
	s, ok, err := c.SegmentStorage.Update(*p.ID, sd)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, segments.MakeNotFound(errors.New(fmt.Sprintf("Segment [%d] not found", *p.ID)))
	}

	response, err := (*Segment)(s).ToMediaType()
	if err != nil {
		return nil, err
	}
	return response, nil
}

// handleCheck determines whether provided identifier is part of segment based on given segment type.
func (c *SegmentController) handleCheck(segmentType SegmentType, segmentCode, identifier string, fields, cache *string) (*segments.SegmentCheck, bool, error) {
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

	return &segments.SegmentCheck{
		Check:             ok,
		Cache:             (SegmentCache(segmentCache)).ToMediaType(),
		EventRules:        er,
		OverridableFields: of,
		Flags:             flags,
	}, true, nil
}
