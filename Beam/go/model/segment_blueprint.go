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
	Type      string   `json:"type"`
	Required  bool     `json:"required"`
	Default   *string  `json:"default"`
	Help      string   `json:"help"`
	Label     string   `json:"label"`
	Group     *string  `json:"group"`
	Available []string `json:"available"`
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
			Params: sbdb.buildParams(c),
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

// buildParams returns map of Params based on provided category.
func (sbdb *SegmentBlueprintDB) buildParams(category string) map[string]SegmentBlueprintTableCriterionParam {
	params := sbdb.commonParams()

	switch category {
	case CategoryPageview:
		params["is_article"] = SegmentBlueprintTableCriterionParam{
			Type:     "boolean",
			Required: false,
			Help:     "Should segment match only article pageviews (true), not-article pageviews (false) or all (parameter not provided)",
			Label:    "Match article pageviews",
		}
	}
	return params
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
		Type:     "datetime",
		Required: true,
		Help:     "Datetime / interval against which should be segment run",
		Label:    "Timespan",
	}

	commonParams["match_campaign"] = SegmentBlueprintTableCriterionParam{
		Type:     "boolean",
		Required: false,
		Help:     "Use current campaign (for which is segment running) as filter",
		Label:    "Match running campaign",
	}

	commonParams["fields"] = SegmentBlueprintTableCriterionParam{
		Type:     "string_array",
		Required: false,
		Help:     "Add fields (key: value) to segment. Eg. `utm_campaign: election_2019`",
		Label:    "Fields",
	}

	return commonParams
}
