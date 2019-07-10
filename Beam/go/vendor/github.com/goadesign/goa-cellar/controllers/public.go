package controllers

import "github.com/goadesign/goa"

// PublicController implements the public resource.
type PublicController struct {
	*goa.Controller
}

// NewPublic creates a public controller.
func NewPublic(service *goa.Service) *PublicController {
	return &PublicController{Controller: service.NewController("PublicController")}
}
