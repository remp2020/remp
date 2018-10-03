package model

import (
	"database/sql"
	"fmt"
	"log"
	"reflect"
	"time"

	"github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
	"github.com/pkg/errors"
	"gitlab.com/remp/remp/Beam/go/cmd/tracker/app"
)

// Entities represents schemas storage interaface.
type Entities interface {
	Get(EntityName string) (*EntitySchema, bool, error)
	Validate(Schema *EntitySchema, data *app.Entity) error
}

// EntitySchemaParam struct
type EntitySchemaParam struct {
	Name string
	Type string
}

// EntitySchema structure
type EntitySchema struct {
	ID         int
	Parent_ID  sql.NullInt64
	Name       string
	Created_At time.Time
	Updated_At mysql.NullTime
	Deleted_At mysql.NullTime
	Params     map[string]*EntitySchemaParam
}

// EntitySchemaCollection is list of EntitySchemas
type EntitySchemaCollection []*EntitySchema

// EntitySchemaParamsCollection is list of EntitySchemaParams
type EntitySchemaParamsCollection []*EntitySchemaParam

// EntitySchemaDB represents EntitySchema's storage MySQL implementation.
type EntitySchemaDB struct {
	MySQL    *sqlx.DB
	Entities map[string]*EntitySchema
}

// Get returns instance of EntitySchema based on the given entity name.
func (eDB *EntitySchemaDB) Get(EntityName string) (*EntitySchema, bool, error) {
	es, ok := eDB.Entities[EntityName]
	if ok {
		return es, true, nil
	}

	es = &EntitySchema{}
	err := eDB.MySQL.Get(es, "SELECT schema FROM entities WHERE name = ?", EntityName)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, false, nil
		}
		return nil, false, errors.Wrap(err, "unable to get schema from MySQL")
	}
	return es, true, nil
}

// Validate payload
func (eDB *EntitySchemaDB) Validate(Schema *EntitySchema, payload *app.Entity) error {
	for i, r := range payload.Entity.Data {
		p := Schema.Params[i]

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

// Cache stores the schemas in memory.
func (eDB *EntitySchemaDB) Cache() error {
	em := make(map[string]*EntitySchema)
	ec := EntitySchemaCollection{}

	err := eDB.MySQL.Select(&ec, `
		SELECT *
		FROM entities
		WHERE deleted_at IS NULL
	`)

	if err != nil {
		if err == sql.ErrNoRows {
			return nil
		}
		return errors.Wrap(err, "unable to cache entity schemas from MySQL")
	}
	for _, e := range ec {
		em[e.Name] = e

		epm := make(map[string]*EntitySchemaParam)
		epc := EntitySchemaParamsCollection{}

		err := eDB.MySQL.Select(&epc, `
			SELECT name, type
			FROM entity_params
			WHERE entity_id = ?
			AND deleted_at IS NULL
		`, e.ID)

		if err != nil {
			if err == sql.ErrNoRows {
				return nil
			}
			return errors.Wrap(err, "unable to cache entity schemas from MySQL")
		}

		for _, p := range epc {
			epm[p.Name] = p
		}

		em[e.Name].Params = epm
	}
	if !reflect.DeepEqual(eDB.Entities, em) {
		log.Println("property cache reloaded")
	}

	// log.Println(esm["user"])

	eDB.Entities = em
	return nil
}
