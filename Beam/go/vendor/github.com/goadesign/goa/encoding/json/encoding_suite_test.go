package json

import (
	. "github.com/onsi/ginkgo"
	. "github.com/onsi/gomega"

	"testing"
)

func TestJsonEncoding(t *testing.T) {
	RegisterFailHandler(Fail)
	RunSpecs(t, "Json Encoding Suite")
}
