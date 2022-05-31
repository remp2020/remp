package controller

import (
	"context"
	"time"
)

// EventProducer provides a method to produce and publish the tracked events as messages.
//
// The messages are published to the configured message queue implementation.
type EventProducer interface {
	// Produce generates a message and passes it to the message queue broker.
	Produce(ctx context.Context, table string, time time.Time, data map[string]any) error

	// Close closes any channels or connections that are opened by the producer.
	Close() error
}
