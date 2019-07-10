package simple9

import "testing"

// Tests that a since canPack returns whether a uint32 is packaable int the given
// number of bits (up to 28)
func Test_CanPack_Single(t *testing.T) {
	minBits := 1
	for value := 1; value < 2<<28; value = value << 1 {

		// test that the value cannot be packed in less than the required bits
		for i := 1; i < minBits; i++ {
			if got := canPack([]uint32{uint32(value)}, i, 1); got {
				t.Fatalf("canPack(%d, %d, 1) mismatch, exp false, got %v", value, i, got)
			}
		}

		// test that the value can be packed in the min and up to max bits required
		for i := minBits; i < 29; i++ {
			if got := canPack([]uint32{uint32(value)}, i, 1); !got {
				t.Fatalf("canPack(%d, %d, 1) mismatch, exp true, got %v", value, i, got)
			}
		}
		minBits = minBits + 1
	}
}

func Test_1bit(t *testing.T) {
	in := make([]uint32, 28)
	for i := 0; i < 28; i++ {
		in[i] = 1
	}
	encoded, _ := EncodeAll(in)
	if exp, got := len(in)/28, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := 28, len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < 28; i++ {
		if exp := uint32(1); decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_1bit_Multiple(t *testing.T) {
	in := make([]uint32, 28*2)
	for i := 0; i < 28*2; i++ {
		in[i] = 1
	}
	encoded, _ := EncodeAll(in)
	if exp, got := len(in)/28, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}

}

func Test_2bit(t *testing.T) {
	values := 14
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 3
	}
	encoded, _ := EncodeAll(in)
	if exp, got := 1, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_3bit(t *testing.T) {
	values := 9
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 7
	}
	encoded, _ := EncodeAll(in)
	if exp, got := 1, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_4bit(t *testing.T) {
	values := 7
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 15
	}
	encoded, _ := EncodeAll(in)
	if exp, got := 1, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_5bit(t *testing.T) {
	values := 5
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 31
	}
	encoded, _ := EncodeAll(in)
	if exp, got := 1, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_7bit(t *testing.T) {
	values := 4
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 63
	}
	encoded, _ := EncodeAll(in)
	if exp, got := 1, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_9bit(t *testing.T) {
	values := 3
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 511
	}
	encoded, _ := EncodeAll(in)
	if exp, got := 1, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_14bit(t *testing.T) {
	values := 2
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 16383
	}
	encoded, _ := EncodeAll(in)

	if exp, got := 1, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_28bit(t *testing.T) {
	values := 1
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 268435455
	}
	encoded, _ := EncodeAll(in)

	if exp, got := 1, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func Test_TooBig(t *testing.T) {
	values := 1
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 2<<31 - 1
	}
	_, err := EncodeAll(in)
	if err == nil {
		t.Fatalf("expected error, got nil")
	}
}

func Test_FewValues(t *testing.T) {
	values := 7
	in := make([]uint32, values)
	for i := 0; i < values; i++ {
		in[i] = 15
	}
	in = in[:len(in)-1]
	encoded, _ := EncodeAll(in)

	if exp, got := 2, len(encoded); got != exp {
		t.Fatalf("Encode len mismatch: exp %v, got %v", exp, got)
	}

	decoded := make([]uint32, len(in))
	_ = DecodeAll(decoded, encoded)
	if exp, got := len(in), len(decoded); got != exp {
		t.Fatalf("Decode len mismatch: exp %v, got %v", exp, got)
	}

	for i := 0; i < len(in); i++ {
		if exp := in[i]; decoded[i] != exp {
			t.Fatalf("Decoded[%d] != %v, got %v", i, exp, decoded[i])
		}
	}
}

func BenchmarkEncodeAll(b *testing.B) {
	total := 0
	x := make([]uint32, 1024)
	for i := 0; i < len(x); i++ {
		x[i] = uint32(15)
	}

	for i := 0; i < b.N; i++ {
		EncodeAll(x)
		b.SetBytes(int64(len(x) * 4))
		total += len(x)
	}
}

func BenchmarkDecodeAll(b *testing.B) {
	total := 0

	x := make([]uint32, 1024)
	for i := 0; i < len(x); i++ {
		x[i] = uint32(15)
	}
	y, _ := EncodeAll(x)

	decoded := make([]uint32, len(x))

	b.ResetTimer()

	for i := 0; i < b.N; i++ {
		_ = DecodeAll(decoded, y)
		b.SetBytes(int64(len(decoded) * 4))
		total += len(decoded)
	}
}
