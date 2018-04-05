package model

import (
	"github.com/olivere/elastic"
)

// ElasticDB represents data layer based on ElasticSearch.
type ElasticDB struct {
	Client *elastic.Client
	Debug  bool
}
