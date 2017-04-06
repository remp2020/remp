package apidsl

import (
	. "github.com/onsi/ginkgo"
	. "github.com/onsi/gomega"

	"testing"
)

func TestApidsl(t *testing.T) {
	RegisterFailHandler(Fail)
	RunSpecs(t, "Apidsl Suite")
}
