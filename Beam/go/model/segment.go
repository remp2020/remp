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
	Check(segment *Segment, userID string, now time.Time) (bool, error)
	// Users return list of all users within segment.
	Users(code string) (UserCollection, error)
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

// SegmentCollection is list of Segments.
type SegmentCollection []*Segment

// SegmentRule represent single rule of a Segment
type SegmentRule struct {
	ID            int
	ParentID      sql.NullInt64 `db:"parent_id"`
	SegmentID     int           `db:"segment_id"`
	EventCategory string        `db:"event_category"`
	EventName     string        `db:"event_name"`
	Timespan      sql.NullInt64
	Count         int
	CreatedAt     time.Time `db:"created_at"`
	UpdatedAt     time.Time `db:"updated_at"`
	Fields        FieldMap

	Segment *Segment `db:"segment"`
}

type FieldMap []map[string]string

func (fm FieldMap) Value() (driver.Value, error) {
	return json.Marshal(fm)
}

func (fm *FieldMap) Scan(src interface{}) error {
	source, ok := src.([]byte)
	if !ok {
		return errors.New("unable to scan FieldMap: type assertion .([]byte) failed")
	}
	err := json.Unmarshal(source, fm)
	if err != nil {
		return errors.Wrap(err, "unable to unmarshal FieldMap")
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
func (sDB *SegmentDB) Check(segment *Segment, userID string, now time.Time) (bool, error) {
	for _, sr := range segment.Rules {
		ok, err := sDB.checkRule(sr, userID, now)
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
func (sDB *SegmentDB) checkRule(sr *SegmentRule, userID string, now time.Time) (bool, error) {
	subquery := sDB.InfluxDB.QueryBuilder.
		Select("COUNT(user_id)").
		From("events").
		Where(fmt.Sprintf("\"user_id\" = '%s'", userID))
	for _, cond := range sr.conditions(now) {
		subquery.Where(cond)
	}
	sq := subquery.Build()

	// If user didn't generate any event so far, ResponseCount will return always zero.
	// We're aiming for query always returning zero for users eligible for segment rule hit.

	query := sDB.InfluxDB.QueryBuilder.
		Select("*").
		From(fmt.Sprintf("(%s)", sq)).
		Where(fmt.Sprintf("\"count\" >= %d", sr.Count))

	response, err := sDB.InfluxDB.Exec(query.Build())
	if err != nil {
		return false, err
	}
	if err := response.Error(); err != nil {
		return false, err
	}

	count, err := sDB.InfluxDB.ResponseCount(response)
	if err != nil {
		return false, err
	}
	if count == 0 {
		return true, nil
	}
	return false, nil
}

// Users return list of all users within segment.
func (sDB *SegmentDB) Users(code string) (UserCollection, error) {
	sc := UserCollection{}

	return sc, nil
}

func (sr *SegmentRule) conditions(now time.Time) []string {
	conds := []string{
		fmt.Sprintf("\"category\" = '%s'", sr.EventCategory),
		fmt.Sprintf("\"name\" = '%s'", sr.EventName),
	}
	if sr.Timespan.Valid {
		t := now.Add(time.Minute * time.Duration(int(sr.Timespan.Int64)*-1))
		conds = append(conds, fmt.Sprintf("\"time\" >= '%s'", t.Format(time.RFC3339Nano)))
	}
	return conds
}
