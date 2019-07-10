package controllers

import (
	"context"
	"fmt"
	"strconv"
	"strings"
	"testing"

	"github.com/goadesign/goa"
	"github.com/goadesign/goa-cellar/app"
	"github.com/goadesign/goa-cellar/app/test"
	"github.com/goadesign/goa-cellar/store"
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
	napa  = "Napa Valley"
	fav   = "Favorite"
	solid = "Solid Pinot"
)

func TestCreateBottle(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewBottle(service, db)
		ctx     = context.Background()
	)

	cases := map[string]struct{ Name string }{
		"name A": {"Bottle #1"},
		"name B": {"Bottle #2"},
	}

	for k, tc := range cases {
		db.Reset()

		// we're not testing accounts, so use database directly to set it up
		account := db.NewAccount()

		payload := &app.CreateBottlePayload{
			Color:     "red",
			Country:   &usa,
			Name:      tc.Name,
			Region:    &napa,
			Review:    &gbe,
			Sweetness: &four,
			Varietal:  "Cabernet Sauvignon",
			Vineyard:  "Joe's vineyard",
			Vintage:   2012,
		}

		r := test.CreateBottleCreated(t, ctx, service, ctrl, account.ID, payload)
		loc := r.Header().Get("Location")
		if loc == "" {
			t.Fatalf("%s: missing Location header", k)
		}
		elems := strings.Split(loc, "/")
		id, err := strconv.Atoi(elems[len(elems)-1])
		if err != nil {
			t.Fatalf("%s: invalid location header %v, must end with id", k, loc)
		}
		bottle, ok := db.GetBottle(account.ID, id)
		if !ok {
			t.Fatalf("%s: bottle not saved in database", k)
		}
		if bottle.ID != 1 {
			t.Errorf("%s: invalid bottle ID, expected 1, got %v", k, bottle.ID)
		}
		if bottle.Name != tc.Name {
			t.Errorf("%s: invalid bottle name, expected %s, got %s", k, fmt.Sprintf(bottleNameFormat, bottle.ID, account.ID), bottle.Name)
		}
		if bottle.CreatedAt.IsZero() {
			t.Errorf("%s: invalid bottle created at: zero value", k)
		}
		if bottle.UpdatedAt.IsZero() {
			t.Errorf("%s: invalid bottle created at: zero value", k)
		}
	}
}

func TestListBottle(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewBottle(service, db)
	)

	accountCases := map[string]struct{ NumAccounts int }{
		"single account": {1},
		"many accounts":  {5},
	}
	bottleCases := map[string]struct{ NumBottles int }{
		"single bottle": {1},
		"many bottles":  {5},
	}

	years := []int{2010, 2012, 2014, 2016}

	for bottleKey, tcBottle := range bottleCases {
		for acctKey, tcAcct := range accountCases {
			db.Reset()
			for i := 0; i < tcAcct.NumAccounts; i++ {
				createAccount(db)
			}

			accounts := db.GetAccounts()

			if len(accounts) != tcAcct.NumAccounts {
				t.Errorf("%s: invalid number of accounts, expected %d, got %d", acctKey, tcAcct.NumAccounts, len(accounts))
			}

			for acctIdx := 0; acctIdx < len(accounts); acctIdx++ {
				a := accounts[acctIdx]
				for j := 0; j < tcBottle.NumBottles; j++ {
					for _, y := range years {
						createBottle(db, a.ID, y)
					}
				}
				// Call generated test helper, this checks that the returned media type is of the
				// correct type (i.e. uses view "tiny") and validates the media type.
				_, bottles := test.ListBottleOK(t, context.Background(), service, ctrl, a.ID, years)
				if len(bottles) != tcBottle.NumBottles*len(years) {
					t.Errorf("%s: invalid number of bottles for multiple years, expected %d, got %d", bottleKey, tcBottle.NumBottles*len(years), len(bottles))
				}
				for j, b := range bottles {
					href := app.BottleHref(a.ID, b.ID)
					if b.Href != href {
						t.Errorf("%s: invalid bottle href at index %d in bottle %d, expected %+v, got %+v", bottleKey, j, b.ID, href, b.Href)
					}
					if b.Name != fmt.Sprintf(bottleNameFormat, b.ID, a.ID) {
						t.Errorf("%s: invalid bottle name at index %d in account %d, expected %+v, got %+v", bottleKey, j, a.ID, fmt.Sprintf(bottleNameFormat, b.ID, a.ID), b.Name)
					}
					if *(b.Rating) != rating {
						t.Errorf("%s: invalid bottle rating at index %d in account %d, expected %v, got %v", bottleKey, j, a.ID, rating, *(b.Rating))
					}
				}
				// test listing according to year
				for yearIdx := 0; yearIdx < len(years); yearIdx++ {
					_, bottles := test.ListBottleOK(t, context.Background(), service, ctrl, a.ID, years[yearIdx:yearIdx+1])
					if len(bottles) != tcBottle.NumBottles {
						t.Errorf("%s: invalid number of bottles for a single year, expected %d, got %d", bottleKey, tcBottle.NumBottles, len(bottles))
					}
				}
			}
		}
	}
}

func TestShowBottle(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewBottle(service, db)
	)

	cases := map[string]int{
		"Year 2010": 2010,
		"Year 2012": 2012,
		"Year 2014": 2014,
		"Year 2015": 2015,
		"Year 2016": 2016,
		"Year 2017": 2017,
	}

	for k, y := range cases {
		db.Reset()
		a := createAccount(db)

		b := createBottle(db, a.ID, y)
		if b.ID != 1 {
			t.Errorf("%s: invalid bottle ID, expected 1, got %v", k, b.ID)
		}
		// Call generated test helper, this checks that the returned media type is of the
		// correct type (i.e. uses view "default") and validates the media type.
		_, bottle := test.ShowBottleOK(t, context.Background(), service, ctrl, a.ID, b.ID)

		if bottle == nil {
			t.Fatalf("%s: nil bottle", k)
		}
		if bottle.ID != b.ID {
			t.Errorf("%s: invalid bottle ID, expected %v, got %v", k, b.ID, bottle.ID)
		}
		expected := app.BottleHref(a.ID, b.ID)
		if bottle.Href != expected {
			t.Errorf("%s: invalid bottle href, expected %v, got %v", k, expected, bottle.Href)
		}
		if bottle.Name != fmt.Sprintf(bottleNameFormat, b.ID, a.ID) {
			t.Errorf("%s: invalid bottle name, expected %s, got %s", k, fmt.Sprintf(bottleNameFormat, b.ID, a.ID), bottle.Name)
		}
		if bottle.Vintage != y {
			t.Errorf("%s: invalid bottle href, expected %v, got %v", k, y, bottle.Vintage)
		}
		// The test helper takes care of validating the status code for us
		test.ShowBottleNotFound(t, context.Background(), service, ctrl, a.ID, 42)
	}
}

func TestDeleteBottle(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewBottle(service, db)
	)

	db.Reset()
	a := createAccount(db)
	b := createBottle(db, a.ID, 2012)

	// Call generated test helper, this checks that the returned media type is of the
	// correct type (i.e. uses view "default") and validates the media type.
	test.DeleteBottleNoContent(t, context.Background(), service, ctrl, a.ID, b.ID)

	// Call generated test helper, this checks that the returned media type is of the
	// correct type (i.e. uses view "default") and validates the media type.
	test.ShowBottleNotFound(t, context.Background(), service, ctrl, a.ID, b.ID)

	// The test helper takes care of validating the status code for us
	test.DeleteBottleNotFound(t, context.Background(), service, ctrl, a.ID, b.ID)
}

func TestUpdateBottle(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewBottle(service, db)
		ctx     = context.Background()
	)

	cases := map[string]struct{ Name string }{
		"name A": {"updated bottle nameA"},
		"name B": {"updated bottle nameB"},
	}
	var a *store.AccountModel
	var payload *app.BottlePayload
	for k, tc := range cases {
		db.Reset()
		a = createAccount(db)
		b := createBottle(db, a.ID, 2010)
		payload = &app.BottlePayload{
			Color:     &color,
			Country:   &usa,
			Name:      &tc.Name,
			Region:    &napa,
			Review:    &gbe,
			Sweetness: &four,
			Varietal:  &varietal,
			Vineyard:  &vineyard,
			Vintage:   &vintage,
		}
		test.UpdateBottleNoContent(t, ctx, service, ctrl, a.ID, b.ID, payload)
		ub, ok := db.GetBottle(a.ID, b.ID)
		if !ok {
			t.Fatalf("%s: bottle not saved in database", k)
		}
		if ub.ID != b.ID {
			t.Errorf("%s: invalid bottle ID, expected %v, got %v", k, b.ID, ub.ID)
		}
		if ub.Name != tc.Name {
			t.Errorf("%s: invalid bottle name, expected %s, got %s", k, tc.Name, ub.Name)
		}
		if b.CreatedAt.IsZero() {
			t.Errorf("%s: invalid bottle created at: zero value", k)
		}
		if b.CreatedAt != ub.CreatedAt {
			t.Errorf("%s: invalid bottle created at: expected %+v, got %+v", k, b.CreatedAt, ub.CreatedAt)
		}
		if b.UpdatedAt == ub.UpdatedAt {
			t.Errorf("%s: invalid bottle updated at: identical to when created", k)
		}
	}

	// The test helper takes care of validating the status code for us
	test.UpdateBottleNotFound(t, ctx, service, ctrl, a.ID, 42, payload)
}
