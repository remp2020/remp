package gobdd

import (
  "fmt"
  "runtime"
  "reflect"
  "strings"
  "io"
  "os"
)


type context struct {
  Description string
  BeforeEach func()
  AfterEach func()
}

type testingError struct {
  String string
  ItString string
  Contexts []string
  ErrorLine string
}

var debugTesting = false
var testingContexts []*context
var testingCurrentIt string
var testingExamples int
var testingErrors []*testingError
var specReportStream io.ReadWriter

var redColor = fmt.Sprintf("%c[31m", 27)
var greenColor = fmt.Sprintf("%c[32m", 27)
var resetColors = fmt.Sprintf("%c[0m", 27)

func Describe(s string, f func()) {
  testingContexts = append(testingContexts, &context{Description: s})
  f()
  testingContexts = testingContexts[0:len(testingContexts)-1]
}

func It(s string, f func()) {
  for _, testingContext := range testingContexts {
    if beforeFunc := testingContext.BeforeEach; beforeFunc != nil {
      beforeFunc()
    }
  }

  testingCurrentIt = s
  testingExamples++

  Expect(func() { f() }, ToNotPanic)

  for _, testingContext := range testingContexts {
    if afterFunc := testingContext.AfterEach; afterFunc != nil {
      afterFunc()
    }
  }
}

func BeforeEach(f func()) {
  testingContexts[len(testingContexts)-1].BeforeEach = f
}

func AfterEach(f func()) {
  testingContexts[len(testingContexts)-1].AfterEach = f
}

func getErrorLine() string {
  pc, _, _, _ := runtime.Caller(3)
  file, line := runtime.FuncForPC(pc).FileLine(pc)
  return fmt.Sprintf("%s:%d", file, line)
}

type validNilType interface{}
func validNil() *validNilType { return nil }

func appendValueFor(array []reflect.Value, obj interface{}) []reflect.Value {
  var value reflect.Value
  if reflect.TypeOf(obj) == nil {
    value = reflect.ValueOf(validNil())
  } else {
    value = reflect.ValueOf(obj)
  }
  return append(array, value)
}

func Expect(obj interface{}, test interface{}, args ...interface{}) {
  var argValues []reflect.Value

  argValues = appendValueFor(argValues, obj)
  for _, v := range args {
    argValues = appendValueFor(argValues, v)
  }

  // fmt.Println(obj, reflect.ValueOf(obj), argValues, reflect.TypeOf(obj) == nil)
  returnValues := reflect.ValueOf(test).Call(argValues)
  str, ok := returnValues[0].String(), returnValues[1].Bool()

  if !ok {
    addErrorObject(str)
  }
}

func ToEqual(actual interface{}, expected interface{}) (string, bool) {
  if actual != expected {
    return fmt.Sprintf("expected: %#v\n     got: %#v\n", expected, actual), false
  }
  return "", true
}

func ToNotEqual(actual interface{}, expected interface{}) (string, bool) {
  if expected == actual {
    return fmt.Sprintf(" expected: %#v\nto not be: %#v\n", expected, actual), false
  }
  return "", true
}

func ToBeNil(actual interface{}) (string, bool) {
  if !reflect.DeepEqual(reflect.ValueOf(nil), reflect.Indirect(reflect.ValueOf(actual))) {
    return fmt.Sprintf("expected to be nil,\n           but got: %#v\n", actual), false
  }
  return "", true
}

func ToNotBeNil(actual interface{}) (string, bool) {
  if reflect.DeepEqual(reflect.ValueOf(nil), reflect.Indirect(reflect.ValueOf(actual))) {
    return "expected to not be nil,\n               but got: nil\n", false
  }
  return "", true
}

func ToDeepEqual(actual interface{}, expected interface{}) (string, bool) {
  if !reflect.DeepEqual(actual, expected) {
    return fmt.Sprintf("    expected: %#v\nto deeply be: %#v\n", expected, actual), false
  }
  return "", true
}

func ToPanicWith(actual interface{}, expected interface{}) (string, bool) {
  actual = rescueException(actual.(func()))

  switch actual.(type) {
  case error:
    if actual.(error).Error() != expected {
      return fmt.Sprintf("expected panic: %v\n           got: %v\n", expected, actual), false
    }
  default:
    if actual != expected {
      return fmt.Sprintf("expected panic: %v\n           got: %v\n", expected, actual), false
    }
  }
  return "", true
}

func ToNotPanic(actual interface{}) (string, bool) {
  actual = rescueException(actual.(func()))

  if actual != nil {
    return fmt.Sprintf("expected no panic,\n          but got: %#v\n", actual), false
  }
  return "", true
}

func rescueException(try func()) (out interface{}) {
  defer func() {
    out = recover()
  }()
  out = recover()
  try()
  return nil
}

func addErrorObject(s string) {
  var contexts []string
  for _, testingContext := range testingContexts {
    contexts = append(contexts, testingContext.Description)
  }

  testingErrors = append(testingErrors, &testingError{
    String: s,
    ItString: testingCurrentIt,
    Contexts: contexts,
    ErrorLine: getErrorLine(),
  })
}



func BuildSpecReport() (string, bool) {
  var s string

  ok := len(testingErrors) == 0

  if !ok {
    s += redColor

    for _, error := range testingErrors {
      indents := 0

      for _, contextStr := range error.Contexts {
        s += fmt.Sprintf("%s- %s\n", strings.Repeat("  ", indents), contextStr)
        indents++
      }

      s += fmt.Sprintf("%s  %s\n\n", strings.Repeat("  ", indents), error.ItString)
      s += fmt.Sprintf("%s\n\t%s\n", error.String, error.ErrorLine)
    }

    s += resetColors
  } else {
    s += greenColor
    s += fmt.Sprintf("All tests passed. %d examples. 0 failures.\n", testingExamples)
    s += resetColors
  }

  return s, ok
}

func PrintSpecReport() {
  report, ok := BuildSpecReport()

  stream := specReportStream
  if stream == nil {
    stream = os.Stdout
  }
  fmt.Fprintf(stream, report)

  if !ok && !debugTesting {
    os.Exit(1)
  }

  testingContexts = testingContexts[0:0]
  testingErrors = testingErrors[0:0]
  testingExamples = 0
}
