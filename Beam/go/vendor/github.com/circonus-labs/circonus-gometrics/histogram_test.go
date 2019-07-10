// Copyright 2016 Circonus, Inc. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

package circonusgometrics

import (
	"reflect"
	"testing"
)

func TestTiming(t *testing.T) {
	t.Log("Testing histogram.Timing")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	cm.Timing("foo", 1)

	hist, ok := cm.histograms["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if hist == nil {
		t.Errorf("Expected *Histogram, found %v", hist)
	}

	val := hist.hist.DecStrings()
	if len(val) != 1 {
		t.Errorf("Expected 1, found '%v'", val)
	}

	expectedVal := "H[1.0e+00]=1"
	if val[0] != expectedVal {
		t.Errorf("Expected '%s', found '%s'", expectedVal, val[0])
	}
}

func TestRecordValue(t *testing.T) {
	t.Log("Testing histogram.RecordValue")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	cm.RecordValue("foo", 1)

	hist, ok := cm.histograms["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if hist == nil {
		t.Errorf("Expected *Histogram, found %v", hist)
	}

	val := hist.hist.DecStrings()
	if len(val) != 1 {
		t.Errorf("Expected 1, found '%v'", val)
	}

	expectedVal := "H[1.0e+00]=1"
	if val[0] != expectedVal {
		t.Errorf("Expected '%s', found '%s'", expectedVal, val[0])
	}
}

func TestRecordCountForValue(t *testing.T) {
	t.Log("Testing histogram.RecordCountForValue")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	cm.RecordCountForValue("foo", 1.2, 5)

	hist, ok := cm.histograms["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if hist == nil {
		t.Errorf("Expected *Histogram, found %v", hist)
	}

	val := hist.hist.DecStrings()
	if len(val) != 1 {
		t.Errorf("Expected 1, found '%v'", val)
	}

	expectedVal := "H[1.2e+00]=5"
	if val[0] != expectedVal {
		t.Errorf("Expected '%s', found '%s'", expectedVal, val[0])
	}
}

func TestSetHistogramValue(t *testing.T) {
	t.Log("Testing histogram.SetHistogramValue")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	cm.SetHistogramValue("foo", 1)

	hist, ok := cm.histograms["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if hist == nil {
		t.Errorf("Expected *Histogram, found %v", hist)
	}

	val := hist.hist.DecStrings()
	if len(val) != 1 {
		t.Errorf("Expected 1, found '%v'", val)
	}

	expectedVal := "H[1.0e+00]=1"
	if val[0] != expectedVal {
		t.Errorf("Expected '%s', found '%s'", expectedVal, val[0])
	}
}

func TestGetHistogramTest(t *testing.T) {
	t.Log("Testing histogram.GetHistogramTest")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	cm.SetHistogramValue("foo", 10)
	expected := "H[1.0e+01]=1"

	val, err := cm.GetHistogramTest("foo")
	if err != nil {
		t.Errorf("Expected no error %v", err)
	}
	if len(val) == 0 {
		t.Error("Expected 1 value, got 0 values")
	}
	if val[0] != expected {
		t.Errorf("Expected '%s' got '%v'", expected, val[0])
	}

	_, err = cm.GetHistogramTest("bar")
	if err == nil {
		t.Error("Expected error")
	}

}

func TestRemoveHistogram(t *testing.T) {
	t.Log("Testing histogram.RemoveHistogram")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	cm.SetHistogramValue("foo", 1)

	hist, ok := cm.histograms["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if hist == nil {
		t.Errorf("Expected *Histogram, found %v", hist)
	}

	val := hist.hist.DecStrings()
	if len(val) != 1 {
		t.Errorf("Expected 1, found '%v'", val)
	}

	expectedVal := "H[1.0e+00]=1"
	if val[0] != expectedVal {
		t.Errorf("Expected '%s', found '%s'", expectedVal, val[0])
	}

	cm.RemoveHistogram("foo")

	hist, ok = cm.histograms["foo"]
	if ok {
		t.Errorf("Expected NOT to find foo")
	}

	if hist != nil {
		t.Errorf("Expected nil, found %v", hist)
	}
}

func TestNewHistogram(t *testing.T) {
	t.Log("Testing histogram.NewHistogram")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	hist := cm.NewHistogram("foo")

	actualType := reflect.TypeOf(hist)
	expectedType := "*circonusgometrics.Histogram"
	if actualType.String() != expectedType {
		t.Errorf("Expected %s, got %s", expectedType, actualType.String())
	}
}

func TestHistName(t *testing.T) {
	t.Log("Testing hist.Name")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	hist := cm.NewHistogram("foo")

	actualType := reflect.TypeOf(hist)
	expectedType := "*circonusgometrics.Histogram"
	if actualType.String() != expectedType {
		t.Errorf("Expected %s, got %s", expectedType, actualType.String())
	}

	expectedName := "foo"
	actualName := hist.Name()
	if actualName != expectedName {
		t.Errorf("Expected '%s', found '%s'", expectedName, actualName)
	}
}

func TestHistRecordValue(t *testing.T) {
	t.Log("Testing hist.RecordValue")

	cm := &CirconusMetrics{histograms: make(map[string]*Histogram)}

	hist := cm.NewHistogram("foo")

	actualType := reflect.TypeOf(hist)
	expectedType := "*circonusgometrics.Histogram"
	if actualType.String() != expectedType {
		t.Errorf("Expected %s, got %s", expectedType, actualType.String())
	}

	hist.RecordValue(1)

	val := hist.hist.DecStrings()
	if len(val) != 1 {
		t.Errorf("Expected 1, found '%v'", val)
	}

	expectedVal := "H[1.0e+00]=1"
	if val[0] != expectedVal {
		t.Errorf("Expected '%s', found '%s'", expectedVal, val[0])
	}

	hist = cm.NewHistogram("foo")
	if hist == nil {
		t.Fatalf("Expected non-nil")
	}
}
