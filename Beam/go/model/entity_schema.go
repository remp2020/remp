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
	Get(name string) (*EntitySchema, bool, error)
	// Cache caches entity definitions.
	Cache(logger *log.Logger) error
}

// EntitySchemaParam represents single parameter of EntitySchema.
type EntitySchemaParam struct {
	Name string
	Type string
}

// EntitySchema represents definition of Entity.
type EntitySchema struct {
	ID        int            `db:"id"`
	ParentID  sql.NullInt64  `db:"parent_id"`
	Name      string         `db:"name"`
	CreatedAt time.Time      `db:"created_at"`
	UpdatedAt mysql.NullTime `db:"updated_at"`
	DeletedAt mysql.NullTime `db:"deleted_at"`

	Params map[string]*EntitySchemaParam
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
func (eDB *EntitySchemaDB) Get(name string) (*EntitySchema, bool, error) {
	es, ok := eDB.Entities[name]
	if ok {
		return es, true, nil
	}

	es = &EntitySchema{}
	err := eDB.MySQL.Get(es, "SELECT * FROM entities WHERE name = ?", name)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, false, nil
		}
		return nil, false, errors.Wrap(err, "unable to get schema from MySQL")
	}
	eDB.Entities[es.Name] = es
	return es, true, nil
}

// Cache caches entity definitions.
func (eDB *EntitySchemaDB) Cache(logger *log.Logger) error {
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
		logger.Println("entity schema cache reloaded")
	}

	eDB.Entities = em
	return nil
}
