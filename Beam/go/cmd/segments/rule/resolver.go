package rule

import (
	"gitlab.com/remp/remp/Beam/go/model"
)

type Resolver interface {
	Resolve(model.SegmentRule) (string, error)
}
