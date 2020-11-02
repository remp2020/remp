package controller

import "beam/cmd/tracker/refererparser"

// RefererResolver extends behavior of snowplow RefererResult
type RefererResolver struct {
	Referer       *refererparser.RefererResult
	InternalHosts []string
}

// SetCurrent sets the current URL in the underlying RefererResult and attempts to resolve "internal" status.
//
// The status is based on referer/current URL hosts comparison or based on list of internal hosts provided
// to RefererResolver (whichever is true).
func (ref *RefererResolver) SetCurrent(curl string) {
	ref.Referer.SetCurrent(curl)
	if ref.Referer.Medium == "internal" {
		return
	}

	for _, internalHost := range ref.InternalHosts {
		if ref.Referer.URI.Host == internalHost {
			ref.Referer.Medium = "internal"
			return
		}
	}
}
