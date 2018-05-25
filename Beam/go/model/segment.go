package model

import (
	"database/sql"
	"fmt"
	"log"
	"reflect"
	"time"

	"github.com/jmoiron/sqlx"
	"github.com/patrickmn/go-cache"
	"github.com/pkg/errors"
)

// SegmentStorage represents interface to get segment related data.
type SegmentStorage interface {
	// Get returns instance of Segment based on the given code.
	Get(code string) (*Segment, bool, error)
	// List returns all available segments configured via Beam admin.
	List() (SegmentCollection, error)
	// CheckUser verifies presence of user within provided segment.
	CheckUser(segment *Segment, userID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error)
	// CheckBrowser verifies presence of browser within provided segment.
	CheckBrowser(segment *Segment, browserID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error)
	// Users return list of all users within segment.
	Users(segment *Segment, now time.Time, ro RuleOverrides) ([]string, error)
	// EventRules returns map of rules assigned to given "category/event" key.
	EventRules() EventRules
	// OverridableFields returns array of fields that expect to be overriden when rule is checked.
	OverridableFields() OverridableFields
	// Flags returns array of flags available for rules.
	Flags() Flags
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
	ID        int
	Code      string
	Name      string
	Active    bool
	CreatedAt time.Time `db:"created_at"`
	UpdatedAt time.Time `db:"updated_at"`

	Group *SegmentGroup
	Rules []SegmentRule `db:"segment_rules"`
}

// SegmentCollection is list of Segments.
type SegmentCollection []*Segment

// SegmentGroup represents metadata about group, in which Segments can be placed in.
type SegmentGroup struct {
	ID      int
	Name    string
	Sorting int
}

// User represents information about User in Segment.
type User struct {
	ID    string
	Email string
}

// UserCollection is list of Users.
type UserCollection []*User

// UserSet is set of Users keyed by userID.
type UserSet map[string]bool

// Intersector responds to ability to intersect provided userID with some other collection structure.
type Intersector func(userID string) bool

// SegmentDB represents Segment's storage MySQL/InfluxDB implementation.
type SegmentDB struct {
	MySQL           *sqlx.DB
	RuleCountCache  *cache.Cache
	EventStorage    EventStorage
	PageviewStorage PageviewStorage
	CommerceStorage CommerceStorage
	Segments        map[string]*Segment
}

// Get returns instance of Segment based on the given code.
func (sDB *SegmentDB) Get(code string) (*Segment, bool, error) {
	p, ok := sDB.Segments[code]
	if ok {
		return p, true, nil
	}

	s := &Segment{}
	err := sDB.MySQL.Get(s, "SELECT * FROM segments WHERE code = ?", code)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, false, nil
		}
		return nil, false, errors.Wrap(err, "unable to get segment from MySQL")
	}

	src := []SegmentRule{}
	err = sDB.MySQL.Select(&src, "SELECT * FROM segment_rules WHERE segment_id = ?", s.ID)
	if err != nil {
		if err != sql.ErrNoRows {
			return nil, false, errors.Wrap(err, fmt.Sprintf("unable to get related segment rules for segment [%d]", s.ID))
		}
	}
	s.Rules = src

	return s, true, nil
}

// List returns all available segments configured via Beam admin.
func (sDB *SegmentDB) List() (SegmentCollection, error) {
	sc := SegmentCollection{}
	err := sDB.MySQL.Select(&sc, "SELECT name, code FROM segments")
	if err != nil {
		return nil, err
	}
	return sc, nil
}

// CheckUser verifies presence of user within provided segment.
func (sDB *SegmentDB) CheckUser(segment *Segment, userID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error) {
	return sDB.check(segment, "user_id", userID, now, cache, ro)
}

// CheckBrowser verifies presence of browser within provided segment.
func (sDB *SegmentDB) CheckBrowser(segment *Segment, browserID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error) {
	return sDB.check(segment, "browser_id", browserID, now, cache, ro)
}

// Check verifies presence of provided tag within segment by its value.
func (sDB *SegmentDB) check(segment *Segment, tagName, tagValue string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error) {
	c := make(SegmentCache)

	for _, sr := range segment.Rules {
		osr := sr.applyOverrides(ro)
		cacheKey := sr.getCacheKey(ro)

		// get count
		var count int
		var err error
		if src, ok := cache[cacheKey]; ok {
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
		ok, err := osr.Evaluate(count)
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
	options.FilterBy = append(options.FilterBy, &FilterBy{tagName, []string{tagValue}})

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

// Users return list of all users within segment.
func (sDB *SegmentDB) Users(segment *Segment, now time.Time, ro RuleOverrides) ([]string, error) {
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
	sc := SegmentCollection{}

	err := sDB.MySQL.Select(&sc, "SELECT * FROM segments WHERE active = 1")
	if err != nil {
		if err == sql.ErrNoRows {
			return nil
		}
		return errors.Wrap(err, "unable to cache segments from MySQL")
	}

	for _, s := range sc {
		src := []SegmentRule{}
		err = sDB.MySQL.Select(&src, "SELECT * FROM segment_rules WHERE segment_id = ?", s.ID)
		if err != nil {
			return errors.Wrap(err, fmt.Sprintf("unable to get related segment rules for segment [%d]", s.ID))
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

// EventRules returns map of rules assigned to given "category/event" key
func (sDB *SegmentDB) EventRules() EventRules {
	er := make(EventRules)
	for _, s := range sDB.Segments {
		for _, sr := range s.Rules {
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
