# BDD testing for Go

It's pretty cool.

## Installation

	make test # assurance that it works
	make install # installs to your $GOROOT

## Usage

Create a gotest-style file (ie, *_test.go) containing the following:

	package my_cool_app
	
	import . "gobdd"

	func init() {
	  defer PrintSpecReport()
  
	  type MyGreatTestType struct {
	    Name string
	    Age int
	  }
  
	  MyNil := func() *MyGreatTestType {
	    return nil
	  }
  
	  MyNonNil := func() *MyGreatTestType {
	    return &MyGreatTestType{}
	  }
  
	  var anObject *MyGreatTestType
  
	  Describe("matchers", func() {
    
	    BeforeEach(func() {
	      // this is run at this level and every describe within it, however deeply nested
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
	        Expect(func() {}, ToNotPanic)
	      })
      
	    })
    
	  })
	}

Next, add the following lines to *only one* of your *_test.go files:

	import "testing"
	func TestEverything(t *testing.T) {}

Then, to run the tests, use either of these:

	make test
	gotest

(It's just the same as running tests using the built-in testing framework.)

## Writing your own matchers

	func ToBeTruthy(obj interface{}) (string, bool) {
	  if obj != true {
	    return fmt.Sprintf(
			"expected: true\n"+
			"     got: %v\n", obj), false
	  }
	  return "", true
	}

Your custom matchers may take any number of arguments of any type your heart so desires. For example:

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

will then work with `Expect("foo", ToBeInside, []string{"foo"})`

## License

Public domain
