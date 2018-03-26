package influxquery

import (
	"time"

	"github.com/influxdata/influxdb/models"
	"github.com/pkg/errors"
)

// Result represents structure of InfluxDB query result.
type Result struct {
	rowIdx int
	series models.Row
	colMap map[string]int
}

// NewInfluxResult creates Result structure based on provided Row series and index to extract.
func NewInfluxResult(series models.Row, rowIdx int) *Result {
	colmap := make(map[string]int)
	for i, col := range series.Columns {
		colmap[col] = i
	}
	return &Result{
		rowIdx: rowIdx,
		series: series,
		colMap: colmap,
	}
}

// StringValue parses string out of Result's column.
func (ir *Result) StringValue(col string) (string, bool) {
	colIdx, ok := ir.colMap[col]
	if !ok {
		return "", false
	}
	val, ok := ir.series.Values[ir.rowIdx][colIdx].(string)
	if !ok {
		return "", false
	}
	return val, true
}

// TimeValue parses string out of Result's column.
func (ir *Result) TimeValue(col string) (time.Time, bool, error) {
	colIdx, ok := ir.colMap[col]
	if !ok {
		return time.Time{}, false, nil
	}
	val := ir.series.Values[ir.rowIdx][colIdx].(string)
	t, err := time.Parse(time.RFC3339, val)
	if err != nil {
		return time.Time{}, false, errors.Wrap(err, "unable to parse influx time")
	}
	return t, true, nil
}

// BoolValue parses bool out of Result's column.
func (ir *Result) BoolValue(col string) (bool, bool) {
	colIdx, ok := ir.colMap[col]
	if !ok {
		return false, false
	}
	val, ok := ir.series.Values[ir.rowIdx][colIdx].(bool)
	if !ok {
		return false, false
	}
	return val, true
}
