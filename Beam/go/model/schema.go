package model

import (
	"database/sql"
	"log"
	"reflect"

	"github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
	"github.com/pkg/errors"
)

// SchemaStorage represents schemas storage interaface.
type SchemaStorage interface {
	Get(entityName string) (*EntitySchema, bool, error)
}

// EntitySchema structure
type EntitySchema struct {
	ID         int
	Parent_ID  sql.NullInt64
	Name       string
	Schema     string
	Created_At mysql.NullTime
	Updated_At mysql.NullTime
	Deleted_At mysql.NullTime
}

// EntitySchemaCollection is list of EntitySchemas
type EntitySchemaCollection []*EntitySchema

// EntitySchemaDB represents EntitySchema's storage MySQL implementation.
type EntitySchemaDB struct {
	MySQL         *sqlx.DB
	EntitySchemas map[string]*EntitySchema
}

// Get returns instance of EntitySchema based on the given entity name.
func (esDB *EntitySchemaDB) Get(Name string) (*EntitySchema, bool, error) {
	es, ok := esDB.EntitySchemas[Name]
	if ok {
		return es, true, nil
	}

	es = &EntitySchema{}
	err := esDB.MySQL.Get(es, "SELECT schema FROM entities WHERE name = ?", Name)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, false, nil
		}
		return nil, false, errors.Wrap(err, "unable to get schema from MySQL")
	}
	return es, true, nil
}

// Cache stores the schemas in memory.
func (esDB *EntitySchemaDB) Cache() error {
	esm := make(map[string]*EntitySchema)
	esc := EntitySchemaCollection{}

	err := esDB.MySQL.Select(&esc, "SELECT * FROM entities")
	if err != nil {
		if err == sql.ErrNoRows {
			return nil
		}
		return errors.Wrap(err, "unable to cache entity schemas from MySQL")
	}
	for _, p := range esc {
		esm[p.Name] = p
	}
	if !reflect.DeepEqual(esDB.EntitySchemas, esm) {
		log.Println("property cache reloaded")
	}

	esDB.EntitySchemas = esm
	return nil
}
