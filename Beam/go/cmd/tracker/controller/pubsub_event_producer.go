package controller

import (
	"context"
	"log"
	"time"

	"cloud.google.com/go/pubsub"
	influxClient "github.com/influxdata/influxdb/client/v2"
	"github.com/pkg/errors"
)

// Implementation of EventProducer
type PubSubEventProducer struct {
	client pubsub.Client
	topic  pubsub.Topic
}

// NewPubSubEventProducer creates new PubSubEventProducer
func NewPubSubEventProducer(ctx context.Context, projectId string, topicId string) (*PubSubEventProducer, error) {
	client, err := pubsub.NewClient(ctx, projectId)
	if err != nil {
		return nil, err
	}

	topic := client.Topic(topicId)
	ok, err := topic.Exists(ctx)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, errors.Errorf("PubSub: topic %q doesn't exist.", topicId)
	}

	return &PubSubEventProducer{
		client: *client,
		topic:  *topic,
	}, nil
}

func (psep PubSubEventProducer) Produce(ctx context.Context, table string, time time.Time, data map[string]any) error {
	p, err := influxClient.NewPoint(table, nil, data, time)
	if err != nil {
		return err
	}

	result := psep.topic.Publish(ctx, &pubsub.Message{
		Data: []byte(p.String()),
	})

	go func(res *pubsub.PublishResult) {
		_, err := res.Get(ctx)
		if err != nil {
			log.Printf("PubSub: failed to publish message: %v\n", err)
		}
	}(result)

	return nil
}

func (psep PubSubEventProducer) Close() error {
	return psep.client.Close()
}
