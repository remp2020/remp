package model

import (
	"github.com/jmoiron/sqlx"
)

type SegmentStorage interface {
	List() (SegmentCollection, error)

	Check(code, userID string) (bool, error)

	Users(code string) (UserCollection, error)
}

type Segment struct {
	Code  string
	Name  string
	Group *SegmentGroup
}

type SegmentCollection []*Segment

type SegmentGroup struct {
	ID      int
	Name    string
	Sorting int
}

type User struct {
	ID    string
	Email string
}

type UserCollection []*User

type SegmentDB struct {
	MySQL    *sqlx.DB
	InfluxDB *InfluxDB
}

func (sDB *SegmentDB) List() (SegmentCollection, error) {
	sc := SegmentCollection{}
	err := sDB.MySQL.Select(&sc, "SELECT name, code FROM segments")
	if err != nil {
		return nil, err
	}
	return sc, nil
}

func (sDB *SegmentDB) Check(code, userID string) (bool, error) {
	return true, nil
}

func (sDB *SegmentDB) Users(code string) (UserCollection, error) {
	sc := UserCollection{}

	return sc, nil
}
