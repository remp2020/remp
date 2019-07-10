package controllers

import (
	"time"

	"github.com/goadesign/goa"
	"github.com/goadesign/goa-cellar/app"
	"github.com/goadesign/goa-cellar/store"
)

// ToAccountMedia builds an account media type from an account model.
func ToAccountMedia(account *store.AccountModel) *app.Account {
	return &app.Account{
		ID:        account.ID,
		Href:      app.AccountHref(account.ID),
		Name:      account.Name,
		CreatedAt: account.CreatedAt,
		CreatedBy: account.CreatedBy,
	}
}

// ToAccountMediaTiny builds an account media type with tiny view from an account model.
func ToAccountMediaTiny(account *store.AccountModel) *app.AccountTiny {
	return &app.AccountTiny{
		ID:   account.ID,
		Href: app.AccountHref(account.ID),
		Name: account.Name,
	}
}

// ToAccountLink builds an account link from an account model.
func ToAccountLink(accountID int) *app.AccountLink {
	return &app.AccountLink{
		ID:   accountID,
		Href: app.AccountHref(accountID),
	}
}

// AccountController implements the account resource.
type AccountController struct {
	*goa.Controller
	db *store.DB
}

// NewAccount creates a account controller.
func NewAccount(service *goa.Service, db *store.DB) *AccountController {
	return &AccountController{
		Controller: service.NewController("Account"),
		db:         db,
	}
}

// List retrieves all the accounts.
func (b *AccountController) List(c *app.ListAccountContext) error {
	accounts := b.db.GetAccounts()
	res := make(app.AccountTinyCollection, len(accounts))
	for i, account := range accounts {
		a := &app.AccountTiny{
			ID:   account.ID,
			Href: app.AccountHref(account.ID),
			Name: account.Name,
		}
		res[i] = a
	}
	return c.OKTiny(res)
}

// Show retrieves the account with the given id.
func (b *AccountController) Show(c *app.ShowAccountContext) error {
	account, ok := b.db.GetAccount(c.AccountID)
	if !ok {
		return c.NotFound()
	}
	return c.OK(ToAccountMedia(&account))
}

// Create records a new account.
func (b *AccountController) Create(c *app.CreateAccountContext) error {
	account := b.db.NewAccount()
	payload := c.Payload
	account.Name = payload.Name
	account.CreatedAt = time.Now()
	b.db.SaveAccount(account)
	c.ResponseData.Header().Set("Location", app.AccountHref(account.ID))
	return c.Created()
}

// Update updates a account field(s).
func (b *AccountController) Update(c *app.UpdateAccountContext) error {
	account, ok := b.db.GetAccount(c.AccountID)
	if !ok {
		return c.NotFound()
	}
	payload := c.Payload
	if payload.Name != "" {
		account.Name = payload.Name
	}
	b.db.SaveAccount(account)
	return c.NoContent()
}

// Delete removes a account from the database.
func (b *AccountController) Delete(c *app.DeleteAccountContext) error {
	account, ok := b.db.GetAccount(c.AccountID)
	if !ok {
		return c.NotFound()
	}
	b.db.DeleteAccount(account)
	return c.NoContent()
}
