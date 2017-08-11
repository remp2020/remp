package influxquery

import (
	"time"

	"github.com/influxdata/influxdb/models"
	"github.com/pkg/errors"
)

type Result struct {
	rowIdx int
	series models.Row
	colMap map[string]int
}

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

func (ir *Result) StringValue(col string) (string, bool) {
	colIdx, ok := ir.colMap[col]
	if !ok {
		return "", false
	}
	val := ir.series.Values[ir.rowIdx][colIdx].(string)
	return val, true
}

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
