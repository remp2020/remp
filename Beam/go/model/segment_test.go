package model

import (
	"testing"
	"time"

	cache "github.com/patrickmn/go-cache"
)

func TestSegmentRule_CacheDuration(t *testing.T) {
	var durationTests = []struct {
		TestedCount int
		RuleCount   int
		Operator    string
		Duration    time.Duration
		ShouldCache bool
	}{
		{4, 5, "<", 0, false},
		{5, 5, "<", 0, false},
		{6, 5, "<", cache.DefaultExpiration, true},

		{4, 5, "<=", 0, false},
		{5, 5, "<=", cache.DefaultExpiration, true},
		{6, 5, "<=", cache.DefaultExpiration, true},

		{4, 5, "=", 0, false},
		{5, 5, "=", 2 * time.Minute, true},
		{6, 5, "=", 2 * time.Minute, true},

		{4, 5, ">=", 0, false},
		{5, 5, ">=", cache.DefaultExpiration, true},
		{6, 5, ">=", cache.DefaultExpiration, true},

		{4, 5, ">", 0, false},
		{5, 5, ">", 0, false},
		{6, 5, ">", cache.DefaultExpiration, true},
	}

	for _, dt := range durationTests {
		sr := &SegmentRule{
			Count:    dt.RuleCount,
			Operator: dt.Operator,
		}
		d, ok := sr.CacheDuration(dt.TestedCount)
		if ok != dt.ShouldCache {
			t.Errorf("returned cache flag %t, expected %t: %v", ok, dt.ShouldCache, dt)
		}
		if d != dt.Duration {
			t.Errorf("returned duration %d, expected %d: %v", d, dt.Duration, dt)
		}
	}
}

func TestSegmentRule_Evaluate(t *testing.T) {
	var evalTests = []struct {
		TestedCount int
		RuleCount   int
		Operator    string
		Result      bool
		HasError    bool
	}{
		{4, 5, "<", true, false},
		{5, 5, "<", false, false},
		{6, 5, "<", false, false},

		{4, 5, "<=", true, false},
		{5, 5, "<=", true, false},
		{6, 5, "<=", false, false},

		{4, 5, "=", false, false},
		{5, 5, "=", true, false},
		{6, 5, "=", false, false},

		{4, 5, ">=", false, false},
		{5, 5, ">=", true, false},
		{6, 5, ">=", true, false},

		{4, 5, ">", false, false},
		{5, 5, ">", false, false},
		{6, 5, ">", true, false},

		{6, 5, "?", false, true},
	}

	for _, et := range evalTests {
		sr := &SegmentRule{
			Count:    et.RuleCount,
			Operator: et.Operator,
		}
		ok, err := sr.Evaluate(et.TestedCount)
		if err != nil && !et.HasError {
			t.Errorf("returned error %s even when none was expected: %v", err, et)
		}
		if ok != et.Result {
			t.Errorf("returned result %t, expected %t: %v", ok, et.Result, et)
		}
	}
}
