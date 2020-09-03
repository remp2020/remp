package controller

import (
	"beam/cmd/tracker/app"
	"beam/model"
	"fmt"
	"reflect"
	"time"

	"github.com/pkg/errors"
)

// EntitySchema represents extendended entity schema definition with validation capability.
type EntitySchema model.EntitySchema

// Validate validates provided entity against provided schema.
func (es *EntitySchema) Validate(payload *app.Entity) error {
	for name, val := range payload.EntityDef.Data {
		paramDef := es.Params[name]

		if paramDef == nil {
			return fmt.Errorf("parameter not allowed: %s", name)
		}

		switch v := val.(type) {
		case string:
			if paramDef.Type == "datetime" {
				_, err := time.Parse(time.RFC3339, v)
				if err != nil {
					return errors.Wrap(err, fmt.Sprintf("invalid type of param, RFC3339 datetime expected: %s", name))
				}

			} else if paramDef.Type != "string" {
				return fmt.Errorf("invalid type of param, %s expected: %s", paramDef.Type, name)
			}
		case float64:
			if paramDef.Type != "number" {
				return fmt.Errorf("invalid type of param, %s expected: %s", paramDef.Type, name)
			}
		case bool:
			if paramDef.Type != "boolean" {
				return fmt.Errorf("invalid type of param, %s expected: %s", paramDef.Type, name)
			}
		case []interface{}:
			if reflect.TypeOf(v).Kind() != reflect.Slice {
				return fmt.Errorf("invalid type of param, %s expected: %s", paramDef.Type, name)
			}

			for _, val := range v {
				if paramDef.Type == "string_array" && reflect.TypeOf(val).Kind() != reflect.String {
					return fmt.Errorf("invalid type of param, string array expected: %s", name)
				}
				if paramDef.Type == "number_array" && reflect.TypeOf(val).Kind() != reflect.Float64 {
					return fmt.Errorf("invalid type of param, number array expected: %s", name)
				}
			}
		}
	}

	return nil
}
