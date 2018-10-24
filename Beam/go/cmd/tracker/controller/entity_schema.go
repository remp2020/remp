package controller

import (
	"fmt"
	"reflect"
	"time"

	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/app"
	"gitlab.com/remp/remp/Beam/go/model"
)

// EntitySchema represents extendended entity schema definition with validation capability.
type EntitySchema model.EntitySchema

// Validate validates provided entity against provided schema.
func (es *EntitySchema) Validate(payload *app.Entity) error {
	for i, r := range payload.Entity.Data {
		p := es.Params[i]

		if p == nil {
			return fmt.Errorf("not allowed parameter: '%v'", i)
		}

		switch v := r.(type) {
		case string:
			if p.Type == "datetime" {
				_, err := time.Parse(time.RFC3339, v)
				if err != nil {
					return errors.Wrap(err, fmt.Sprintf("param: '%v' should be valid RFC3339 date", i))
				}

			} else if p.Type != "string" {
				return fmt.Errorf("param: '%v' should be type of '%v'", i, p.Type)
			}
		case float64:
			if p.Type != "number" {
				return fmt.Errorf("param: '%v' should be type of '%v'", i, p.Type)
			}
		case bool:
			if p.Type != "boolean" {
				return fmt.Errorf("param: '%v' should be type of '%v'", i, p.Type)
			}
		case []interface{}:
			if reflect.TypeOf(v).Kind() != reflect.Slice {
				return fmt.Errorf("param: '%v' should be type of '%v'", i, p.Type)
			}

			for _, val := range v {
				if p.Type == "string_array" && reflect.TypeOf(val).Kind() != reflect.String {
					return fmt.Errorf("param: '%v' should be type of '%v'", i, p.Type)
				}
				if p.Type == "number_array" && reflect.TypeOf(val).Kind() != reflect.Float64 {
					return fmt.Errorf("param: '%v' should be type of '%v'", i, p.Type)
				}
			}
		}
	}

	return nil
}
