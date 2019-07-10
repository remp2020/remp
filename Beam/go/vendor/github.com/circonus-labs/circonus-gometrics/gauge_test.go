// Copyright 2016 Circonus, Inc. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

package circonusgometrics

import (
	"testing"
)

func TestGauge(t *testing.T) {
	t.Log("Testing gauge.Gauge")

	t.Log("int")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int) != v {
			t.Errorf("Expected %d, found %v", v, val)
		}
	}

	t.Log("int8")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int8(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int8) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("int16")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int16(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int16) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("int32")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int32(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int32) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("int64")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int64(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int64) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint8")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint8(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint8) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint16")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint16(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint16) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint32")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint32(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint32) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint64")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint64(1)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint64) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("float32")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := float32(3.12)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(float32) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("float64")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := float64(3.12)
		cm.Gauge("foo", v)
		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(float64) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}
}

func TestAddGauge(t *testing.T) {
	t.Log("Testing gauge.AddGauge")

	t.Log("int")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Fatalf("Expected to find foo")
		}

		if val.(int) != v {
			t.Fatalf("Expected %v, found %v", v, val)
		}
	}

	t.Log("int8")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int8(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int8) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("int16")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int16(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int16) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("int32")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int32(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int32) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("int64")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := int64(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(int64) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint8")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint8(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint8) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint16")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint16(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint16) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint32")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint32(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint32) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("uint64")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := uint64(1)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v++

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(uint64) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("float32")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := float32(3.12)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v = v + v

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(float32) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}

	t.Log("float64")
	{
		cm := &CirconusMetrics{gauges: make(map[string]interface{})}

		v := float64(3.12)
		cm.Gauge("foo", v)
		cm.AddGauge("foo", v)
		v = v + v

		val, ok := cm.gauges["foo"]
		if !ok {
			t.Errorf("Expected to find foo")
		}

		if val.(float64) != v {
			t.Errorf("Expected %v, found %v", v, val)
		}
	}
}

func TestSetGauge(t *testing.T) {
	t.Log("Testing gauge.SetGauge")

	cm := &CirconusMetrics{gauges: make(map[string]interface{})}

	v := int(10)
	cm.SetGauge("foo", v)

	val, ok := cm.gauges["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if val.(int) != v {
		t.Errorf("Expected %d, found %v", v, val)
	}
}

func TestGetGaugeTest(t *testing.T) {
	t.Log("Testing gauge.GetGaugeTest")

	cm := &CirconusMetrics{gauges: make(map[string]interface{})}

	v := int(10)
	cm.SetGauge("foo", v)

	val, err := cm.GetGaugeTest("foo")
	if err != nil {
		t.Errorf("Expected no error %v", err)
	}
	if val.(int) != v {
		t.Errorf("Expected '%d' got '%v'", v, val)
	}

	_, err = cm.GetGaugeTest("bar")
	if err == nil {
		t.Error("Expected error")
	}

}

func TestRemoveGauge(t *testing.T) {
	t.Log("Testing gauge.RemoveGauge")

	cm := &CirconusMetrics{gauges: make(map[string]interface{})}

	v := int(5)
	cm.Gauge("foo", v)
	val, ok := cm.gauges["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if val.(int) != v {
		t.Errorf("Expected %d, found %v", v, val)
	}

	cm.RemoveGauge("foo")

	val, ok = cm.gauges["foo"]
	if ok {
		t.Errorf("Expected NOT to find foo")
	}

	if val != nil {
		t.Errorf("Expected nil, found '%v'", val)
	}
}

func TestSetGaugeFunc(t *testing.T) {
	t.Log("Testing gauge.SetGaugeFunc")

	gf := func() int64 {
		return 1
	}

	cm := &CirconusMetrics{gaugeFuncs: make(map[string]func() int64)}

	cm.SetGaugeFunc("foo", gf)

	val, ok := cm.gaugeFuncs["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if val() != 1 {
		t.Errorf("Expected 1, found %d", val())
	}
}

func TestRemoveGaugeFunc(t *testing.T) {
	t.Log("Testing gauge.RemoveGaugeFunc")

	gf := func() int64 {
		return 1
	}

	cm := &CirconusMetrics{gaugeFuncs: make(map[string]func() int64)}

	cm.SetGaugeFunc("foo", gf)

	val, ok := cm.gaugeFuncs["foo"]
	if !ok {
		t.Errorf("Expected to find foo")
	}

	if val() != 1 {
		t.Errorf("Expected 1, found %d", val())
	}

	cm.RemoveGaugeFunc("foo")

	val, ok = cm.gaugeFuncs["foo"]
	if ok {
		t.Errorf("Expected NOT to find foo")
	}

	if val != nil {
		t.Errorf("Expected nil, found %v", val())
	}

}
