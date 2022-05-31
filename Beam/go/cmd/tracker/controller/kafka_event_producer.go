package controller

import (
	"context"
	"log"
	"time"

	"github.com/Shopify/sarama"
	influxClient "github.com/influxdata/influxdb/client/v2"
)

// KafkaEventProducer implements Tracker's EventProducer interface.
type KafkaEventProducer struct {
	EventProducer sarama.AsyncProducer
}

// SaslConfig holds optional configuration settings for Kafka authentication.
type SaslConfig struct {
	Username string
	Password string
}

// NewKafkaEventProducer creates new KafkaEventProducer
func NewKafkaEventProducer(brokerAddrs []string, saslConfig *SaslConfig) (*KafkaEventProducer, error) {
	config := sarama.NewConfig()
	config.ClientID = "beam-tracker"
	config.Producer.RequiredAcks = sarama.WaitForLocal       // Only wait for the leader to ack
	config.Producer.Compression = sarama.CompressionSnappy   // Compress messages
	config.Producer.Flush.Frequency = 500 * time.Millisecond // Flush batches every 500ms

	if saslConfig.Username != "" && saslConfig.Password != "" {
		config.Net.SASL.Enable = true
		config.Net.SASL.User = saslConfig.Username
		config.Net.SASL.Password = saslConfig.Password
	}

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

// Produce generates a message in the influx-data format and passes it to the configured Kafka instance.
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

// Close closes the connection to the Kafka event producer.
func (kep KafkaEventProducer) Close() error {
	return kep.EventProducer.Close()
}
