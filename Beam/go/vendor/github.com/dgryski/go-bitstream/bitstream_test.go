package bitstream

import (
	"bytes"
	"fmt"
	"io"
	"strings"
	"testing"
)

func TestBitStreamEOF(t *testing.T) {

	br := NewReader(strings.NewReader("0"))

	b, err := br.ReadByte()
	if b != '0' {
		t.Error("ReadByte didn't return first byte")
	}

	b, err = br.ReadByte()
	if err != io.EOF {
		t.Error("ReadByte on empty string didn't return EOF")
	}

	// 0 = 0b00110000
	br = NewReader(strings.NewReader("0"))

	buf := bytes.NewBuffer(nil)
	bw := NewWriter(buf)

	for i := 0; i < 4; i++ {
		bit, err := br.ReadBit()
		if err == io.EOF {
			break
		}
		if err != nil {
			t.Error("GetBit returned error err=", err.Error())
			return
		}
		err = bw.WriteBit(bit)
		if err != nil {
			t.Errorf("unexpected writer error")
		}
	}

	bw.Flush(One)

	err = bw.WriteByte(0xAA)
	if err != nil {
		t.Error("unable to WriteByte")
	}

	c := buf.Bytes()

	if len(c) != 2 || c[1] != 0xAA || c[0] != 0x3f {
		t.Error("bad return from 4 read bytes")
	}

	_, err = NewReader(strings.NewReader("")).ReadBit()
	if err != io.EOF {
		t.Error("ReadBit on empty string didn't return EOF")
	}

}

// reader that returns a single byte with EOF
type eofReader struct {
	readCalled bool
}

func (e *eofReader) Read(p []byte) (int, error) {
	if e.readCalled {
		return 0, io.EOF
	}
	e.readCalled = true
	p[0] = 'A'
	return 1, io.EOF
}

func TestBitStreamReadEOF(t *testing.T) {

	br := NewReader(&eofReader{})

	r, _ := br.ReadBits(8)

	if r != 'A' {
		t.Errorf("failed to read single byte with EOF: %08b", r)
	}

}

func TestBitStream(t *testing.T) {

	buf := bytes.NewBuffer(nil)
	br := NewReader(strings.NewReader("hello"))
	bw := NewWriter(buf)

	for {
		bit, err := br.ReadBit()
		if err == io.EOF {
			break
		}
		if err != nil {
			t.Error("GetBit returned error err=", err.Error())
			return
		}
		err = bw.WriteBit(bit)
		if err != nil {
			t.Errorf("unexpected writer error")
		}
	}

	s := buf.String()

	if s != "hello" {
		t.Error("expected 'hello', got=", []byte(s))
	}
}

func TestByteStream(t *testing.T) {

	buf := bytes.NewBuffer(nil)
	br := NewReader(strings.NewReader("hello"))
	bw := NewWriter(buf)

	for i := 0; i < 3; i++ {
		bit, err := br.ReadBit()
		if err == io.EOF {
			break
		}
		if err != nil {
			t.Error("GetBit returned error err=", err.Error())
			return
		}
		err = bw.WriteBit(bit)
		if err != nil {
			t.Errorf("unexpected writer error")
		}
	}

	for i := 0; i < 3; i++ {
		byt, err := br.ReadByte()
		if err == io.EOF {
			break
		}
		if err != nil {
			t.Error("GetByte returned error err=", err.Error())
			return
		}
		bw.WriteByte(byt)
	}

	u, err := br.ReadBits(13)

	if err != nil {
		t.Error("ReadBits returned error err=", err.Error())
		return
	}

	err = bw.WriteBits(u, 13)
	if err != nil {
		t.Errorf("unexpected writer error")
	}

	err = bw.WriteBits(('!'<<12)|('.'<<4)|0x02, 20)
	if err != nil {
		t.Errorf("unexpected writer error")
	}
	// 0x2f == '/'
	bw.Flush(One)

	s := buf.String()

	if s != "hello!./" {
		t.Errorf("expected 'hello!./', got=%x", []byte(s))
	}
}

var myError error = fmt.Errorf("my error")

type badWriter struct{}

func (w *badWriter) Write(p []byte) (n int, err error) {
	return 0, myError
}
func TestErrorPropagation(t *testing.T) {
	// check WriteBit
	w := &badWriter{}
	bw := NewWriter(w)
	for i := 0; i < 7; i++ {
		err := bw.WriteBit(One)
		if err != nil {
			t.Errorf("unexpected error during buffered write operation")
		}
	}
	err := bw.WriteBit(One)
	if err != myError {
		t.Errorf("failed to propagate error")
	}

	// check WriteBits
	w = &badWriter{}
	bw = NewWriter(w)
	err = bw.WriteBits(256, 8)
	if err != myError {
		t.Errorf("failed to propagate error")
	}
}

func TestReset(t *testing.T) {
	r := NewReader(strings.NewReader("abc"))
	b, _ := r.ReadBits(8)
	if b != 'a' {
		t.Errorf("expected 'a', got=%08b", b)
	}

	r.Reset(strings.NewReader("def"))
	b, _ = r.ReadBits(8)

	if b != 'd' {
		t.Errorf("expected 'd', got=%08b", b)
	}

	firstWriter := bytes.NewBuffer(nil)
	w := NewWriter(firstWriter)
	w.WriteBits(97, 8)

	secondWriter := bytes.NewBuffer(nil)
	w.Reset(secondWriter)
	w.WriteBits(98, 8)

	if firstWriter.String() != "a" {
		t.Errorf("expected 'a', got=%x", firstWriter.Bytes())
	}

	if secondWriter.String() != "b" {
		t.Errorf("expected 'b', got=%x", secondWriter.Bytes())
	}
}
