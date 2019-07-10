package controllers

import (
	"context"
	"fmt"
	"strconv"
	"strings"
	"testing"
	"time"

	"github.com/goadesign/goa"
	"github.com/goadesign/goa-cellar/app"
	"github.com/goadesign/goa-cellar/app/test"
	"github.com/goadesign/goa-cellar/store"
)

func TestListAccount(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewAccount(service, db)
	)

	cases := map[string]struct{ NumAccounts int }{
		"empty":  {0},
		"single": {1},
		"many":   {5},
	}

	for k, tc := range cases {
		db.Reset()
		for i := 0; i < tc.NumAccounts; i++ {
			createAccount(db)
		}

		// Call generated test helper, this checks that the returned media type is of the
		// correct type (i.e. uses view "tiny") and validates the media type.
		_, accounts := test.ListAccountOKTiny(t, context.Background(), service, ctrl)

		if accounts == nil {
			t.Fatalf("%s: nil accounts", k)
		}
		if len(accounts) != tc.NumAccounts {
			t.Errorf("%s: invalid number of accounts, expected %d, got %d", k, tc.NumAccounts, len(accounts))
		}
		for i, a := range accounts {
			id := i + 1
			if a.ID != id {
				t.Errorf("%s: invalid account ID at index %d, expected %v, got %v", k, i, id, a.ID)
			}
			href := app.AccountHref(id)
			if a.Href != href {
				t.Errorf("%s: invalid account href at index %d, expected %+v, got %+v", k, i, href, a.Href)
			}
			if a.Name != fmt.Sprintf(accountNameFormat, id) {
				t.Errorf("%s: invalid account name at index %d, expected %+v, got %+v", k, i, fmt.Sprintf(accountNameFormat, id), a.Name)
			}
		}
	}
}

func TestShowAccount(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewAccount(service, db)
		timeA   = time.Now()
		timeB   = time.Now().Add(time.Duration(-1) * time.Hour)
	)

	cases := map[string]struct {
		CreatedAt time.Time
		CreatedBy string
	}{
		"time A": {timeA, createdBy},
		"time B": {timeB, createdBy},
		"other":  {timeA, "other@goa.design"},
	}

	for k, tc := range cases {
		db.Reset()
		a := createAccount(db)
		a.CreatedAt = tc.CreatedAt
		a.CreatedBy = tc.CreatedBy
		db.SaveAccount(*a)

		// Call generated test helper, this checks that the returned media type is of the
		// correct type (i.e. uses view "default") and validates the media type.
		_, account := test.ShowAccountOK(t, context.Background(), service, ctrl, 1)

		if account == nil {
			t.Fatalf("%s: nil account", k)
		}
		if account.ID != 1 {
			t.Errorf("%s: invalid account ID, expected 1, got %v", k, a.ID)
		}
		expected := app.AccountHref(1)
		if account.Href != expected {
			t.Errorf("%s: invalid account href, expected %v, got %v", k, expected, account.Href)
		}
		if account.Name != fmt.Sprintf(accountNameFormat, 1) {
			t.Errorf("%s: invalid account name, expected %s, got %s", k, fmt.Sprintf(accountNameFormat, account.ID), account.Name)
		}
		if account.CreatedAt != tc.CreatedAt {
			t.Errorf("%s: invalid account href, expected %v, got %v", k, tc.CreatedAt, account.CreatedAt)
		}
		if account.CreatedBy != tc.CreatedBy {
			t.Errorf("%s: invalid account CreatedBy, expected %v, got %v", k, tc.CreatedBy, account.CreatedBy)
		}
	}

	// The test helper takes care of validating the status code for us
	test.ShowAccountNotFound(t, context.Background(), service, ctrl, 42)
}

func TestDeleteAccount(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewAccount(service, db)
	)

	db.Reset()
	createAccount(db)

	// Call generated test helper, this checks that the returned media type is of the
	// correct type (i.e. uses view "default") and validates the media type.
	test.DeleteAccountNoContent(t, context.Background(), service, ctrl, 1)

	// The test helper takes care of validating the status code for us
	test.DeleteAccountNotFound(t, context.Background(), service, ctrl, 42)
}

func TestCreateAccount(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewAccount(service, db)
		ctx     = context.Background()
	)

	cases := map[string]struct{ Name string }{
		"name A": {"nameA"},
		"name B": {"nameB"},
	}

	for k, tc := range cases {
		db.Reset()
		payload := &app.CreateAccountPayload{Name: tc.Name}
		r := test.CreateAccountCreated(t, ctx, service, ctrl, payload)
		loc := r.Header().Get("Location")
		if loc == "" {
			t.Fatalf("%s: missing Location header", k)
		}
		elems := strings.Split(loc, "/")
		id, err := strconv.Atoi(elems[len(elems)-1])
		if err != nil {
			t.Fatalf("%s: invalid location header %v, must end with id", k, loc)
		}
		account, ok := db.GetAccount(id)
		if !ok {
			t.Fatalf("%s: account not saved in database", k)
		}
		if account.ID != 1 {
			t.Errorf("%s: invalid account ID, expected 1, got %v", k, account.ID)
		}
		if account.Name != tc.Name {
			t.Errorf("%s: invalid account name, expected %s, got %s", k, fmt.Sprintf(accountNameFormat, account.ID), account.Name)
		}
		if account.CreatedAt.IsZero() {
			t.Errorf("%s: invalid account created at: zero value", k)
		}
	}
}

func TestUpdateAccount(t *testing.T) {
	var (
		service = goa.New("cellar-test")
		db      = store.NewDB()
		ctrl    = NewAccount(service, db)
		ctx     = context.Background()
	)

	cases := map[string]struct{ Name string }{
		"name A": {"nameA"},
		"name B": {"nameB"},
	}

	for k, tc := range cases {
		db.Reset()
		a := createAccount(db)
		payload := &app.UpdateAccountPayload{Name: tc.Name}
		test.UpdateAccountNoContent(t, ctx, service, ctrl, a.ID, payload)
		account, ok := db.GetAccount(1)
		if !ok {
			t.Fatalf("%s: account not saved in database", k)
		}
		if account.ID != 1 {
			t.Errorf("%s: invalid account ID, expected %v, got %v", k, a.ID, account.ID)
		}
		if account.Name != tc.Name {
			t.Errorf("%s: invalid account name, expected %s, got %s", k, fmt.Sprintf(accountNameFormat, a.ID), account.Name)
		}
		if account.CreatedAt.IsZero() {
			t.Errorf("%s: invalid account created at: zero valuev", k)
		}
	}

	// The test helper takes care of validating the status code for us
	test.UpdateAccountNotFound(t, ctx, service, ctrl, 42, &app.UpdateAccountPayload{Name: "foo"})
}
