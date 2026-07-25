package util

import (
	"crypto/rand"
	"math/big"
)

const alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789"

func RandomCode(n int) (string, error) {
	if n <= 0 {
		n = 6
	}
	b := make([]byte, n)
	for i := 0; i < n; i++ {
		v, err := rand.Int(rand.Reader, big.NewInt(int64(len(alphabet))))
		if err != nil {
			return "", err
		}
		b[i] = alphabet[v.Int64()]
	}
	return string(b), nil
}

func RandomString(n int) (string, error) {
	const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
	b := make([]byte, n)
	for i := 0; i < n; i++ {
		v, err := rand.Int(rand.Reader, big.NewInt(int64(len(chars))))
		if err != nil {
			return "", err
		}
		b[i] = chars[v.Int64()]
	}
	return string(b), nil
}
