package gobdd

import (
  "testing"
  "bytes"
  "reflect"
  "fmt"
  "strings"
)

func TestDescribe(t *testing.T) {
  i := 0

  Describe("foo", func() {
    assertEqualObjects(t, len(testingContexts), 1)
    assertEqualObjects(t, testingContexts[0].Description, "foo")
    i++

    Describe("bar", func() {
      assertEqualObjects(t, len(testingContexts), 2)
      assertEqualObjects(t, testingContexts[0].Description, "foo")
      assertEqualObjects(t, testingContexts[1].Description, "bar")
      i++
    })
  })

  assertEqualObjects(t, i, 2)
  assertEqualObjects(t, len(testingContexts), 0)
}

func TestIt(t *testing.T) {
  i := 0

  Describe("foo", func() {
    It("is very cool", func() {
      assertEqualObjects(t, testingCurrentIt, "is very cool")
      i++
    })
  })

  assertEqualObjects(t, i, 1)
}

func TestBeforeEach(t *testing.T) {
  Describe("foo", func() {
    i := 0

    BeforeEach(func() {
      i++
    })

    It("will run", func() {
      assertEqualObjects(t, i, 1)
    })

    It("will run here too", func() {
      assertEqualObjects(t, i, 2)
    })

    Describe("bar", func() {
      j := 0

      BeforeEach(func() {
        j++
      })

      It("will run", func() {
        assertEqualObjects(t, j, 1)
        assertEqualObjects(t, i, 3)
      })

      It("will run here too", func() {
        assertEqualObjects(t, j, 2)
        assertEqualObjects(t, i, 4)
      })
    })
  })
}

func TestAfterEach(t *testing.T) {
  Describe("foo", func() {
    i := 0

    AfterEach(func() {
      i++
    })

    It("will run", func() {
      assertEqualObjects(t, i, 0)
    })

    It("will run here too", func() {
      assertEqualObjects(t, i, 1)
    })

    Describe("bar", func() {
      j := 0

      AfterEach(func() {
        j++
      })

      It("will run", func() {
        assertEqualObjects(t, j, 0)
        assertEqualObjects(t, i, 2)
      })

      It("will run here too", func() {
        assertEqualObjects(t, j, 1)
        assertEqualObjects(t, i, 3)
      })
    })
  })
}

func TestEqualAssertion(t *testing.T) {
  Describe("foo", func() {
    Describe("bar", func() {
      It("is good", func() {
        Expect(42, ToEqual, 42)
        Expect(24, ToEqual, 23)
      })
    })
  })

  assertEqualObjects(t, len(testingErrors), 1)
  assertEqualObjects(t, testingErrors[0].String, "expected: 23\n     got: 24\n")
  assertDeepEqualObjects(t, testingErrors[0].Contexts, []string{"foo", "bar"})
  assertEqualObjects(t, strings.HasSuffix(testingErrors[0].ErrorLine, "bdd_test.go:122"), true)

  testingErrors = testingErrors[0:0] // cleanup.. bleh
}

func TestPrintSpecReport(t *testing.T) {
  Describe("foo", func() {
    Describe("bar", func() {
      It("is cool", func() {
        Expect(23, ToEqual, 24)
      })
      It("is lame", func() {
        Expect(23, ToEqual, 23)
      })
    })
  })

  report, ok := BuildSpecReport()
  assertEqualObjects(t, ok, false)
  assertEqualObjects(t,
    strings.Contains(report, testingErrors[0].String) &&
    strings.Contains(report, testingErrors[0].ErrorLine) &&
    strings.Contains(report, testingErrors[0].Contexts[0]) &&
    strings.Contains(report, testingErrors[0].Contexts[1]) &&
    strings.Contains(report, "is cool"),
    true)

  stream := bytes.NewBufferString("")
  specReportStream = stream
  debugTesting = true
  PrintSpecReport()
  assertEqualObjects(t, stream.String(), report)
}

func TestPrintGreen(t *testing.T) {
  Describe("foo", func() {
    Describe("bar", func() {
      It("is cool", func() {
        Expect(23, ToEqual, 23)
      })
      It("is cool", func() {
        Expect(23, ToEqual, 23)
      })
    })
  })

  report, ok := BuildSpecReport()
  assertEqualObjects(t, ok, true)
  assertEqualObjects(t,
    strings.Contains(report, "All tests passed.") &&
    strings.Contains(report, "2 examples") &&
    strings.Contains(report, "0 failures"),
    true)
}

func init() {
  defer PrintSpecReport()

  type MyGreatTestType struct {
    Name string
    Age int
  }

  MyNil := func() *MyGreatTestType {
    return nil
  }

  MyInterfaceNil := func() interface{} {
    return nil
  }

  MyNonNil := func() *MyGreatTestType {
    return &MyGreatTestType{}
  }

  var anObject *MyGreatTestType

  Describe("matchers", func() {

    BeforeEach(func() {
      anObject = new(MyGreatTestType)
      anObject.Name = "john"
      anObject.Age = 23
    })

    Describe("not equals", func() {

      It("matches on simple objects", func() {
        Expect(&MyGreatTestType{"john", 23}, ToNotEqual, anObject)
        Expect("foo", ToEqual, "foo")
        Expect("foo", ToNotEqual, "bar")
      })

      It("matches for typed-nil", func() {
        Expect(MyNil(), ToBeNil)
        Expect(MyInterfaceNil(), ToBeNil)
        Expect(MyNonNil(), ToNotBeNil)
      })

      It("matches for nil", func() {
        Expect(nil, ToBeNil)
        Expect(true, ToNotBeNil)
      })

    })

    Describe("deep equals matcher", func() {

      It("matches what equals does not", func() {
        Expect(&MyGreatTestType{"john", 23}, ToDeepEqual, anObject)
        Expect("foo", ToDeepEqual, "foo")
      })

    })

    Describe("exception-rescuing matchers", func() {

      It("is super cool", func() {
        Expect(func() { panic("foobar!") }, ToPanicWith, "foobar!")
        Expect(func() {
          a := []int{1, 2, 3}
          _ = a[5]
        }, ToPanicWith, "runtime error: index out of range")
        Expect(func() {}, ToNotPanic)

        panicky := func() int {
          panic("sup")
          return 4; // chosen by fair dice roll.
                    // guaranteed to be random.
        }

        Expect(func() {
          It("doesnt panic in here", func() {
            Expect(panicky(), ToEqual, 4)
          })
        }, ToNotPanic)
      })

      Describe("custom matchers", func() {
        It("can take your own specified number of arguments", func() {
          Expect("foo", ToBeInside, []string{"foo"})
        })
      })

    })

  })
}

func ToBeInside(obj interface{}, array []string) (string, bool) {
  found := false

  for _, v := range array {
    if obj == v { found = true }
  }

  if !found {
    return fmt.Sprintf(
		"expected to find: %v\n"+
		"        in array: %v\n", obj, array), false
  }
  return "", true
}

func assertDeepEqualObjects(t *testing.T, obj interface{}, expected interface{}) {
  if !reflect.DeepEqual(obj, expected) {
    t.Errorf("expected [%v] to equal [%v]", expected, obj)
  }
}

func assertEqualObjects(t *testing.T, obj interface{}, expected interface{}) {
  if obj != expected {
    t.Errorf("expected [%v] to equal [%v]", expected, obj)
  }
}

func TestExceptions(t *testing.T) {
  foo := rescueException(func() { panic("foo") })
  assertEqualObjects(t, foo, "foo")

  none := rescueException(func() {})
  assertEqualObjects(t, none, nil)
}
