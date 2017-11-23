package model

import (
	"database/sql"
	"database/sql/driver"
	"encoding/json"
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
	// Check verifies presence of user within provided segment.
	Check(segment *Segment, userID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error)
	// Users return list of all users within segment.
	Users(segment *Segment, now time.Time, ro RuleOverrides) ([]string, error)
	// EventRules returns map of rules assigned to given "category/event" key
	EventRules() EventRules
}

// EventRules represent map of rules with given "category/event" assigned
type EventRules map[string][]int

// SegmentCache represents event count information for SegmentRules indexed by SegmentRule ID.
type SegmentCache map[int]*SegmentRuleCache

// SegmentRuleCache represents event count information for single SegmentRule.
type SegmentRuleCache struct {
	SyncedAt time.Time `json:"s"`
	Count    int       `json:"c"`
}

// Segment structure.
type Segment struct {
	ID        int
	Code      string
	Name      string
	Active    bool
	CreatedAt time.Time `db:"created_at"`
	UpdatedAt time.Time `db:"updated_at"`

	Group *SegmentGroup
	Rules []*SegmentRule `db:"segment_rules"`
}

// RuleOverrides represent key-value string pairs for overriding stored tags in segment rules.
type RuleOverrides struct {
	Fields map[string]string
}

// SegmentCollection is list of Segments.
type SegmentCollection []*Segment

// SegmentRule represent single rule of a Segment
type SegmentRule struct {
	ID            int
	ParentID      sql.NullInt64 `db:"parent_id"`
	SegmentID     int           `db:"segment_id"`
	EventCategory string        `db:"event_category"`
	EventAction   string        `db:"event_action"`
	Timespan      sql.NullInt64
	Operator      string
	Count         int
	CreatedAt     time.Time `db:"created_at"`
	UpdatedAt     time.Time `db:"updated_at"`
	Fields        JSONMap
	Flags         JSONMap

	Segment *Segment `db:"segment"`
}

// JSONMap represents key-value string pairs stored as string JSON [{"key": "foo", "value": "bar"}].
type JSONMap []map[string]string

// Value returns JSON-encoded value of JSONMap.
func (jm JSONMap) Value() (driver.Value, error) {
	return json.Marshal(jm)
}

// Scan populates JSONMap based on scanned value.
func (jm *JSONMap) Scan(src interface{}) error {
	source, ok := src.([]byte)
	if !ok {
		return errors.New("unable to scan JSONMap: type assertion .([]byte) failed")
	}
	err := json.Unmarshal(source, jm)
	if err != nil {
		return errors.Wrap(err, "unable to unmarshal JSONMap")
	}
	return nil
}

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
		return nil, false, errors.Wrap(err, "unable to get segment from MySQL")
	}
	if s.ID == 0 {
		return nil, false, nil
	}

	src := []*SegmentRule{}
	err = sDB.MySQL.Select(&src, "SELECT * FROM segment_rules WHERE segment_id = ?", s.ID)
	if err != nil {
		return nil, false, errors.Wrap(err, fmt.Sprintf("unable to get related segment rules for segment [%d]", s.ID))
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

// Check verifies presence of user within provided segment.
func (sDB *SegmentDB) Check(segment *Segment, userID string, now time.Time, cache SegmentCache, ro RuleOverrides) (SegmentCache, bool, error) {
	c := make(SegmentCache)

	for _, sr := range segment.Rules {
		osr := sr.applyOverrides(ro)

		// get count
		var count int
		var err error
		if src, ok := cache[sr.ID]; ok {
			count = src.Count
			// update cache
			c[osr.ID] = &SegmentRuleCache{
				Count:    count,
				SyncedAt: cache[sr.ID].SyncedAt,
			}
		} else {
			count, err = sDB.getRuleEventCount(osr, userID, now, ro)
			if err != nil {
				return nil, false, errors.Wrap(err, "unable to get SegmentRule event count")
			}
			// update cache
			c[osr.ID] = &SegmentRuleCache{
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
func (sDB *SegmentDB) getRuleEventCount(sr SegmentRule, userID string, now time.Time, ro RuleOverrides) (int, error) {
	// get count of events directly from influx
	query := sDB.InfluxDB.QueryBuilder.
		Select(`COUNT("token")`).
		From(sr.tableName()).
		Where(fmt.Sprintf(`"user_id" = '%s'`, userID))
	for _, cond := range sr.conditions(now, ro) {
		query = query.Where(cond)
	}

	response, err := sDB.InfluxDB.Exec(query.Build())
	if err != nil {
		return 0, err
	}
	if err := response.Error(); err != nil {
		return 0, err
	}

	count, ok, err := sDB.InfluxDB.Count(response)
	if err != nil {
		return 0, err
	}
	if !ok { // no response from influx mean no data tracked
		count = 0
	}

	return count, nil
}

// CacheKey generates string cache key for SegmentRule.
func (sr *SegmentRule) CacheKey(userID string) string {
	key := fmt.Sprintf(
		"%s,%s/%s,%s%d,%d",
		userID,
		sr.EventCategory,
		sr.EventAction,
		sr.Operator,
		sr.Count,
		sr.Timespan.Int64,
	)
	for _, def := range sr.Fields {
		key = fmt.Sprintf("%s,%s/%s", key, def["key"], def["value"])
	}
	return key
}

// CacheDuration returns duration to cache the item for and whether the item should be cached at all.
func (sr *SegmentRule) CacheDuration(count int) (time.Duration, bool) {
	var d time.Duration
	switch sr.Operator {
	case "<=", ">=":
		if count < sr.Count {
			return 0, false
		}
		d = cache.DefaultExpiration
	case "<", ">":
		if count <= sr.Count {
			return 0, false
		}
		d = cache.DefaultExpiration
	case "=":
		if count < sr.Count {
			return 0, false
		}
		d = 2 * time.Minute
	default:
		return 0, false
	}
	return d, true
}

// Evaluate evaluates segment rule condition against provided count.
func (sr *SegmentRule) Evaluate(count int) (bool, error) {
	switch sr.Operator {
	case "<=":
		return count <= sr.Count, nil
	case "<":
		return count < sr.Count, nil
	case "=":
		return count == sr.Count, nil
	case ">=":
		return count >= sr.Count, nil
	case ">":
		return count > sr.Count, nil
	default:
		return false, fmt.Errorf("unhandled operator: %s", sr.Operator)
	}
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
func (sDB *SegmentDB) ruleUsers(sr *SegmentRule, now time.Time, o RuleOverrides, intersect Intersector) (UserSet, error) {
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

	err := sDB.MySQL.Select(&sc, "SELECT * FROM segments")
	if err != nil {
		if err == sql.ErrNoRows {
			return nil
		}
		return errors.Wrap(err, "unable to cache segments from MySQL")
	}

	for _, s := range sc {
		src := []*SegmentRule{}
		err = sDB.MySQL.Select(&src, "SELECT * FROM segment_rules WHERE segment_id = ?", s.ID)
		if err != nil {
			return errors.Wrap(err, fmt.Sprintf("unable to get related segment rules for segment [%d]", s.ID))
		}
		s.Rules = src
	}

	var changed bool
	for _, s := range sc {
		old, ok := sDB.Segments[s.Code]
		if !changed && (!ok || !reflect.DeepEqual(old, s)) {
			changed = true
		}
		sm[s.Code] = s
	}
	if changed {
		log.Println("segment cache reloaded")
	}

	sDB.Segments = sm
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

// applyOverrides overrides field values based on provided RuleOverrides.
func (sr SegmentRule) applyOverrides(o RuleOverrides) SegmentRule {
	for _, def := range sr.Fields {
		if overriddenVal, ok := o.Fields[def["key"]]; ok {
			def["value"] = overriddenVal
		}
	}
	return sr
}

// conditions returns list of influx conditions for current SegmentRule.
func (sr *SegmentRule) conditions(now time.Time, o RuleOverrides) []string {
	var conds []string
	switch sr.EventCategory {
	case CategoryPageview:
		// no condition needed yet, pageview-load event is implicit
	case CategoryCommerce:
		conds = append(
			conds,
			fmt.Sprintf(`"step" = '%s'`, sr.EventAction),
		)
	default:
		conds = append(
			conds,
			fmt.Sprintf(`"category" = '%s'`, sr.EventCategory),
			fmt.Sprintf(`"action" = '%s'`, sr.EventAction),
		)
	}

	for _, def := range sr.Fields {
		if def["key"] == "" || def["value"] == "" {
			continue
		}
		conds = append(
			conds,
			fmt.Sprintf(`"%s" = '%s'`, def["key"], def["value"]),
		)
	}

	for _, def := range sr.Flags {
		if def["value"] == "" {
			continue
		}
		conds = append(
			conds,
			fmt.Sprintf(`"%s" = '%s'`, def["key"], def["value"]),
		)
	}

	if sr.Timespan.Valid {
		t := now.Add(time.Minute * time.Duration(int(sr.Timespan.Int64)*-1))
		conds = append(conds, fmt.Sprintf(`"time" >= '%s'`, t.Format(time.RFC3339Nano)))
	}
	return conds
}

// tableName returns name of table containing data based on SegmentRule internals.
func (sr *SegmentRule) tableName() string {
	switch sr.EventCategory {
	case CategoryPageview:
		return TablePageviews
	case CategoryCommerce:
		return TableCommerce
	default:
		return TableEvents
	}
}
