package genschema

import (
	. "github.com/onsi/ginkgo"
	. "github.com/onsi/gomega"

	"testing"
)

func TestGenSchema(t *testing.T) {
	RegisterFailHandler(Fail)
	RunSpecs(t, "GenSchema Suite")
}
