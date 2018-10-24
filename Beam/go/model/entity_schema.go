package model

import (
	"database/sql"
	"log"
	"reflect"
	"time"

	"github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
	"github.com/pkg/errors"
)

// EntitySchemaStorage represents schemas storage interaface.
type EntitySchemaStorage interface {
	// Get returns EntitySchema based on provided EntityName.
	Get(EntityName string) (*EntitySchema, bool, error)
	// Cache caches entity definitions.
	Cache() error
}

// EntitySchemaParam represents single parameter of EntitySchema.
type EntitySchemaParam struct {
	Name string
	Type string
}

// EntitySchema represents definition of Entity.
type EntitySchema struct {
	ID        int
	ParentID  sql.NullInt64
	Name      string
	CreatedAt time.Time
	UpdatedAt mysql.NullTime
	DeletedAt mysql.NullTime
	Params    map[string]*EntitySchemaParam
}

// EntitySchemaCollection is list of EntitySchemas.
type EntitySchemaCollection []*EntitySchema

// EntitySchemaParamsCollection is list of EntitySchemaParams.
type EntitySchemaParamsCollection []*EntitySchemaParam

// EntitySchemaDB represents EntitySchemaStorage MySQL implementation.
type EntitySchemaDB struct {
	MySQL    *sqlx.DB
	Entities map[string]*EntitySchema
}

// Get returns EntitySchema based on provided EntityName.
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

// Cache caches entity definitions.
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

	eDB.Entities = em
	return nil
}
