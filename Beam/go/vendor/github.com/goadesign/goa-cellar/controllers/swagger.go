package controllers

import "github.com/goadesign/goa"

// SwaggerController implements the swagger resource.
type SwaggerController struct {
	*goa.Controller
}

// NewSwagger creates a swagger controller.
func NewSwagger(service *goa.Service) *SwaggerController {
	return &SwaggerController{Controller: service.NewController("SwaggerController")}
}
