package store

import (
	"fmt"
	"sort"
	"sync"
	"time"
)

// Use variables for these so we can take their address
var (
	one   = 1
	three = 3
	four  = 4
	five  = 5
	usa   = "USA"
	ca    = "CA"
	gv    = "Great value"
	gbe   = "Good but expensive"
	ok    = "OK"
	fav   = "Favorite"
	solid = "Solid Pinot"
)

// DB emulates a database driver using in-memory data structures.
type DB struct {
	sync.Mutex
	maxAccountModelID int
	accounts          map[int]*AccountModel
	bottles           map[int][]*BottleModel
}

// BottleModel is the database "model" for bottles
type BottleModel struct {
	ID        int
	AccountID int
	Name      string
	Kind      string
	Color     string
	Country   *string
	CreatedAt time.Time
	Rating    *int
	Region    *string
	Review    *string
	Sweetness *int
	UpdatedAt time.Time
	Varietal  string
	Vineyard  string
	Vintage   int
}

// AccountModel is the database "model" for accounts
type AccountModel struct {
	ID        int
	Name      string
	CreatedAt time.Time
	CreatedBy string
}

// NewDB initializes a new "DB" with dummy data.
func NewDB() *DB {
	account := &AccountModel{ID: 1, Name: "account 1"}
	account2 := &AccountModel{ID: 2, Name: "account 2"}
	bottles := map[int][]*BottleModel{
		1: []*BottleModel{
			&BottleModel{
				ID:        100,
				AccountID: 1,
				Name:      "Number 8",
				Kind:      "wine",
				Vineyard:  "Asti Winery",
				Varietal:  "Merlot",
				Vintage:   2012,
				Color:     "red",
				Sweetness: &one,
				Country:   &usa,
				Region:    &ca,
				Review:    &gv,
				Rating:    &four,
			},
			&BottleModel{
				ID:        101,
				AccountID: 1,
				Name:      "Mourvedre",
				Kind:      "wine",
				Vineyard:  "Rideau",
				Varietal:  "Mourvedre",
				Vintage:   2012,
				Color:     "red",
				Sweetness: &one,
				Country:   &usa,
				Region:    &ca,
				Review:    &gbe,
				Rating:    &three,
			},
			&BottleModel{
				ID:        102,
				AccountID: 1,
				Name:      "Blue's Cuvee",
				Kind:      "wine",
				Vineyard:  "Longoria",
				Varietal:  "Cabernet Franc with Merlot, Malbec, Cabernet Sauvignon and Syrah",
				Vintage:   2012,
				Color:     "red",
				Sweetness: &one,
				Country:   &usa,
				Region:    &ca,
				Review:    &fav,
				Rating:    &five,
			},
		},
		2: []*BottleModel{
			&BottleModel{
				ID:        200,
				AccountID: 2,
				Name:      "Blackstone Merlot",
				Kind:      "wine",
				Vineyard:  "Blackstone",
				Varietal:  "Merlot",
				Vintage:   2012,
				Color:     "red",
				Sweetness: &one,
				Country:   &usa,
				Region:    &ca,
				Review:    &ok,
				Rating:    &three,
			},
			&BottleModel{
				ID:        201,
				AccountID: 2,
				Name:      "Wild Horse",
				Kind:      "wine",
				Vineyard:  "Wild Horse",
				Varietal:  "Pinot Noir",
				Vintage:   2010,
				Color:     "red",
				Sweetness: &one,
				Country:   &usa,
				Region:    &ca,
				Review:    &solid,
				Rating:    &four,
			},
		},
	}
	return &DB{accounts: map[int]*AccountModel{1: account, 2: account2}, bottles: bottles, maxAccountModelID: 2}
}

// Reset removes all entries from the database. Mainly intended for tests.
func (db *DB) Reset() {
	db.maxAccountModelID = 0
	db.accounts = make(map[int]*AccountModel)
	db.bottles = make(map[int][]*BottleModel)
}

// GetAccounts returns all the accounts.
func (db *DB) GetAccounts() []AccountModel {
	db.Lock()
	defer db.Unlock()
	ids := make([]int, len(db.accounts))
	i := 0
	for id := range db.accounts {
		ids[i] = id
		i++
	}
	sort.Ints(ids)
	list := make([]AccountModel, len(ids))
	for i, id := range ids {
		list[i] = *db.accounts[id]
	}
	return list
}

// GetAccount returns the account with given id if any, nil otherwise.
func (db *DB) GetAccount(id int) (model AccountModel, ok bool) {
	db.Lock()
	defer db.Unlock()
	var p *AccountModel
	if p, ok = db.accounts[id]; ok {
		model = *p
		ok = true
	}
	return
}

// NewAccount creates a new blank account resource.
func (db *DB) NewAccount() (model AccountModel) {
	db.Lock()
	defer db.Unlock()
	db.maxAccountModelID++
	model = AccountModel{ID: db.maxAccountModelID}
	db.accounts[db.maxAccountModelID] = &model
	return
}

// SaveAccount "persists" the account.
func (db *DB) SaveAccount(model AccountModel) {
	db.Lock()
	defer db.Unlock()
	db.accounts[model.ID] = &model
}

// DeleteAccount deletes the account.
func (db *DB) DeleteAccount(model AccountModel) {
	db.Lock()
	defer db.Unlock()
	delete(db.bottles, model.ID)
	delete(db.accounts, model.ID)
}

// GetBottle returns the bottle with the given id from the given account or nil if not found.
func (db *DB) GetBottle(account, id int) (model BottleModel, ok bool) {
	db.Lock()
	defer db.Unlock()
	bottles, found := db.bottles[account]
	if !found {
		return
	}
	for _, b := range bottles {
		if b.ID == id {
			model = *b
			ok = true
			break
		}
	}
	return
}

// GetBottles return the bottles from the given account.
func (db *DB) GetBottles(account int) ([]BottleModel, error) {
	db.Lock()
	defer db.Unlock()
	bottles, ok := db.bottles[account]
	if !ok {
		return nil, fmt.Errorf("unknown account %d", account)
	}
	list := make([]BottleModel, len(bottles))
	for i, b := range bottles {
		list[i] = *b
	}
	return list, nil
}

// GetBottlesByYears returns the bottles with the vintage in the given array from the given account.
func (db *DB) GetBottlesByYears(account int, years []int) ([]BottleModel, error) {
	db.Lock()
	defer db.Unlock()
	bottles, ok := db.bottles[account]
	if !ok {
		return nil, fmt.Errorf("unknown account %d", account)
	}
	var res []BottleModel
	for _, b := range bottles {
		for _, y := range years {
			if y == b.Vintage {
				goto selected
			}
		}
		continue
	selected:
		res = append(res, *b)
	}
	return res, nil
}

// NewBottle creates a new bottle resource.
func (db *DB) NewBottle(account int) (model BottleModel, err error) {
	db.Lock()
	defer db.Unlock()
	if _, ok := db.accounts[account]; !ok {
		return model, fmt.Errorf("unknown account %d", account)
	}
	bottles, _ := db.bottles[account]
	newID := 0
	for {
		// newID has to be incremented in the loop, not
		// in the for statement, otherwise it gets
		// incremented on break, we skip the first
		// available new ID, and all new bottle IDs
		// get the identical second available new ID
		newID++
		for _, b := range bottles {
			if b.ID == newID {
				goto taken
			}
		}
		break
	taken:
		continue
	}
	model = BottleModel{ID: newID, AccountID: account}
	model.CreatedAt = time.Now()
	db.bottles[account] = append(db.bottles[account], &model)
	return
}

// SaveBottle persists bottle to bottlesbase.
func (db *DB) SaveBottle(model BottleModel) {
	db.Lock()
	defer db.Unlock()

	bottles, found := db.bottles[model.AccountID]
	if found {
		for i := 0; i < len(bottles); i++ {
			if bottles[i].ID == model.ID {
				bottles[i] = &model
				break
			}
		}
	} else {
		db.bottles[model.AccountID] = append(db.bottles[model.AccountID], &model)
	}
}

// DeleteBottle deletes bottle from bottlesbase.
func (db *DB) DeleteBottle(model BottleModel) {
	db.Lock()
	defer db.Unlock()
	id, accountID := model.ID, model.AccountID
	if bs, ok := db.bottles[accountID]; ok {
		idx := -1
		for i, b := range bs {
			if b.ID == id {
				idx = i
				break
			}
		}
		if idx > -1 {
			bs = append(bs[:idx], bs[idx+1:]...)
			db.bottles[accountID] = bs
		}
	}
}
