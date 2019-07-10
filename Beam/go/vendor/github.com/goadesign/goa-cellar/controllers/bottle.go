package controllers

import (
	"fmt"
	"io"
	"time"

	"github.com/goadesign/goa"
	"github.com/goadesign/goa-cellar/app"
	"github.com/goadesign/goa-cellar/store"
	"golang.org/x/net/websocket"
)

// ToBottleMedia converts a bottle model into a bottle media type
func ToBottleMedia(a *store.AccountModel, b *store.BottleModel) *app.Bottle {
	account := ToAccountMediaTiny(a)
	link := ToAccountLink(a.ID)
	return &app.Bottle{
		Account:  account,
		Href:     app.BottleHref(b.AccountID, b.ID),
		ID:       b.ID,
		Links:    &app.BottleLinks{Account: link},
		Name:     b.Name,
		Rating:   b.Rating,
		Varietal: b.Varietal,
		Vineyard: b.Vineyard,
		Vintage:  b.Vintage,
	}
}

// ToBottleMediaTiny builds an bottle media type with tiny view from an bottle model.
func ToBottleMediaTiny(bottle *store.BottleModel) *app.BottleTiny {
	link := ToAccountLink(bottle.AccountID)

	return &app.BottleTiny{
		ID:     bottle.ID,
		Href:   app.BottleHref(bottle.AccountID, bottle.ID),
		Links:  &app.BottleLinks{Account: link},
		Name:   bottle.Name,
		Rating: bottle.Rating,
	}
}

// BottleController implements the bottle resource.
type BottleController struct {
	*goa.Controller
	db *store.DB
}

// NewBottle creates a bottle controller.
func NewBottle(service *goa.Service, db *store.DB) *BottleController {
	return &BottleController{
		Controller: service.NewController("Bottle"),
		db:         db,
	}
}

// List lists all the bottles in the account optionally filtering by year.
func (b *BottleController) List(ctx *app.ListBottleContext) error {
	var bottles []store.BottleModel
	var err error
	if ctx.Years != nil {
		bottles, err = b.db.GetBottlesByYears(ctx.AccountID, ctx.Years)
	} else {
		bottles, err = b.db.GetBottles(ctx.AccountID)
	}
	if err != nil {
		return ctx.NotFound()
	}
	bs := make(app.BottleCollection, len(bottles))
	for i, bt := range bottles {
		a, ok := b.db.GetAccount(bt.AccountID)
		if !ok {
			return ctx.NotFound()
		}
		bs[i] = ToBottleMedia(&a, &bt)
	}
	return ctx.OK(bs)
}

// Show retrieves the bottle with the given id.
func (b *BottleController) Show(ctx *app.ShowBottleContext) error {
	account, ok := b.db.GetAccount(ctx.AccountID)
	if !ok {
		return ctx.NotFound()
	}
	bottle, ok := b.db.GetBottle(account.ID, ctx.BottleID)
	if !ok {
		return ctx.NotFound()
	}
	return ctx.OK(ToBottleMedia(&account, &bottle))
}

// Watch watches the bottle with the given id.
func (b *BottleController) Watch(ctx *app.WatchBottleContext) error {
	Watcher(ctx.AccountID, ctx.BottleID).ServeHTTP(ctx.ResponseWriter, ctx.Request)
	return nil
}

// Watcher echos the data received on the WebSocket.
func Watcher(accountID, bottleID int) websocket.Handler {
	return func(ws *websocket.Conn) {
		watched := fmt.Sprintf("Account: %d, Bottle: %d", accountID, bottleID)
		ws.Write([]byte(watched))
		io.Copy(ws, ws)
	}
}

// Create records a new bottle.
func (b *BottleController) Create(ctx *app.CreateBottleContext) error {
	bottle, err := b.db.NewBottle(ctx.AccountID)
	if err != nil {
		return ctx.NotFound()
	}
	payload := ctx.Payload
	bottle.Name = payload.Name
	bottle.Vintage = payload.Vintage
	bottle.Vineyard = payload.Vineyard
	bottle.CreatedAt = time.Now()
	bottle.UpdatedAt = bottle.CreatedAt
	if payload.Varietal != "" {
		bottle.Varietal = payload.Varietal
	}
	if payload.Color != "" {
		bottle.Color = payload.Color
	}
	if payload.Sweetness != nil {
		bottle.Sweetness = payload.Sweetness
	}
	if payload.Country != nil {
		bottle.Country = payload.Country
	}
	if payload.Region != nil {
		bottle.Region = payload.Region
	}
	if payload.Review != nil {
		bottle.Review = payload.Review
	}
	b.db.SaveBottle(bottle)
	ctx.ResponseData.Header().Set("Location", app.BottleHref(ctx.AccountID, bottle.ID))
	return ctx.Created()
}

// Update updates a bottle field(s).
func (b *BottleController) Update(ctx *app.UpdateBottleContext) error {
	bottle, ok := b.db.GetBottle(ctx.AccountID, ctx.BottleID)
	if !ok {
		return ctx.NotFound()
	}
	payload := ctx.Payload
	if payload.Name != nil {
		bottle.Name = *payload.Name
	}
	if payload.Vintage != nil {
		bottle.Vintage = *payload.Vintage
	}
	if payload.Vineyard != nil {
		bottle.Vineyard = *payload.Vineyard
	}
	if payload.Varietal != nil {
		bottle.Varietal = *payload.Varietal
	}
	if payload.Color != nil {
		bottle.Color = *payload.Color
	}
	if payload.Sweetness != nil {
		bottle.Sweetness = payload.Sweetness
	}
	if payload.Country != nil {
		bottle.Country = payload.Country
	}
	if payload.Region != nil {
		bottle.Region = payload.Region
	}
	if payload.Review != nil {
		bottle.Review = payload.Review
	}
	bottle.UpdatedAt = time.Now()

	b.db.SaveBottle(bottle)
	return ctx.NoContent()
}

// Delete removes a bottle from the database.
func (b *BottleController) Delete(ctx *app.DeleteBottleContext) error {
	bottle, ok := b.db.GetBottle(ctx.AccountID, ctx.BottleID)
	if !ok {
		return ctx.NotFound()
	}
	b.db.DeleteBottle(bottle)
	return ctx.NoContent()
}

// Rate rates a bottle.
func (b *BottleController) Rate(ctx *app.RateBottleContext) error {
	bottle, ok := b.db.GetBottle(ctx.AccountID, ctx.BottleID)
	if !ok {
		return ctx.NotFound()
	}
	bottle.Rating = &ctx.Payload.Rating
	bottle.UpdatedAt = time.Now()
	b.db.SaveBottle(bottle)
	return ctx.NoContent()
}
