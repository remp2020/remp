package model

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"reflect"
	"time"

	"github.com/jmoiron/sqlx"
	cache "github.com/patrickmn/go-cache"
	"github.com/pkg/errors"
)

const (
	ruleSegmentType     = "rule"
	explicitSegmentType = "explicit"
)

// SegmentStorage represents interface to get segment related data.
type SegmentStorage interface {
	// Create creates new Segment from provided data and returns it.
	Create(sd SegmentData) (*Segment, error)
	// Update updates existing Segment from provided data and returns it.
	Update(id int, sd SegmentData) (*Segment, bool, error)
	// Get returns instance of Segment based on the given code.
	Get(code string) (*Segment, bool, error)
	// GetByID returns instance of Segment based on the given segment ID.
	GetByID(id int) (*Segment, bool, error)
	// List returns all available segments configured via Beam admin.
	List() (SegmentCollection, error)
	// Groups returns all available segment groups.
	Groups() (SegmentGroupCollection, error)
	// CheckUser verifies presence of user within provided segment.
	CheckUser(segment *Segment, userID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error)
	// CheckBrowser verifies presence of browser within provided segment.
	CheckBrowser(segment *Segment, browserID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error)
	// Users return list of all users within segment.
	Users(segment *Segment, now time.Time, ro RuleOverrides) ([]string, error)
	// CountAll returns count of unique tracked users.
	CountAll() (int, error)
	// EventRules returns map of rules assigned to given "category/event" key.
	EventRules() EventRules
	// OverridableFields returns array of fields that expect to be overriden when rule is checked.
	OverridableFields() OverridableFields
	// Flags returns array of flags available for rules.
	Flags() Flags
	// Related compares provided criteria to existing segments and returns segments with same criteria.
	Related(SegmentCriteria) (SegmentCollection, error)
	// BuildRules builds segment rules from segment criteria.
	BuildRules(segment *Segment) ([]SegmentRule, bool, error)
}

// EventRules represent map of rules with given "category/event" assigned
type EventRules map[string][]int

// OverridableFields represent array of fields (key-value pairs) keyed by Rule ID.
type OverridableFields map[int][]string

// Flags represent array of flags keyed by Rule ID.
type Flags map[int]map[string]string

// SegmentCache represents event count information for SegmentRules indexed by SegmentRule ID.
type SegmentCache map[int]*SegmentRuleCache

// Segment structure.
type Segment struct {
	ID int
	SegmentData

	CreatedAt time.Time `db:"created_at"`
	UpdatedAt time.Time `db:"updated_at"`

	Group *SegmentGroup `db:"segment_group"`
	Rules []SegmentRule `db:"segment_rules"`
}

// SegmentData contains data of segment
type SegmentData struct {
	Name           string
	Code           string
	Active         bool
	SegmentGroupID int `db:"segment_group_id"`
	Criteria       sql.NullString
}

// Scan scans provided input to SegmentCriteria.
func (sc *SegmentCriteria) Scan(criteria string) error {
	err := json.Unmarshal([]byte(criteria), sc)
	if err != nil {
		return err
	}

	return nil
}

// SegmentCollection is list of Segments.
type SegmentCollection []*Segment

// SegmentGroup represents metadata about group, in which Segments can be placed in.
type SegmentGroup struct {
	ID        int
	Name      string
	Code      string
	Type      string
	Sorting   int
	CreatedAt time.Time `db:"created_at"`
	UpdatedAt time.Time `db:"updated_at"`
}

// SegmentGroupCollection is list of SegmentGroups.
type SegmentGroupCollection []*SegmentGroup

// User represents information about User in Segment.
type User struct {
	ID    string
	Email string
}

// UserCollection is list of Users.
type UserCollection []*User

// UserSet is set of Users keyed by userID.
type UserSet map[string]bool

// BrowserSet is set of Browser keyed by browserID.
type BrowserSet map[string]bool

// Intersector responds to ability to intersect provided userID with some other collection structure.
type Intersector func(userID string) bool

// SegmentDB represents Segment's storage implementation.
type SegmentDB struct {
	MySQL                    *sqlx.DB
	CountCache               *cache.Cache
	EventStorage             EventStorage
	PageviewStorage          PageviewStorage
	CommerceStorage          CommerceStorage
	Segments                 map[string]*Segment
	ExplicitSegmentsUsers    map[string]UserSet
	ExplicitSegmentsBrowsers map[string]BrowserSet
}

// Create creates new Segment from provided data and returns it.
func (sDB *SegmentDB) Create(sd SegmentData) (*Segment, error) {
	// TODO: insert & get should be in transaction
	_, err := sDB.MySQL.NamedExec(`
		INSERT INTO segments (name, code, active, segment_group_id, criteria, created_at, updated_at)
		VALUES (:name, :code, :active, :segment_group_id, :criteria, :created_at, :updated_at)`,
		map[string]interface{}{
			"name":             sd.Name,
			"code":             sd.Code,
			"active":           sd.Active,
			"segment_group_id": sd.SegmentGroupID,
			"criteria":         sd.Criteria,
			"created_at":       time.Now(),
			"updated_at":       time.Now(),
		})
	if err != nil {
		return nil, err
	}

	s, ok, err := sDB.Get(sd.Code)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, errors.New("transaction error: unable to load created segment")
	}
	return s, nil
}

// Update updates existing Segment from provided data and returns it.
func (sDB *SegmentDB) Update(id int, sd SegmentData) (*Segment, bool, error) {
	// TODO: get & update & get should be in transaction

	// find out if provided ID belongs to some segment
	_, ok, err := sDB.GetByID(id)
	if err != nil {
		return nil, false, err
	}
	if !ok {
		return nil, false, nil
	}

	// update segment
	_, err = sDB.MySQL.NamedExec(`
		UPDATE segments
		SET
			name = :name,
			active = :active,
			segment_group_id = :segment_group_id,
			criteria = :criteria,
			updated_at = :updated_at
		WHERE id = :id
		`,
		map[string]interface{}{
			"name":             sd.Name,
			"active":           sd.Active,
			"segment_group_id": sd.SegmentGroupID,
			"criteria":         sd.Criteria,
			"updated_at":       time.Now(),
			"id":               id,
		})
	if err != nil {
		return nil, false, err
	}

	// reload segment
	s, ok, err := sDB.GetByID(id)
	if err != nil {
		return nil, false, err
	}
	if !ok {
		return nil, false, errors.New("transaction error: unable to load updated segment")
	}
	return s, true, nil
}

// Get returns instance of Segment based on the given code.
func (sDB *SegmentDB) Get(code string) (*Segment, bool, error) {
	p, ok := sDB.Segments[code]
	if ok {
		return p, true, nil
	}

	s := &Segment{}
	err := sDB.MySQL.Get(s, `
	SELECT
		segments.*,
		segment_groups.id AS 'segment_group.id',
		segment_groups.name AS 'segment_group.name',
		segment_groups.code AS 'segment_group.code',
		segment_groups.type AS 'segment_group.type',
		segment_groups.sorting AS 'segment_group.sorting'
	FROM segments
	JOIN segment_groups ON segments.segment_group_id = segment_groups.id
	WHERE segments.code = ?
	AND segments.active = 1
	`, code)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, false, nil
		}
		return nil, false, errors.Wrap(err, "unable to get segment from MySQL")
	}

	src, err := sDB.loadSegmentRules(s)
	if err != nil {
		return nil, false, err
	}
	s.Rules = src

	return s, true, nil
}

// GetByID returns instance of Segment based on the given segment ID.
// TODO: how to get segment from sDB.Segments?
func (sDB *SegmentDB) GetByID(id int) (*Segment, bool, error) {
	s := &Segment{}
	err := sDB.MySQL.Get(s, `
	SELECT
		segments.*,
		segment_groups.id AS 'segment_group.id',
		segment_groups.name AS 'segment_group.name',
		segment_groups.code AS 'segment_group.code',
		segment_groups.type AS 'segment_group.type',
		segment_groups.sorting AS 'segment_group.sorting'
	FROM segments
	JOIN segment_groups ON segments.segment_group_id = segment_groups.id
	WHERE segments.id = ?
	AND segments.active = 1
	`, id)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, false, nil
		}
		return nil, false, errors.Wrap(err, "unable to get segment from MySQL")
	}

	src, err := sDB.loadSegmentRules(s)
	if err != nil {
		return nil, false, err
	}
	s.Rules = src

	return s, true, nil
}

// List returns all available segments configured via Beam admin.
func (sDB *SegmentDB) List() (SegmentCollection, error) {
	sc := SegmentCollection{}
	err := sDB.MySQL.Select(&sc, "SELECT segments.id, segments.name, segments.code, segments.segment_group_id, "+
		"segment_groups.id AS 'segment_group.id', "+
		"segment_groups.name AS 'segment_group.name', "+
		"segment_groups.code AS 'segment_group.code', "+
		"segment_groups.type AS 'segment_group.type', "+
		"segment_groups.sorting AS 'segment_group.sorting' "+
		"FROM segments JOIN segment_groups ON segments.segment_group_id = segment_groups.id "+
		"WHERE segments.active = 1")
	if err != nil {
		return nil, err
	}
	return sc, nil
}

// Groups returns all available segment groups configured via Beam admin.
func (sDB *SegmentDB) Groups() (SegmentGroupCollection, error) {
	sgc := SegmentGroupCollection{}
	err := sDB.MySQL.Select(&sgc, "SELECT id, name, code, type, sorting, created_at, updated_at "+
		"FROM segment_groups ORDER BY sorting")
	if err != nil {
		return nil, err
	}
	return sgc, nil
}

// CheckUser verifies presence of user within provided segment.
func (sDB *SegmentDB) CheckUser(segment *Segment, userID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error) {
	if segment.Group.Type == explicitSegmentType {
		if _, ok := sDB.ExplicitSegmentsUsers[segment.Code]; !ok {
			// if segment is not present in the cache, reload
			sDB.CacheExplicitSegments()
		}
		segmentUsers, ok := sDB.ExplicitSegmentsUsers[segment.Code]
		if !ok {
			return cache, false, nil
		}
		_, ok = segmentUsers[userID]
		return cache, ok, nil
	}
	return sDB.check(segment, "user_id", userID, now, cache, ro)
}

// CheckBrowser verifies presence of browser within provided segment.
func (sDB *SegmentDB) CheckBrowser(segment *Segment, browserID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error) {
	if segment.Group.Type == explicitSegmentType {
		if _, ok := sDB.ExplicitSegmentsBrowsers[segment.Code]; !ok {
			// if segment is not present in the cache, reload
			sDB.CacheExplicitSegments()
		}
		segmentBrowsers, ok := sDB.ExplicitSegmentsBrowsers[segment.Code]
		if !ok {
			return cache, false, nil
		}
		_, ok = segmentBrowsers[browserID]
		return cache, ok, nil
	}
	return sDB.check(segment, "browser_id", browserID, now, cache, ro)
}

// Check verifies presence of provided tag within segment by its value.
func (sDB *SegmentDB) check(segment *Segment, tagName, tagValue string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error) {
	c := make(SegmentCache)

	// copy cache to new instance to prevent mutability of original cache
	for key, val := range cache {
		c[key] = &SegmentRuleCache{
			Count:    val.Count,
			SyncedAt: val.SyncedAt,
		}
	}

	for _, sr := range segment.Rules {
		osr := sr.applyOverrides(ro)

		cacheKey := sr.getCacheKey(ro)
		src, ok := cache[cacheKey]

		// get count
		var count int
		var err error
		if sr.cacheable() && ok {
			count = src.Count
			// update cache
			c[cacheKey] = &SegmentRuleCache{
				Count:    count,
				SyncedAt: cache[cacheKey].SyncedAt,
			}
		} else {
			count, err = sDB.getRuleEventCount(osr, tagName, tagValue, now, ro)
			if err != nil {
				return nil, false, errors.Wrap(err, "unable to get SegmentRule event count")
			}
			// set synced cache
			c[cacheKey] = &SegmentRuleCache{
				Count:    count,
				SyncedAt: now,
			}
		}

		// evaluate
		ok, err = osr.Evaluate(count)
		if err != nil {
			return nil, false, errors.Wrap(err, "unable to evaluate SegmentRule")
		}

		// if user doesn't match current rule, no need to evaluate further
		if !ok {
			return c, ok, nil
		}
	}

	return c, true, nil
}

// getRuleEventCount returns real db-based number of events occurred based on provided SegmentRule.
func (sDB *SegmentDB) getRuleEventCount(sr *SegmentRule, tagName, tagValue string, now time.Time, ro RuleOverrides) (int, error) {
	options := sr.options(now, ro)
	options.FilterBy = append(options.FilterBy, &FilterBy{Tag: tagName, Values: []string{tagValue}})

	var crc CountRowCollection
	var ok bool
	var err error

	switch sr.EventCategory {
	case CategoryPageview:
		crc, ok, err = sDB.PageviewStorage.Count(options)
	case CategoryCommerce:
		crc, ok, err = sDB.CommerceStorage.Count(options)
	default:
		crc, ok, err = sDB.EventStorage.Count(options)
	}

	if err != nil {
		return 0, errors.Wrap(err, "unable to get rule event count")
	}
	if !ok {
		return 0, nil
	}

	// result read
	if len(crc) > 1 {
		return 0, fmt.Errorf("unexpected result of CountRows returned: %d", len(crc))
	}

	return crc[0].Count, nil
}

// CountAll returns count of unique tracked users.
func (sDB *SegmentDB) CountAll() (int, error) {
	// return cached count of all
	if allCache, ok := sDB.CountCache.Get("all"); ok {
		if all, ok := allCache.(int); ok {
			return all, nil
		}
	}

	var pageviews int
	var o AggregateOptions
	o.Action = ActionPageviewLoad

	src, ok, err := sDB.PageviewStorage.Unique(o, UniqueCountUsers)
	if err != nil {
		return 0, err
	}
	if ok {
		pageviews = src[0].Count
	}

	sDB.CountCache.Set("all", pageviews, time.Hour)
	log.Println("segment's count all cache reloaded")
	return pageviews, nil
}

// Users return list of all users within segment.
func (sDB *SegmentDB) Users(segment *Segment, now time.Time, ro RuleOverrides) ([]string, error) {
	if segment.Group.Type == explicitSegmentType {
		uc := []string{}
		for userID := range sDB.ExplicitSegmentsUsers[segment.Code] {
			uc = append(uc, userID)
		}
		return uc, nil
	}

	users := make(UserSet)

	for i, sr := range segment.Rules {
		filteredUsers, err := sDB.ruleUsers(sr, now, ro, func(userID string) bool {
			// on first iteration everyone is eligible to be in "users"
			if i == 0 {
				return true
			}
			// on further iterations user needs to be present in "users" (effectively all previous iterations)
			_, ok := users[userID]
			return ok
		})
		if err != nil {
			return nil, err
		}
		users = filteredUsers
	}

	uc := []string{}
	for userID := range users {
		uc = append(uc, userID)
	}

	return uc, nil
}

// ruleUsers lists all users based on SegmentRule and filters them based on the provided Intersector.
func (sDB *SegmentDB) ruleUsers(sr SegmentRule, now time.Time, ro RuleOverrides, intersect Intersector) (UserSet, error) {
	options := sr.options(now, ro)
	options.GroupBy = append(options.GroupBy, "user_id")

	um := make(UserSet)

	var crc CountRowCollection
	var ok bool
	var err error

	switch sr.EventCategory {
	case CategoryPageview:
		crc, ok, err = sDB.PageviewStorage.Count(options)
	case CategoryCommerce:
		crc, ok, err = sDB.CommerceStorage.Count(options)
	default:
		crc, ok, err = sDB.EventStorage.Count(options)
	}

	if err != nil {
		return nil, errors.Wrap(err, "unable to get rule event count")
	}
	if !ok {
		return um, nil
	}

	for _, cr := range crc {
		evalResult, err := sr.Evaluate(cr.Count)
		if err != nil {
			return nil, err
		}
		if !evalResult {
			continue
		}
		userID := cr.Tags["user_id"]
		if intersect(userID) {
			um[userID] = true
		}
	}

	return um, nil
}

// Cache stores the segments in memory.
func (sDB *SegmentDB) Cache() error {
	sm := make(map[string]*Segment)

	// prepare map of all active segment groups
	sgc := SegmentGroupCollection{}
	err := sDB.MySQL.Select(&sgc, "SELECT segment_groups.* FROM segment_groups INNER JOIN segments ON segment_group_id = segment_groups.id WHERE segments.active = 1")
	if err != nil {
		if err == sql.ErrNoRows {
			return nil
		}
		return errors.Wrap(err, "unable to cache segment groups from MySQL")
	}
	sgm := make(map[int]*SegmentGroup)
	for _, sg := range sgc {
		sgm[sg.ID] = sg
	}

	// load all active segments with groups and rules
	sc := SegmentCollection{}
	err = sDB.MySQL.Select(&sc, "SELECT * FROM segments WHERE active = 1")
	if err != nil {
		if err == sql.ErrNoRows {
			return nil
		}
		return errors.Wrap(err, "unable to cache segments from MySQL")
	}

	for _, s := range sc {
		sg, ok := sgm[s.SegmentGroupID]
		if !ok {
			sg = &SegmentGroup{}
			err = sDB.MySQL.Get(sg, "SELECT * FROM segment_groups WHERE id = ?", s.SegmentGroupID)
			if err != nil {
				return errors.Wrap(err, fmt.Sprintf("unable to get related segment group for segment [%d]", s.ID))
			}
			sgm[s.SegmentGroupID] = sg
		}
		s.Group = sg
	}

	for _, s := range sc {
		if s.Group.Type != ruleSegmentType {
			// don't try to load rules for segments not working with rules
			continue
		}
		src, err := sDB.loadSegmentRules(s)
		if err != nil {
			return err
		}
		s.Rules = src
	}

	old := sDB.Segments
	for _, s := range sc {
		sm[s.Code] = s
	}
	sDB.Segments = sm

	if !reflect.DeepEqual(old, sm) {
		log.Println("segment cache reloaded")
	}
	return nil
}

// CacheExplicitSegments caches segments data in memory
func (sDB *SegmentDB) CacheExplicitSegments() error {
	usersSet := make(map[string]UserSet)
	browsersSet := make(map[string]BrowserSet)

	for code, s := range sDB.Segments {
		if s.Group.Type == explicitSegmentType {
			// load users
			rows, err := sDB.MySQL.Query("SELECT user_id FROM segment_users WHERE segment_id = ?", s.ID)
			if err != nil {
				if err == sql.ErrNoRows {
					continue
				}
				return errors.Wrap(err, fmt.Sprintf("unable to get segment users from MySQL [%d]", s.ID))
			}
			users := make(UserSet)
			for rows.Next() {
				var userID string
				err = rows.Scan(&userID)
				if err != nil {
					return errors.Wrap(err, "unable to load user ID from result row")
				}
				users[userID] = true
			}
			usersSet[code] = users

			// load browsers
			rows, err = sDB.MySQL.Query("SELECT browser_id FROM segment_browsers WHERE segment_id = ?", s.ID)
			if err != nil {
				if err == sql.ErrNoRows {
					continue
				}
				return errors.Wrap(err, fmt.Sprintf("unable to get segment browsers from MySQL [%d]", s.ID))
			}
			browsers := make(BrowserSet)
			for rows.Next() {
				var browserID string
				err = rows.Scan(&browserID)
				if err != nil {
					return errors.Wrap(err, "unable to load browser ID from result row")
				}
				browsers[browserID] = true
			}
			browsersSet[code] = browsers
		}
	}
	sDB.ExplicitSegmentsUsers = usersSet
	sDB.ExplicitSegmentsBrowsers = browsersSet
	return nil
}

// EventRules returns map of rules assigned to given "category/event" key
func (sDB *SegmentDB) EventRules() EventRules {
	er := make(EventRules)
	for _, s := range sDB.Segments {
		for _, sr := range s.Rules {
			if !sr.cacheable() {
				continue
			}
			key := fmt.Sprintf("%s/%s", sr.EventCategory, sr.EventAction)
			er[key] = append(er[key], sr.ID)
		}
	}
	return er
}

// OverridableFields returns array of fields that expect to be overriden when rule is checked.
func (sDB *SegmentDB) OverridableFields() OverridableFields {
	of := make(OverridableFields)
	for _, s := range sDB.Segments {
		for _, sr := range s.Rules {
			of[sr.ID] = sr.overridableFields()
		}
	}
	return of
}

// Flags returns array of flags available for rules.
func (sDB *SegmentDB) Flags() Flags {
	flags := make(Flags)
	for _, s := range sDB.Segments {
		for _, sr := range s.Rules {
			flags[sr.ID] = sr.flags()
		}
	}
	return flags
}

// Related compares provided criteria to existing segments and returns segments with same criteria.
func (sDB *SegmentDB) Related(criteria SegmentCriteria) (SegmentCollection, error) {
	sc := SegmentCollection{}
	err := sDB.MySQL.Select(&sc, "SELECT * FROM segments WHERE criteria IS NOT NULL")
	if err != nil {
		return nil, err
	}

	scRelated := SegmentCollection{}
	for _, s := range sc {
		if !s.Criteria.Valid {
			continue
		}

		sCriteria := SegmentCriteria{}
		err := sCriteria.Scan(s.Criteria.String)
		if err != nil {
			return nil, errors.Wrap(err, "unable to scan criteria")
		}

		// comparing only nodes, version can be different
		if reflect.DeepEqual(criteria.Nodes, sCriteria.Nodes) {
			scRelated = append(scRelated, s)
		}
	}

	return scRelated, nil
}

// loadSegmentRules loads SegmentRules from segment_rules table.
// If no segment rules exist, but Segment contains criteria, it tries to build segment rules.
func (sDB *SegmentDB) loadSegmentRules(s *Segment) ([]SegmentRule, error) {
	src := []SegmentRule{}
	err := sDB.MySQL.Select(&src, "SELECT * FROM segment_rules WHERE segment_id = ?", s.ID)
	if err != nil {
		if err != sql.ErrNoRows {
			return nil, errors.Wrap(err, fmt.Sprintf("unable to get related segment rules for segment [%d]", s.ID))
		}
	}

	if len(src) == 0 && s.Criteria.Valid {
		var ok bool
		var err error
		src, ok, err = sDB.BuildRules(s)
		if err != nil {
			return nil, errors.Wrap(err, "unable to build segment rules from criteria")
		}
		if !ok {
			return nil, errors.New("unable to build segment rules from criteria")
		}
	}

	return src, nil
}
