package controller

import (
	"beam/cmd/segments/gen/swagger"
	"log"
)

// SwaggerController structure
type SwaggerController struct {
	logger *log.Logger
}

// NewSwaggerController returns the swagger service implementation.
func NewSwaggerController(logger *log.Logger) swagger.Service {
	return &SwaggerController{logger}
}
