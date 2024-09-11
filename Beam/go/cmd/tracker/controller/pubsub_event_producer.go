package controller

import (
	"context"
	"log"
	"time"

	"cloud.google.com/go/pubsub"
	influxClient "github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
)

// PubSubEventProducer is a Cloud PubSub implementation of EventProducer interface.
type PubSubEventProducer struct {
	client *pubsub.Client
	topic  *pubsub.Topic
}

// NewPubSubEventProducer creates new PubSubEventProducer
func NewPubSubEventProducer(ctx context.Context, projectID string, topicID string) (*PubSubEventProducer, error) {
	client, err := pubsub.NewClient(ctx, projectID)
	if err != nil {
		return nil, err
	}

	topic := client.Topic(topicID)
	ok, err := topic.Exists(ctx)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, errors.Errorf("PubSub: topic %q doesn't exist.", topicID)
	}

	return &PubSubEventProducer{
		client: client,
		topic:  topic,
	}, nil
}

// Produce generates a message and passes it to the configured Cloud Pub/Sub interface.
func (psep PubSubEventProducer) Produce(ctx context.Context, table string, time time.Time, data map[string]any) error {
	p, err := influxClient.NewPoint(table, nil, data, time)
	if err != nil {
		return err
	}

	result := psep.topic.Publish(ctx, &pubsub.Message{
		Data: []byte(p.String()),
	})

	go func(res *pubsub.PublishResult) {
		// Wait until the result is ready. If we called res.Get once the ctx is Done (it is once the request
		// is handled by HTTP server), this would log an error. If the result is ready, ctx status is ignored.
		<-res.Ready()

		_, err := res.Get(ctx)
		if err != nil {
			log.Printf("PubSub: failed to publish message: %v\n", err)
		}
	}(result)

	return nil
}

// Close closes the connection with the Cloud Pub/Sub client.
func (psep PubSubEventProducer) Close() error {
	psep.topic.Stop()
	return psep.client.Close()
}
