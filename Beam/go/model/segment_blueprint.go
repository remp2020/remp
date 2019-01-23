package model

import (
	"fmt"
	"strings"
)

// SegmentBlueprintStorage represents interface for segment blueprint related interactions.
type SegmentBlueprintStorage interface {
	// Get returns SegmentBlueprintTableCollection.
	Get() (SegmentBlueprintTableCollection, error)
}

// SegmentBlueprintDB represents SegmentBlueprintStorage implementation.
type SegmentBlueprintDB struct {
	EventStorage    EventStorage
	PageviewStorage PageviewStorage
	CommerceStorage CommerceStorage
}

// SegmentBlueprintTable represents blueprint for one segment field (table).
type SegmentBlueprintTable struct {
	Table    string
	Fields   []string
	Criteria SegmentBlueprintTableCriterionCollection
}

// SegmentBlueprintTableCollection is list of SegmentBlueprintTable.
type SegmentBlueprintTableCollection []*SegmentBlueprintTable

// SegmentBlueprintTableCriterion represents one criterion of segment blueprint.
type SegmentBlueprintTableCriterion struct {
	Key    string
	Label  string
	Params map[string]SegmentBlueprintTableCriterionParam
	Fields []string
}

// SegmentBlueprintTableCriterionCollection is list of SegmentBlueprintTableCriterion.
type SegmentBlueprintTableCriterionCollection []*SegmentBlueprintTableCriterion

// SegmentBlueprintTableCriterionParam represents one parameter of Segment Criterion.
type SegmentBlueprintTableCriterionParam struct {
	Type      string
	Required  bool
	Default   *string
	Help      string
	Label     string
	Group     *string
	Available []string
}

// PCEStorage is interface which ensures provided storage (Pageview/Commerce/Event)
// can return categories and actions of given category.
type PCEStorage interface {
	// Categories lists all tracked categories.
	Categories() ([]string, error)
	// Actions lists all tracked actions under the given category.
	Actions(category string) ([]string, error)
}

// Get returns all criteria / blueprint for creating new or editing segment.
func (sbdb *SegmentBlueprintDB) Get() (SegmentBlueprintTableCollection, error) {
	return sbdb.generateBlueprintData()
}

// generateBlueprintData generates segment blueprint.
func (sbdb *SegmentBlueprintDB) generateBlueprintData() (SegmentBlueprintTableCollection, error) {
	var blueprint SegmentBlueprintTableCollection
	sbt := &SegmentBlueprintTable{
		Table:    "users",
		Criteria: SegmentBlueprintTableCriterionCollection{},
	}
	blueprint = append(blueprint, sbt)

	// TODO: add flag _article for load action
	// append pageviews
	err := sbdb.fillCriterionFromStorage(sbdb.PageviewStorage, sbt)
	if err != nil {
		return nil, err
	}

	// append commerce
	err = sbdb.fillCriterionFromStorage(sbdb.CommerceStorage, sbt)
	if err != nil {
		return nil, err
	}

	// append events
	err = sbdb.fillCriterionFromStorage(sbdb.EventStorage, sbt)
	if err != nil {
		return nil, err
	}

	return blueprint, nil
}

// fillCriterionFromStorage creates & fills SegmentBlueprintTableCriterion from categories and actions of Pageview/Commerce storage.
func (sbdb *SegmentBlueprintDB) fillCriterionFromStorage(storage PCEStorage, sbt *SegmentBlueprintTable) error {
	categories, err := storage.Categories()
	if err != nil {
		return err
	}
	sbt.Fields = append(sbt.Fields, categories...)

	for _, c := range categories {

		sbtc := &SegmentBlueprintTableCriterion{
			Key:    c,
			Label:  strings.Title(c),
			Params: sbdb.commonParams(),
		}

		actions, err := storage.Actions(c)
		if err != nil {
			return err
		}

		sbtc.Params["action"] = SegmentBlueprintTableCriterionParam{
			Type:      "string",
			Required:  true,
			Default:   &actions[0],
			Help:      fmt.Sprintf("Select user's %s action", c),
			Label:     "Action",
			Available: actions,
		}
		sbt.Criteria = append(sbt.Criteria, sbtc)
	}

	return nil
}

// commonParams returns map of Params which are common for all tables available for segment.
func (sbdb *SegmentBlueprintDB) commonParams() map[string]SegmentBlueprintTableCriterionParam {
	commonParams := make(map[string]SegmentBlueprintTableCriterionParam)

	commonParams["count"] = SegmentBlueprintTableCriterionParam{
		Type:     "number",
		Required: true,
		Help:     "Value of count against which is action of segment rule checked. E.g. 5",
		Label:    "Count",
	}

	commonParams["timespan"] = SegmentBlueprintTableCriterionParam{
		Type:     "interval",
		Required: true,
		Help:     "Interval against which should be segment run",
		Label:    "Timespan",
	}

	commonParams["match_campaign"] = SegmentBlueprintTableCriterionParam{
		Type:     "boolean",
		Required: false,
		Help:     "Use current campaign (for which is segment running) as filter",
		Label:    "Match running campaign",
	}

	return commonParams
}
