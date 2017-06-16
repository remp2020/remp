package influxquery

import "fmt"

type Builder interface {
	Select(s string) Builder
	From(f string) Builder
	Where(cond string) Builder
	Build() string
}

func New() Builder {
	return &influxBuilder{
		selectStr: "*",
	}
}

// Builder represents very simplified builder of influxDB queries
type influxBuilder struct {
	selectStr string
	fromStr   string
	whereArr  []string
}

func (b *influxBuilder) Build() string {
	cmd := fmt.Sprintf("SELECT %s FROM %s", b.selectStr, b.fromStr)
	for i, cond := range b.whereArr {
		if i == 0 {
			cmd = fmt.Sprintf("%s WHERE %s", cmd, cond)
		} else {
			cmd = fmt.Sprintf("%s AND %s", cmd, cond)
		}
	}
	return cmd
}

func (b *influxBuilder) Select(s string) Builder {
	b.selectStr = s
	return b
}

func (b *influxBuilder) From(f string) Builder {
	b.fromStr = f
	return b
}

func (b *influxBuilder) Where(cond string) Builder {
	b.whereArr = append(b.whereArr, cond)
	return b
}
