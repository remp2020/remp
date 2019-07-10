package controllers

import (
	"fmt"

	"github.com/goadesign/goa"
	"github.com/goadesign/goa-cellar/app"
	"github.com/goadesign/goa-cellar/store"
)

// HealthController implements the account resource.
type HealthController struct {
	*goa.Controller
	db *store.DB
}

// NewHealth creates a account controller.
func NewHealth(service *goa.Service, db *store.DB) *HealthController {
	return &HealthController{
		Controller: service.NewController("Health"),
		db:         db,
	}
}

// Health performs the health-check.
func (b *HealthController) Health(c *app.HealthHealthContext) error {
	if _, ok := b.db.GetAccount(1); !ok {
		return fmt.Errorf("failed to connect to DB")
	}
	return c.OK([]byte("ok"))
}
