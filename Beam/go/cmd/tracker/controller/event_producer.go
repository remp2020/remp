package controller

import (
	"context"
	"time"
)

type EventProducer interface {
	Produce(ctx context.Context, table string, time time.Time, data map[string]any) error

	Close() error
}
