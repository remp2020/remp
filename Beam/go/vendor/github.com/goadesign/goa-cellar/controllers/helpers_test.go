package controllers

import (
	"fmt"
	"time"

	"github.com/goadesign/goa-cellar/store"
)

const (
	createdBy         = "test@goa.design"
	accountNameFormat = "Account #%d"
	bottleNameFormat  = "Bottle #%d in account #%d"
)

var (
	createdAt = time.Now()
	kind      = "wine"
	sweetness = 1
	country   = "usa"
	region    = "ca"
	review    = "review"
	rating    = 4
	varietal  = "pinot noir"
	vineyard  = "vineyard"
	vintage   = 2012
	color     = "red"
)

func createAccount(db *store.DB) *store.AccountModel {
	a := db.NewAccount()
	a.Name = fmt.Sprintf(accountNameFormat, a.ID)
	a.CreatedAt = createdAt
	a.CreatedBy = createdBy
	db.SaveAccount(a)
	return &a
}
func createBottle(db *store.DB, acctIdx int, year int) *store.BottleModel {
	b, err := db.NewBottle(acctIdx)
	if err != nil {
		return nil
	}
	b.Name = fmt.Sprintf(bottleNameFormat, b.ID, acctIdx)
	b.Kind = "red"
	b.Vineyard = "Blackstone"
	b.Varietal = "Marlot"
	b.Vintage = year
	b.Color = "red"
	b.Rating = &rating
	db.SaveBottle(b)
	return &b
}
