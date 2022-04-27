package controller

import (
	"context"
	"log"
	"time"

	"github.com/Shopify/sarama"
	influxClient "github.com/influxdata/influxdb/client/v2"
)

// Implementation of EventProducer
type KafkaEventProducer struct {
	EventProducer sarama.AsyncProducer
}

// NewKafkaEventProducer creates new KafkaEventProducer
func NewKafkaEventProducer(brokerAddrs []string) (*KafkaEventProducer, error) {
	config := sarama.NewConfig()
	config.ClientID = "beam-tracker"
	config.Producer.RequiredAcks = sarama.WaitForLocal       // Only wait for the leader to ack
	config.Producer.Compression = sarama.CompressionSnappy   // Compress messages
	config.Producer.Flush.Frequency = 500 * time.Millisecond // Flush batches every 500ms

	producer, err := sarama.NewAsyncProducer(brokerAddrs, config)
	if err != nil {
		return nil, err
	}

	// We will just log to STDOUT if we're not able to produce messages.
	// Note: messages will only be returned here after all retry attempts are exhausted.
	go func() {
		for err := range producer.Errors() {
			log.Println("Failed to write kafka producer entry:", err)
		}
	}()

	return &KafkaEventProducer{
		EventProducer: producer,
	}, nil
}

func (kep KafkaEventProducer) Produce(ctx context.Context, table string, time time.Time, data map[string]any) error {
	p, err := influxClient.NewPoint(table, nil, data, time)
	if err != nil {
		return err
	}
	kep.EventProducer.Input() <- &sarama.ProducerMessage{
		Topic: "beam_events",
		Value: sarama.StringEncoder(p.String()),
	}
	return nil
}

func (kep KafkaEventProducer) Close() error {
	return kep.EventProducer.Close()
}
