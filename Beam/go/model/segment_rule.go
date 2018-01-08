package model

import (
	"database/sql"
	"database/sql/driver"
	"encoding/json"
	"fmt"
	"hash/fnv"
	"sort"
	"strconv"
	"time"

	"github.com/patrickmn/go-cache"
	"github.com/pkg/errors"
)

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

	cacheKey int
}

// RuleOverrides represent key-value string pairs for overriding stored tags in segment rules.
type RuleOverrides struct {
	Fields map[string]string
}

// JSONMap represents key-value string pairs stored as string JSON [{"key": "foo", "value": "bar"}].
type JSONMap []map[string]string

// SegmentRuleCache represents event count information for single SegmentRule.
type SegmentRuleCache struct {
	SyncedAt time.Time `json:"s"`
	Count    int       `json:"c"`
}

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

// applyOverrides overrides field values based on provided RuleOverrides.
func (sr SegmentRule) applyOverrides(o RuleOverrides) *SegmentRule {
	overridable := make(map[string]bool)
	for _, f := range sr.overridableFields() {
		overridable[f] = true
	}

	// we can't edit sr.Fields directly, it's being passed as pointer and we would be editing it for everybody
	newFields := make(JSONMap, len(sr.Fields))
	for _, def := range sr.Fields {
		k := def["key"]
		v := def["value"]
		if _, ok := overridable[def["key"]]; ok {
			v = o.Fields[def["key"]]
		}
		newFields = append(newFields, map[string]string{
			"key":   k,
			"value": v,
		})
	}
	sr.Fields = newFields
	return &sr
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

	if sr.Timespan.Valid {
		t := now.Add(time.Minute * time.Duration(int(sr.Timespan.Int64)*-1))
		conds = append(conds, fmt.Sprintf(`"time" >= '%s'`, t.Format(time.RFC3339Nano)))
	}
	return conds
}

func (sr *SegmentRule) groups() []string {
	var conds []string
	for _, def := range sr.Flags {
		if def["value"] == "" {
			continue
		}
		conds = append(conds, def["key"])
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

// overridableFields returns list of fields that can be overriden via RuleOverrides.
func (sr *SegmentRule) overridableFields() []string {
	fields := []string{}
	for _, def := range sr.Fields {
		if def["value"] != "" {
			continue
		}
		fields = append(fields, def["key"])
	}
	sort.Strings(fields)
	return fields
}

// flags returns map of used flags with values (e.g. "_article": "1")
func (sr *SegmentRule) flags() map[string]string {
	flags := make(map[string]string)
	for _, def := range sr.Flags {
		if def["value"] == "" {
			continue
		}
		flags[def["key"]] = def["value"]
	}
	return flags
}

// getCacheKey generates unique int-based key based on SegmentRule definition.
func (sr *SegmentRule) getCacheKey(ro RuleOverrides) int {
	if sr.cacheKey != 0 {
		return sr.cacheKey
	}
	k := strconv.Itoa(sr.ID)

	// use overridable fields
	for _, f := range sr.overridableFields() {
		k = fmt.Sprintf("%s_%s", k, ro.Fields[f])
	}

	// generate and store hash
	h := fnv.New32a()
	h.Write([]byte(k))
	sr.cacheKey = int(h.Sum32())

	return sr.cacheKey
}
