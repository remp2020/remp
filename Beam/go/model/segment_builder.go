package model

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"math"
	"time"

	"github.com/pkg/errors"
)

// SegmentCriteria represents segment's criteria.
type SegmentCriteria struct {
	Version string
	Nodes   SegmentCriteriaOperatorNodeCollection
}

// SegmentCriteriaOperatorNode represents one operator node of criteria.
// TODO: merge SegmentCriteriaOperatorNode and SegmentCriteriaNode into one type?
type SegmentCriteriaOperatorNode struct {
	Type     string
	Operator string
	Nodes    SegmentCriteriaNodeCollection
}

// SegmentCriteriaOperatorNodeCollection represents collection of SegmentCriteriaOperatorNode.
type SegmentCriteriaOperatorNodeCollection []SegmentCriteriaOperatorNode

// SegmentCriteriaNode represents one node of segment's criteria.
type SegmentCriteriaNode struct {
	Type     string
	Key      string
	Negation bool
	Values   map[string]interface{}
}

// SegmentCriteriaNodeCollection represents collection of SegmentCriteriaNode.
type SegmentCriteriaNodeCollection []SegmentCriteriaNode

// SegmentCriteriaValuesDatetime represents datetime type of criteria values.
type SegmentCriteriaValuesDatetime struct {
	Type     string
	Absolute *SegmentCriteriaValuesDatetimeAbsolute
	Interval *SegmentCriteriaValuesDatetimeInterval
}

// SegmentCriteriaValuesDatetimeAbsolute represents absolute datetime type of criteria values.
type SegmentCriteriaValuesDatetimeAbsolute map[string]string

// SegmentCriteriaValuesDatetimeInterval represents interval datetime type of criteria values.
type SegmentCriteriaValuesDatetimeInterval map[string]struct {
	Value int
	Unit  string
}

// Scan scans datetime from values to SegmentCriteriaValuesDatetime type.
func (scvd *SegmentCriteriaValuesDatetime) Scan(datetime interface{}) error {
	dtJSON, err := json.Marshal(datetime)
	if err != nil {
		return err
	}

	err = json.Unmarshal(dtJSON, scvd)
	if err != nil {
		return err
	}

	return nil
}

// BuildRules builds segment rules from segment criteria.
func (sDB *SegmentDB) BuildRules(s *Segment) ([]SegmentRule, bool, error) {
	rules := []SegmentRule{}

	if !s.Criteria.Valid {
		return rules, false, nil
	}

	sc := &SegmentCriteria{}
	err := json.Unmarshal([]byte(s.Criteria.String), sc)
	if err != nil {
		return rules, false, err
	}

	// no nodes of criteria means no rules
	if len(sc.Nodes) == 0 {
		return rules, true, nil
	}

	for _, n := range sc.Nodes {
		// TODO: move to validation when creating/updating segment criteria
		if n.Type != "operator" {
			return rules, false, errors.New("incorrect type of node - only `operator` is allowed on this level")
		}
		// TODO: move to validation when creating/updating segment criteria
		if n.Operator != "AND" {
			return rules, false, errors.New("incorrect operator - only `AND` operator is allowed")
		}

		for _, nn := range n.Nodes {
			// TODO: move to validation when creating/updating segment criteria
			if nn.Type != "criteria" {
				return rules, false, errors.New("incorrect type of node - only `criteria` is allowed on this level")
			}

			var sr SegmentRule

			sr.EventCategory = nn.Key
			sr.SegmentID = s.ID
			sr.CreatedAt = s.CreatedAt
			sr.UpdatedAt = s.UpdatedAt

			sr.Fields = make(JSONMap, 0)
			sr.Flags = make(JSONMap, 0)

			for k, v := range nn.Values {
				switch k {
				case "action":
					if action, ok := v.(string); ok {
						sr.EventAction = action
					}
				case "count":
					mf := v.(map[string]interface{})
					for fk, fv := range mf {
						var operator string
						switch fk {
						case "eq":
							operator = "="
						case "gt":
							operator = ">"
						case "gte":
							operator = ">="
						case "lte":
							operator = "<="
						case "lt":
							operator = "<"
						default:
							return nil, false, errors.New("unhandled operator")
						}

						var count int
						if c, ok := fv.(float64); ok {
							count = int(c)
						}

						if sr.Operator == "" && sr.Count == 0 {
							sr.Operator = operator
							sr.Count = count

						} else if sr.Operator2 == nil && sr.Count2 == nil {
							sr.Operator2 = &operator
							sr.Count2 = &count
						}
					}
				case "fields":
					mf := v.(map[string]interface{})
					var fields JSONMap
					for fk, fv := range mf {
						fields = append(fields, map[string]string{
							"key":   fk,
							"value": fmt.Sprintf("%v", fv),
						})
					}
					sr.Fields = fields
				case "is_article":
					if ia, ok := v.(bool); ok {
						var value string
						if ia {
							value = "1"
						} else {
							value = "0"
						}
						sr.Flags = append(sr.Flags, map[string]string{
							"key":   "_article",
							"value": value,
						})
					}
				case "match_campaign":
					if mc, ok := v.(bool); ok {
						var value string
						if mc {
							value = ""
						} else {
							break
						}
						sr.Flags = append(sr.Flags, map[string]string{
							"key":   "utm_campaign",
							"value": value,
						})
					}
				case "timespan":
					var scvd SegmentCriteriaValuesDatetime
					err := scvd.Scan(v)
					if err != nil {
						return nil, false, errors.Wrap(err, "unable to scan datetime from segment criteria values")
					}
					switch scvd.Type {
					case "absolute":
						if scvd.Absolute == nil {
							return nil, false, errors.New("absolute timespan missing values")
						}
						if val, ok := (*scvd.Absolute)["gte"]; ok {
							layout := "2006-01-02T15:04:05.000Z"
							t, err := time.Parse(layout, val)
							if err != nil {
								return nil, false, errors.New(fmt.Sprintf("unable to parse timespan [%s]", val))
							}
							duration := time.Since(t)
							sr.Timespan = sql.NullInt64{
								Int64: int64(duration.Minutes()),
								Valid: true,
							}
						}
					case "interval":
						if scvd.Interval == nil {
							return nil, false, errors.New("interval timespan missing values")
						}
						if val, ok := (*scvd.Interval)["gte"]; ok {
							var timespan int
							var multiplier int
							switch val.Unit {
							case "hour":
								multiplier = 60
							case "day":
								multiplier = 1440
							case "month":
								multiplier = 43800
							}
							timespan = int(math.Abs(float64(val.Value))) * multiplier
							sr.Timespan = sql.NullInt64{
								Int64: int64(timespan),
								Valid: true,
							}
						}
					}
				}
			}
			rules = append(rules, sr)
		}
	}

	return rules, true, nil
}
