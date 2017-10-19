package model

import (
	"database/sql"
	"database/sql/driver"
	"encoding/json"
	"fmt"
	"time"

	"github.com/jmoiron/sqlx"
	"github.com/pkg/errors"
)

// SegmentStorage represents segment's storage interface.
type SegmentStorage interface {
	// Get returns instance of Segment based on the given code.
	Get(code string) (*Segment, bool, error)
	// List returns all available segments configured via Beam admin.
	List() (SegmentCollection, error)
	// Check verifies presence of user within provided segment.
	Check(segment *Segment, userID string, now time.Time, ro RuleOverrides) (bool, error)
	// Users return list of all users within segment.
	Users(segment *Segment, now time.Time, ro RuleOverrides) ([]string, error)
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
	Fields        JsonMap
	Flags         JsonMap

	Segment *Segment `db:"segment"`
}

type JsonMap []map[string]string

func (fm JsonMap) Value() (driver.Value, error) {
	return json.Marshal(fm)
}

func (fm *JsonMap) Scan(src interface{}) error {
	source, ok := src.([]byte)
	if !ok {
		return errors.New("unable to scan JsonMap: type assertion .([]byte) failed")
	}
	err := json.Unmarshal(source, fm)
	if err != nil {
		return errors.Wrap(err, "unable to unmarshal JsonMap")
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
	MySQL    *sqlx.DB
	InfluxDB *InfluxDB
}

// Get returns instance of Segment based on the given code.
func (sDB *SegmentDB) Get(code string) (*Segment, bool, error) {
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
func (sDB *SegmentDB) Check(segment *Segment, userID string, now time.Time, ro RuleOverrides) (bool, error) {
	for _, sr := range segment.Rules {
		ok, err := sDB.checkRule(sr, userID, now, ro)
		if err != nil {
			return false, errors.Wrap(err, "unable to check SegmentRule")
		}
		if !ok {
			return false, nil
		}
	}
	return true, nil
}

// checkRule verifies defined rule against current state within InfluxDB.
func (sDB *SegmentDB) checkRule(sr *SegmentRule, userID string, now time.Time, ro RuleOverrides) (bool, error) {
	// get count of events
	query := sDB.InfluxDB.QueryBuilder.
		Select(`COUNT("token")`).
		From(sr.tableName()).
		Where(fmt.Sprintf(`"user_id" = '%s'`, userID))
	for _, cond := range sr.conditions(now, ro) {
		query = query.Where(cond)
	}

	response, err := sDB.InfluxDB.Exec(query.Build())
	if err != nil {
		return false, err
	}
	if err := response.Error(); err != nil {
		return false, err
	}

	count, ok, err := sDB.InfluxDB.Count(response)
	if err != nil {
		return false, err
	}
	if !ok { // no response from influx mean no data tracked
		count = 0
	}

	// in-place rule evaluation
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
			// on further iterations user needs to be present in "users" (effectivelly all previous iterations)
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
		value := def["value"]
		if overriddenVal, ok := o.Fields[def["key"]]; ok {
			value = overriddenVal
		}
		if def["key"] == "" || value == "" {
			continue
		}
		conds = append(
			conds,
			fmt.Sprintf(`"%s" = '%s'`, def["key"], value),
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
