package controller

import (
	"beam/cmd/tracker/gen/swagger"
	"log"
)

type SwaggerController struct {
	logger *log.Logger
}

func NewSwaggerController(logger *log.Logger) swagger.Service {
	return &SwaggerController{logger}
}
