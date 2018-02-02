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
	MySQL          *sqlx.DB
	InfluxDB       *InfluxDB
	RuleCountCache *cache.Cache
	Segments       map[string]*Segment
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
		return c, ok, nil
	}

	return c, true, nil
}

// getRuleEventCount returns real db-based number of events occurred based on provided SegmentRule.
func (sDB *SegmentDB) getRuleEventCount(sr *SegmentRule, tagName, tagValue string, now time.Time, ro RuleOverrides) (int, error) {
	// get count of events directly from influx
	query := sDB.InfluxDB.QueryBuilder.
		Select(`COUNT("token")`).
		From(sr.tableName()).
		Where(fmt.Sprintf(`("%s" = '%s')`, tagName, tagValue))
	for _, cond := range sr.conditions(now, ro) {
		query = query.Where(cond)
	}
	for _, cond := range sr.groups() {
		query = query.GroupBy(cond)
	}

	response, err := sDB.InfluxDB.Exec(query.Build())
	if err != nil {
		return 0, err
	}
	if err := response.Error(); err != nil {
		return 0, err
	}

	crc, ok, err := sDB.InfluxDB.MultiGroupedCount(response)
	if err != nil {
		return 0, err
	}
	if !ok {
		return 0, nil
	}

	flags := sr.flags()
	matchGroupedCount := func(cr CountRow) bool {
		for flag, flagVal := range flags {
			tagVal, ok := cr.Tags[flag]
			if !ok {
				return false
			}
			if flagVal != tagVal {
				return false
			}
		}
		return true
	}

	for _, cr := range crc {
		if !matchGroupedCount(cr) {
			continue
		}
		return cr.Count, nil
	}

	return 0, nil
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

	var uc []string
	for userID := range users {
		uc = append(uc, userID)
	}

	return uc, nil
}

// ruleUsers lists all users based on SegmentRule and filters them based on the provided Intersector.
func (sDB *SegmentDB) ruleUsers(sr SegmentRule, now time.Time, o RuleOverrides, intersect Intersector) (UserSet, error) {
	subquery := sDB.InfluxDB.QueryBuilder.
		Select(`COUNT("token")`).
		From(sr.tableName()).
		GroupBy(`"user_id"`)
	for _, cond := range sr.conditions(now, o) {
		subquery = subquery.Where(cond)
	}

	query := sDB.InfluxDB.QueryBuilder.
		Select(`"count", "user_id"`).
		From(fmt.Sprintf("(%s)", subquery.Build())).
		Where(fmt.Sprintf(`"count" < %d`, sr.Count))

	response, err := sDB.InfluxDB.Exec(query.Build())
	if err != nil {
		return nil, err
	}
	if err := response.Error(); err != nil {
		return nil, err
	}

	um := make(UserSet)
	for _, serie := range response.Results[0].Series {
		var index int
		for i, col := range serie.Columns {
			if col == "user_id" {
				index = i
				break
			}
		}
		for _, val := range serie.Values {
			userID, ok := val[index].(string)
			if !ok {
				return nil, errors.New("influx result is not string, cannot proceed")
			}
			if intersect(userID) {
				um[userID] = true
			}
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
