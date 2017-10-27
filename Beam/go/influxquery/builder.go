package influxquery

import (
	"fmt"
)

// Builder is fluent interface for building influx queries.
type Builder interface {
	// Select sets SELECT clause of query.
	Select(s string) Builder
	// From sets FROM clause of query. It overrides previous value.
	From(f string) Builder
	// Where appends WHERE condition. Previously added conditions stay untouched.
	Where(cond string) Builder
	// GroupBy appends GROUP BY condition. Previously added conditions stay untouched.
	GroupBy(gb string) Builder
	// Build builds the final influxDB query.
	Build() string
}

// NewInfluxBuilder returns instance of simple influx builder.
func NewInfluxBuilder() Builder {
	return &influxBuilder{
		selectStr: "*",
	}
}

// influxBuilder represents very simplified builder of influxDB queries
type influxBuilder struct {
	selectStr string
	fromStr   string
	whereArr  []string
	groupArr  []string
}

// Build builds the final influxDB query.
func (b influxBuilder) Build() string {
	cmd := fmt.Sprintf("SELECT %s FROM %s", b.selectStr, b.fromStr)
	for i, cond := range b.whereArr {
		if i == 0 {
			cmd = fmt.Sprintf("%s WHERE %s", cmd, cond)
		} else {
			cmd = fmt.Sprintf("%s AND %s", cmd, cond)
		}
	}
	for i, group := range b.groupArr {
		if i == 0 {
			cmd = fmt.Sprintf("%s GROUP BY %s", cmd, group)
		} else {
			cmd = fmt.Sprintf("%s AND %s", cmd, group)
		}
	}
	return cmd
}

// Select sets SELECT clause of query. It overrides previous value.
func (b influxBuilder) Select(s string) Builder {
	b.selectStr = s
	return b
}

// From sets FROM clause of query. It overrides previous value.
func (b influxBuilder) From(f string) Builder {
	b.fromStr = f
	return b
}

// Where appends WHERE condition. Previously added conditions stay untouched.
func (b influxBuilder) Where(cond string) Builder {
	b.whereArr = append(b.whereArr, cond)
	return b
}

// GroupBy appends GROUP BY condition. Previously added conditions stay untouched.
func (b influxBuilder) GroupBy(gb string) Builder {
	b.groupArr = append(b.groupArr, gb)
	return b
}
