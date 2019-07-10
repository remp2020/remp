package simple9

import "fmt"

type packing struct {
	n, bit int
	unpack func(uint32, []uint32)
	pack   func([]uint32) uint32
}

var selector [9]packing = [9]packing{
	packing{28, 1, unpack28, pack28},
	packing{14, 2, unpack14, pack14},
	packing{9, 3, unpack9, pack9},
	packing{7, 4, unpack7, pack7},
	packing{5, 5, unpack5, pack5},
	packing{4, 7, unpack4, pack4},
	packing{3, 9, unpack3, pack3},
	packing{2, 14, unpack2, pack2},
	packing{1, 28, unpack1, pack1},
}

// EncodeAll returns a packed slice of the values from src.  It a value is over
// 1 << 28, an error is returned.
func EncodeAll(src []uint32) ([]uint32, error) {
	i := 0
	dst := make([]uint32, len(src))
	j := 0

	for {
		if i >= len(src) {
			break
		}
		remaining := src[i:]

		if canPack(remaining, 1, 28) {
			dst[j] = pack28(src[i : i+28])
			i += 28
		} else if canPack(remaining, 2, 14) {
			dst[j] = pack14(src[i : i+14])
			i += 14
		} else if canPack(remaining, 3, 9) {
			dst[j] = pack9(src[i : i+9])
			i += 9
		} else if canPack(remaining, 4, 7) {
			dst[j] = pack7(src[i : i+7])
			i += 7
		} else if canPack(remaining, 5, 5) {
			dst[j] = pack5(src[i : i+5])
			i += 5
		} else if canPack(remaining, 7, 4) {
			dst[j] = pack4(src[i : i+4])
			i += 4
		} else if canPack(remaining, 9, 3) {
			dst[j] = pack3(src[i : i+3])
			i += 3
		} else if canPack(remaining, 14, 2) {
			dst[j] = pack2(src[i : i+2])
			i += 2
		} else if canPack(remaining, 28, 1) {
			dst[j] = pack1(src[i : i+1])
			i += 1
		} else {
			return nil, fmt.Errorf("value out of bounds")
		}
		j += 1
	}
	return dst[:j], nil
}

// DecodeAll returns the uncompressed values from in
func DecodeAll(dst, src []uint32) error {
	j := 0
	for _, v := range src {
		sel := v >> 28
		if sel >= 9 {
			return fmt.Errorf("invalid selector value: %b", sel)
		}
		selector[sel].unpack(v, dst[j:])
		j += selector[sel].n
	}
	return nil
}

// returns true if the next n values in in can be packed using bits bits
// per value.
func canPack(src []uint32, bits, n int) bool {
	if len(src) < n {
		return false
	}

	end := len(src)
	if n < end {
		end = n
	}

	max := uint32((2 << uint32(bits-1)) - 1)
	for i := 0; i < end; i++ {
		if src[i] > max {
			return false
		}
	}

	return true
}

// pack28 packs 28 values from in using 1 bit each
func pack28(in []uint32) uint32 {
	return in[0] |
		in[1]<<1 |
		in[2]<<2 |
		in[3]<<3 |
		in[4]<<4 |
		in[5]<<5 |
		in[6]<<6 |
		in[7]<<7 |
		in[8]<<8 |
		in[9]<<9 |
		in[10]<<10 |
		in[11]<<11 |
		in[12]<<12 |
		in[13]<<13 |
		in[14]<<14 |
		in[15]<<15 |
		in[16]<<16 |
		in[17]<<17 |
		in[18]<<18 |
		in[19]<<19 |
		in[20]<<20 |
		in[21]<<21 |
		in[22]<<22 |
		in[23]<<23 |
		in[24]<<24 |
		in[25]<<25 |
		in[26]<<26 |
		in[27]<<27
}

// pack14 packs 14 values from in using 2 bits each
func pack14(in []uint32) uint32 {
	return 1<<28 |
		in[0] |
		in[1]<<2 |
		in[2]<<4 |
		in[3]<<6 |
		in[4]<<8 |
		in[5]<<10 |
		in[6]<<12 |
		in[7]<<14 |
		in[8]<<16 |
		in[9]<<18 |
		in[10]<<20 |
		in[11]<<22 |
		in[12]<<24 |
		in[13]<<26
}

// pack9 packs 9 values from in using 3 bits each
func pack9(in []uint32) uint32 {
	return 2<<28 |
		in[0]<<1 |
		in[1]<<4 |
		in[2]<<7 |
		in[3]<<10 |
		in[4]<<13 |
		in[5]<<16 |
		in[6]<<19 |
		in[7]<<22 |
		in[8]<<25
}

// pack7 packs 7 values from in using 4 bits each
func pack7(in []uint32) uint32 {
	return 3<<28 |
		in[0] |
		in[1]<<4 |
		in[2]<<8 |
		in[3]<<12 |
		in[4]<<16 |
		in[5]<<20 |
		in[6]<<24
}

// pack5 packs 5 values from in using 5 bits each
func pack5(in []uint32) uint32 {
	return 4<<28 |
		in[0]<<3 |
		in[1]<<8 |
		in[2]<<13 |
		in[3]<<18 |
		in[4]<<23
}

// pack4 packs 4 values from in using 7 bits each
func pack4(in []uint32) uint32 {
	return 5<<28 |
		in[0] |
		in[1]<<7 |
		in[2]<<14 |
		in[3]<<21
}

// pack3 packs 3 values from in using 9 bits each
func pack3(in []uint32) uint32 {
	return 6<<28 |
		in[0]<<1 |
		in[1]<<10 |
		in[2]<<19
}

// pack2 packs 2 values from in using 14 bits each
func pack2(in []uint32) uint32 {
	return 7<<28 |
		in[0] |
		in[1]<<14
}

// pack1 packs 1 value from in using 28 bits
func pack1(in []uint32) uint32 {
	return 8<<28 |
		in[0]
}

func unpack28(in uint32, out []uint32) {
	out[0] = (in >> 27) & 1
	out[1] = (in >> 26) & 1
	out[2] = (in >> 25) & 1
	out[3] = (in >> 24) & 1
	out[4] = (in >> 23) & 1
	out[5] = (in >> 22) & 1
	out[6] = (in >> 21) & 1
	out[7] = (in >> 20) & 1
	out[8] = (in >> 19) & 1
	out[9] = (in >> 18) & 1
	out[10] = (in >> 17) & 1
	out[11] = (in >> 16) & 1
	out[12] = (in >> 15) & 1
	out[13] = (in >> 14) & 1
	out[14] = (in >> 13) & 1
	out[15] = (in >> 12) & 1
	out[16] = (in >> 11) & 1
	out[17] = (in >> 10) & 1
	out[18] = (in >> 9) & 1
	out[19] = (in >> 8) & 1
	out[20] = (in >> 7) & 1
	out[21] = (in >> 6) & 1
	out[22] = (in >> 5) & 1
	out[23] = (in >> 4) & 1
	out[24] = (in >> 3) & 1
	out[25] = (in >> 2) & 1
	out[26] = (in >> 1) & 1
	out[27] = in & 1
}

func unpack14(in uint32, out []uint32) {
	out[0] = (in >> 26) & 3
	out[1] = (in >> 24) & 3
	out[2] = (in >> 22) & 3
	out[3] = (in >> 20) & 3
	out[4] = (in >> 18) & 3
	out[5] = (in >> 16) & 3
	out[6] = (in >> 14) & 3
	out[7] = (in >> 12) & 3
	out[8] = (in >> 10) & 3
	out[9] = (in >> 8) & 3
	out[10] = (in >> 6) & 3
	out[11] = (in >> 4) & 3
	out[12] = (in >> 2) & 3
	out[13] = in & 3
}

func unpack9(in uint32, out []uint32) {
	out[0] = (in >> 25) & 7
	out[1] = (in >> 22) & 7
	out[2] = (in >> 19) & 7
	out[3] = (in >> 16) & 7
	out[4] = (in >> 13) & 7
	out[5] = (in >> 10) & 7
	out[6] = (in >> 7) & 7
	out[7] = (in >> 4) & 7
	out[8] = (in >> 1) & 7
}

func unpack7(in uint32, out []uint32) {
	out[0] = (in >> 24) & 15
	out[1] = (in >> 20) & 15
	out[2] = (in >> 16) & 15
	out[3] = (in >> 12) & 15
	out[4] = (in >> 8) & 15
	out[5] = (in >> 4) & 15
	out[6] = in & 15
}

func unpack5(in uint32, out []uint32) {
	out[0] = (in >> 23) & 31
	out[1] = (in >> 18) & 31
	out[2] = (in >> 13) & 31
	out[3] = (in >> 8) & 31
	out[4] = (in >> 3) & 31
}

func unpack4(in uint32, out []uint32) {
	out[0] = (in >> 21) & 63
	out[1] = (in >> 14) & 63
	out[2] = (in >> 7) & 63
	out[3] = in & 63
}

func unpack3(in uint32, out []uint32) {
	out[0] = (in >> 19) & 511
	out[1] = (in >> 10) & 511
	out[2] = (in >> 1) & 511
}

func unpack2(in uint32, out []uint32) {
	out[0] = (in >> 14) & 16383
	out[1] = in & 16383
}

func unpack1(in uint32, out []uint32) {
	out[0] = in & 268435455
}
