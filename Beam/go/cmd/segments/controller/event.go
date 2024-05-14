package controller

import (
	"beam/cmd/segments/gen/events"
	"beam/model"
	"context"
	"time"
)

// EventController implements the event resource.
type EventController struct {
	EventStorage model.EventStorage
}

// NewEventController creates an event controller.
func NewEventController(es model.EventStorage) events.Service {
	return &EventController{es}
}

// CountAction returns counts of events for given action and category
func (c *EventController) CountAction(ctx context.Context, p *events.EventOptionsPayload) (res events.CountCollection, err error) {
	o, err := aggregateOptionsFromEventsOptions(p)
	if err != nil {
		return nil, err
	}
	acrc, err := processEventCount(c, o)
	if err != nil {
		return nil, err
	}
	return acrc, nil
}

// CountEndpoint runs the count action
func (c *EventController) CountEndpoint(ctx context.Context, p *events.EventOptionsPayload) (res events.CountCollection, err error) {
	o, err := aggregateOptionsFromEventsOptions(p)
	if err != nil {
		return nil, err
	}
	acrc, err := processEventCount(c, o)
	if err != nil {
		return nil, err
	}
	return acrc, nil
}

// List runs the list action.
func (c *EventController) List(ctx context.Context, p *events.ListEventOptionsPayload) (res events.EventsCollection, err error) {
	aggOptions, err := aggregateOptionsFromEventsOptions(p.Conditions)
	if err != nil {
		return nil, err
	}
	o := model.ListOptions{
		AggregateOptions: aggOptions,
		SelectFields:     p.SelectFields,
	}

	erc, err := c.EventStorage.List(o)
	if err != nil {
		return nil, err
	}
	mt, err := EventRowCollection(erc).ToMediaType()
	if err != nil {
		return nil, err
	}
	return mt, nil
}

// Categories runs the categories action.
func (c *EventController) Categories(ctx context.Context) (res []string, err error) {
	return c.EventStorage.Categories()
}

// Actions runs the action action. :)
func (c *EventController) Actions(ctx context.Context, p *events.ActionsPayload) (res []string, err error) {
	return c.EventStorage.Actions(*p.Category)
}

// Users runs the users action.
func (c *EventController) Users(ctx context.Context) (res []string, err error) {
	return c.EventStorage.Users()
}

func processEventCount(c *EventController, ao model.AggregateOptions) (events.CountCollection, error) {
	crc, ok, err := c.EventStorage.Count(ao)
	if err != nil {
		return nil, err
	}
	if !ok {
		cr := model.CountRow{
			Tags:  make(map[string]string),
			Count: 0,
		}
		crc = model.CountRowCollection{}
		crc = append(crc, cr)
	}

	return CountRowCollection(crc).ToEventsMediaType(), nil
}

// aggregateOptionsFromEventsOptions converts payload data to AggregateOptions.
func aggregateOptionsFromEventsOptions(payload *events.EventOptionsPayload) (model.AggregateOptions, error) {
	var o model.AggregateOptions

	for _, val := range payload.FilterBy {
		fb := &model.FilterBy{
			Tag:     val.Tag,
			Values:  val.Values,
			Inverse: false,
		}
		if val.Inverse != nil {
			fb.Inverse = *val.Inverse
		}
		o.FilterBy = append(o.FilterBy, fb)
	}

	o.GroupBy = payload.GroupBy
	if payload.TimeAfter != nil {
		t, _ := time.Parse(time.RFC3339, *payload.TimeAfter)
		o.TimeAfter = t
	}
	if payload.TimeBefore != nil {
		t, _ := time.Parse(time.RFC3339, *payload.TimeBefore)
		o.TimeBefore = t
	}

	if payload.TimeHistogram != nil {
		o.TimeHistogram = &model.TimeHistogram{
			Interval: payload.TimeHistogram.Interval,
		}

		if payload.TimeHistogram.TimeZone != nil {
			location, err := time.LoadLocation(*payload.TimeHistogram.TimeZone)
			if err != nil {
				return o, err
			}
			o.TimeHistogram.TimeZone = location
		}
	}

	if payload.Action != nil {
		o.Action = *payload.Action
	}

	if payload.Category != nil {
		o.Category = *payload.Category
	}

	return o, nil
}
