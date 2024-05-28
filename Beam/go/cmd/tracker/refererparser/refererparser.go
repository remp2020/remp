/*
Package refererparser implements referer extraction using a shared 'database' of known referers
Original package from https://github.com/snowplow-referer-parser/golang-referer-parser

TODO: update referers database (JSON files) periodically, see https://github.com/snowplow-referer-parser/referer-parser
*/
package refererparser

import (
	bindata "beam/cmd/tracker/data"
	"encoding/json"
	"net/url"
	"strings"

	"github.com/imdario/mergo"
)

type refererData map[string]map[string]map[string][]string

var data refererData

func init() {
	data = loadRefererData()
}

// loadRefererData loads and parses the JSON files.
func loadRefererData() refererData {
	snowplowReferers, err := bindata.Asset("data/referers.json")
	if err != nil {
		panic(err)
	}
	referers1 := make(refererData)
	if err := json.Unmarshal(snowplowReferers, &referers1); err != nil {
		panic(err)
	}

	customReferers, err := bindata.Asset("data/custom_referers.json")
	if err != nil {
		panic(err)
	}
	referers2 := make(refererData)
	if err := json.Unmarshal(customReferers, &referers2); err != nil {
		panic(err)
	}

	mergo.Merge(&referers1, referers2)
	return referers1
}

// RefererResult holds the extracted data
type RefererResult struct {
	Known           bool
	Referer         string
	Medium          string
	SearchParameter string
	SearchTerm      string
	URI             *url.URL
}

// SetCurrent is used to set the "internal" medium if needed.
func (ref *RefererResult) SetCurrent(curl string) {
	purl, _ := url.Parse(curl)
	if purl.Host == ref.URI.Host {
		ref.Medium = "internal"
	}
}

func lookup(uri *url.URL, q string, suffix bool) (refResult *RefererResult) {
	refResult = &RefererResult{URI: uri, Medium: "unknown"}
	for medium, mediumData := range data {
		for refName, refconfig := range mediumData {
			for _, domain := range refconfig["domains"] {
				if (!suffix && q == domain) || (suffix && (strings.HasSuffix(q, domain) || strings.HasPrefix(q, domain))) {
					refResult.Known = true
					refResult.Referer = refName
					refResult.Medium = medium
					params, paramExists := refconfig["parameters"]
					if paramExists {
						for _, param := range params {
							sterm := uri.Query().Get(param)
							if sterm != "" {
								refResult.SearchParameter = param
								refResult.SearchTerm = sterm
							}
						}
					}
					return refResult
				}
			}
		}
	}
	return
}

// Parse an url and extract referer, it returns a RefererResult.
func Parse(uri string) (refResult *RefererResult) {
	puri, parseErr := url.Parse(uri)
	if parseErr != nil {
		return
	}
	// Split before the first dot ".".
	parts := strings.SplitAfterN(puri.Host, ".", 2)
	rhost := ""
	if len(parts) > 1 {
		rhost = parts[1]
	}
	queries := []string{puri.Host + puri.Path, rhost + puri.Path, puri.Host, rhost}
	for _, q := range queries {
		refResult = lookup(puri, q, false)
		if refResult.Known {
			return
		}
	}
	if !refResult.Known {
		for _, q := range queries {
			refResult = lookup(puri, q, true)
			if refResult.Known {
				return
			}
		}
	}
	return
}
