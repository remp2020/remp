package model

import (
	"testing"

	"github.com/elastic/go-elasticsearch/v8/typedapi/core/search"
)


func TestDebugEventQuery(t *testing.T) {
	newDB, cleanup := setupTestClient(t)
	defer cleanup()

	options := AggregateOptions{
		TimeAfter:  testHourStart,
		TimeBefore: testHourEnd,
		Category:   "banner",
		Action:     "show",
	}

	// Build query using TypedAPI
	query, err := newDB.BuildBoolQuery("events", options)
	if err != nil {
		t.Skipf("Failed to build query: %v", err)
	}
	t.Logf("Query built: %+v", query)

	// Execute search_after using TypedAPI
	res, err := newDB.SearchAfterTyped("events", query, 10, nil, nil)
	if err != nil {
		t.Skipf("Failed to execute search_after: %v", err)
	}

	if res.Hits.Total != nil {
		t.Logf("Total hits: %d", res.Hits.Total.Value)
	}
	t.Logf("Number of hits: %d", len(res.Hits.Hits))
	if len(res.Hits.Hits) > 0 {
		t.Logf("First hit ID: %v", res.Hits.Hits[0].Id_)
	}
}

func TestDebugFieldMapping(t *testing.T) {
	newDB, cleanup := setupTestClient(t)
	defer cleanup()

	fields, err := newDB.cacheFieldMappingTyped("events")
	if err != nil {
		t.Skipf("Failed to get field mapping: %v", err)
	}

	t.Logf("Found %d fields", len(fields))

	// Check specific fields
	checkFields := []string{"category", "category.keyword", "action", "action.keyword", "time", "remp_pageview_id", "remp_pageview_id.keyword"}
	for _, field := range checkFields {
		if fieldType, ok := fields[field]; ok {
			t.Logf("Field %s: %s", field, fieldType)
		} else {
			t.Logf("Field %s: NOT FOUND", field)
		}
	}

	// Test resolveKeyword
	categoryField, err := newDB.resolveKeyword("events", "category")
	if err != nil {
		t.Skipf("Failed to resolve category: %v", err)
	}
	t.Logf("resolveKeyword('category') = '%s'", categoryField)

	actionField, err := newDB.resolveKeyword("events", "action")
	if err != nil {
		t.Skipf("Failed to resolve action: %v", err)
	}
	t.Logf("resolveKeyword('action') = '%s'", actionField)
}

func TestDebugCountVsList(t *testing.T) {
	newDB, cleanup := setupTestClient(t)
	defer cleanup()

	newEvent := &EventElasticsearch{DB: newDB}

	options := AggregateOptions{
		TimeAfter:  testHourStart,
		TimeBefore: testHourEnd,
		Category:   "banner",
		Action:     "show",
	}

	// Test Count
	t.Log("Testing Count()...")
	countResult, ok, err := newEvent.Count(options)
	if err != nil {
		t.Skipf("Count failed: %v", err)
	}
	t.Logf("Count OK=%v, results=%d", ok, len(countResult))
	if len(countResult) > 0 {
		t.Logf("Count value: %d", countResult[0].Count)
	}

	// Test List
	t.Log("Testing List()...")
	listResult, err := newEvent.List(ListOptions{AggregateOptions: options})
	if err != nil {
		t.Skipf("List failed: %v", err)
	}
	t.Logf("List results: %d", len(listResult))
	if len(listResult) > 0 {
		t.Logf("First result has %d events", len(listResult[0].Events))
	}
}

func TestDebugSearchTyped(t *testing.T) {
	newDB, cleanup := setupTestClient(t)
	defer cleanup()

	options := AggregateOptions{
		TimeAfter:  testHourStart,
		TimeBefore: testHourEnd,
	}

	query, err := newDB.BuildBoolQuery("events", options)
	if err != nil {
		t.Skipf("Failed to build query: %v", err)
	}

	size := 0
	req := &search.Request{
		Query: query,
		Size:  &size,
	}
	req.TrackTotalHits = true

	res, err := newDB.SearchTyped("events", req)
	if err != nil {
		t.Skipf("SearchTyped failed: %v", err)
	}

	if res.Hits.Total != nil {
		t.Logf("Total hits: %d", res.Hits.Total.Value)
	}
	t.Logf("Aggregations: %d", len(res.Aggregations))
}
