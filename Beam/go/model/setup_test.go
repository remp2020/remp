package model

import (
	"context"
	"crypto/tls"
	"net/http"
	"testing"
	"time"

	"github.com/elastic/go-elasticsearch/v8"
	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
)

// Shared fixed historical dates for all integration tests.
// Nov 20-21, 2025 is a stable period with known production data.
var (
	testHourStart = time.Date(2025, time.November, 20, 10, 0, 0, 0, time.UTC)
	testHourEnd   = time.Date(2025, time.November, 20, 11, 0, 0, 0, time.UTC)
)

// TestConfig holds configuration for tests.
type TestConfig struct {
	ElasticAddrs  string `envconfig:"elastic_addrs" default:"http://localhost:9200"`
	ElasticUser   string `envconfig:"elastic_user" default:""`
	ElasticPasswd string `envconfig:"elastic_passwd" default:""`
	IndexPrefix   string `envconfig:"index_prefix" default:"beam_test_"`
}

// setupTestClient creates an Elasticsearch v8 client for integration tests.
// Accepts testing.TB so it can be called from both tests and benchmarks.
func setupTestClient(t testing.TB) (*ElasticsearchDB, func()) {
	t.Helper()
	_ = godotenv.Load("../../.env")
	_ = godotenv.Load("../cmd/segments/.env")
	var c TestConfig
	if err := envconfig.Process("segments", &c); err != nil {
		t.Skipf("Cannot process envconfig: %v", err)
	}

	ctx := context.Background()

	cfg := elasticsearch.Config{
		Addresses: []string{c.ElasticAddrs},
		Transport: &http.Transport{
			TLSClientConfig: &tls.Config{
				InsecureSkipVerify: true,
			},
		},
	}
	if c.ElasticUser != "" {
		cfg.Username = c.ElasticUser
		cfg.Password = c.ElasticPasswd
	}

	client, err := elasticsearch.NewClient(cfg)
	if err != nil {
		t.Skipf("Cannot create Elasticsearch client: %v", err)
	}

	_, err = client.Ping()
	if err != nil {
		t.Skipf("Elasticsearch client cannot connect: %v", err)
	}

	db := NewElasticsearchDB(ctx, client, c.IndexPrefix, false)

	return db, func() {}
}
