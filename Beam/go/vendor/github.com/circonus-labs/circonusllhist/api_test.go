package circonusllhist_test

import (
	"math"
	"math/rand"
	"testing"
	"time"

	hist "github.com/circonus-labs/circonusllhist"
)

func fuzzy_equals(expected, actual float64) bool {
	delta := math.Abs(expected / 100000.0)
	if actual >= expected-delta && actual <= expected+delta {
		return true
	}
	return false
}

var s1 = []float64{0.123, 0, 0.43, 0.41, 0.415, 0.2201, 0.3201, 0.125, 0.13}

func TestDecStrings(t *testing.T) {
	h := hist.New()
	for _, sample := range s1 {
		h.RecordValue(sample)
	}
	out := h.DecStrings()
	expect := []string{"H[0.0e+00]=1", "H[1.2e-01]=2", "H[1.3e-01]=1",
		"H[2.2e-01]=1", "H[3.2e-01]=1", "H[4.1e-01]=2",
		"H[4.3e-01]=1"}
	for i, str := range expect {
		if str != out[i] {
			t.Errorf("DecString '%v' != '%v'", out[i], str)
		}
	}
}

func TestNewFromStrings(t *testing.T) {
	strings := []string{"H[0.0e+00]=1", "H[1.2e-01]=2", "H[1.3e-01]=1",
		"H[2.2e-01]=1", "H[3.2e-01]=1", "H[4.1e-01]=2", "H[4.3e-01]=1"}

	// hist of single set of strings
	singleHist, err := hist.NewFromStrings(strings, false)
	if err != nil {
		t.Errorf("error creating hist from strings '%v'", err)
	}

	// hist of multiple sets of strings
	strings = append(strings, strings...)
	doubleHist, err := hist.NewFromStrings(strings, false)
	if err != nil {
		t.Errorf("error creating hist from strings '%v'", err)
	}

	// sanity check the sums are doubled
	if singleHist.ApproxSum()*2 != doubleHist.ApproxSum() {
		t.Error("aggregate histogram approxSum failure")
	}

	if singleHist.Equals(doubleHist) {
		t.Error("histograms should not be equal")
	}
}

func TestMean(t *testing.T) {
	h := hist.New()
	for _, sample := range s1 {
		h.RecordValue(sample)
	}
	mean := h.ApproxMean()
	if !fuzzy_equals(0.2444444444, mean) {
		t.Errorf("mean() -> %v != %v", mean, 0.24444)
	}
}

func helpQTest(t *testing.T, vals, qin, qexpect []float64) {
	h := hist.New()
	for _, sample := range vals {
		h.RecordValue(sample)
	}
	qout, _ := h.ApproxQuantile(qin)
	if len(qout) != len(qexpect) {
		t.Errorf("wrong number of quantiles")
	}
	for i, q := range qout {
		if !fuzzy_equals(qexpect[i], q) {
			t.Errorf("q(%v) -> %v != %v", qin[i], q, qexpect[i])
		}
	}
}

func TestQuantiles(t *testing.T) {
	helpQTest(t, []float64{1}, []float64{0, 0.25, 0.5, 1}, []float64{1, 1.025, 1.05, 1.1})
	helpQTest(t, s1, []float64{0, 0.95, 0.99, 1.0}, []float64{0, 0.4355, 0.4391, 0.44})
	helpQTest(t, []float64{1.0, 2.0}, []float64{0.5}, []float64{1.1})
	helpQTest(t, []float64{1.0, 1e200}, []float64{0, 1}, []float64{1.0, 1.1})
	helpQTest(t, []float64{1e200, 1e200, 1e200, 0, 0, 1e-20, 1e-20, 1e-20, 1e-10}, []float64{0, 1},
		[]float64{0, 1.1e-10})
	helpQTest(t, []float64{0, 1}, []float64{0, 0.1}, []float64{0, 0})
}

func BenchmarkHistogramRecordValue(b *testing.B) {
	h := hist.NewNoLocks()
	for i := 0; i < b.N; i++ {
		h.RecordValue(float64(i % 1000))
	}
	b.ReportAllocs()
}

func BenchmarkHistogramTypical(b *testing.B) {
	h := hist.NewNoLocks()
	for i := 0; i < b.N; i++ {
		h.RecordValue(float64(i % 1000))
	}
	b.ReportAllocs()
}

func BenchmarkHistogramRecordIntScale(b *testing.B) {
	h := hist.NewNoLocks()
	for i := 0; i < b.N; i++ {
		h.RecordIntScale(int64(i%90+10), (i/1000)%3)
	}
	b.ReportAllocs()
}

func BenchmarkHistogramTypicalIntScale(b *testing.B) {
	h := hist.NewNoLocks()
	for i := 0; i < b.N; i++ {
		h.RecordIntScale(int64(i%90+10), (i/1000)%3)
	}
	b.ReportAllocs()
}

func BenchmarkNew(b *testing.B) {
	b.ReportAllocs()

	for i := 0; i < b.N; i++ {
		hist.New()
	}
}

func TestCompare(t *testing.T) {
	// var h1, h2 *Bin
}

func TestConcurrent(t *testing.T) {
	h := hist.New()
	for r := 0; r < 100; r++ {
		go func() {
			for j := 0; j < 100; j++ {
				for i := 50; i < 100; i++ {
					if err := h.RecordValue(float64(i)); err != nil {
						t.Fatal(err)
					}
				}
			}
		}()
	}
}

func TestRang(t *testing.T) {
	h1 := hist.New()
	src := rand.NewSource(time.Now().UnixNano())
	rnd := rand.New(src)
	for i := 0; i < 1000000; i++ {
		h1.RecordValue(rnd.Float64() * 10)
	}
}

func TestEquals(t *testing.T) {
	h1 := hist.New()
	for i := 0; i < 1000000; i++ {
		if err := h1.RecordValue(float64(i)); err != nil {
			t.Fatal(err)
		}
	}

	h2 := hist.New()
	for i := 0; i < 10000; i++ {
		if err := h1.RecordValue(float64(i)); err != nil {
			t.Fatal(err)
		}
	}

	if h1.Equals(h2) {
		t.Error("Expected Histograms to not be equivalent")
	}

	h1.Reset()
	h2.Reset()

	if !h1.Equals(h2) {
		t.Error("Expected Histograms to be equivalent")
	}
}

func TestMinMaxMean(t *testing.T) {
	const (
		minVal = 0
		maxVal = 1000000
	)

	h := hist.New()
	for i := minVal; i < maxVal; i++ {
		if err := h.RecordValue(float64(i)); err != nil {
			t.Fatal(err)
		}
	}

	if h.Min() > minVal {
		t.Error("incorrect min value")
	}

	if h.Max() < maxVal {
		t.Error("incorrect max value")
	}

	round := func(val float64) int {
		if val < 0 {
			return int(val - 0.5)
		}
		return int(val + 0.5)
	}

	if round(h.Mean()) != round(maxVal/2) {
		t.Errorf("incorrect mean value")
	}
}

func TestCopy(t *testing.T) {
	h1 := hist.New()
	for i := 0; i < 1000000; i++ {
		if err := h1.RecordValue(float64(i)); err != nil {
			t.Fatal(err)
		}
	}

	h2 := h1.Copy()
	if !h2.Equals(h1) {
		t.Errorf("expected copy: %v to equal original: %v", h2, h1)
	}
}

func TestFullReset(t *testing.T) {
	h1 := hist.New()
	for i := 0; i < 1000000; i++ {
		if err := h1.RecordValue(float64(i)); err != nil {
			t.Fatal(err)
		}
	}

	h1.Reset()
	h2 := hist.New()
	if !h2.Equals(h1) {
		t.Errorf("expected reset value: %v to equal new value: %v", h1, h2)
	}
}
