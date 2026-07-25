package service

import (
	"sync"
	"time"
)

// KV is a process-local key/value store with TTL.
// Swap the implementation for Redis later without changing callers.
type KV struct {
	mu   sync.Mutex
	data map[string]kvEntry
}

type kvEntry struct {
	value     []byte
	expiresAt time.Time
}

func NewKV() *KV {
	k := &KV{data: make(map[string]kvEntry)}
	go k.loop()
	return k
}

func (k *KV) loop() {
	t := time.NewTicker(time.Minute)
	defer t.Stop()
	for range t.C {
		k.mu.Lock()
		now := time.Now()
		for key, e := range k.data {
			if now.After(e.expiresAt) {
				delete(k.data, key)
			}
		}
		k.mu.Unlock()
	}
}

func (k *KV) Set(key string, value []byte, ttl time.Duration) {
	k.mu.Lock()
	defer k.mu.Unlock()
	k.data[key] = kvEntry{value: append([]byte(nil), value...), expiresAt: time.Now().Add(ttl)}
}

func (k *KV) Get(key string) ([]byte, bool) {
	k.mu.Lock()
	defer k.mu.Unlock()
	e, ok := k.data[key]
	if !ok {
		return nil, false
	}
	if time.Now().After(e.expiresAt) {
		delete(k.data, key)
		return nil, false
	}
	out := append([]byte(nil), e.value...)
	return out, true
}

// Take returns the value and deletes the key (one-shot).
func (k *KV) Take(key string) ([]byte, bool) {
	k.mu.Lock()
	defer k.mu.Unlock()
	e, ok := k.data[key]
	if !ok {
		return nil, false
	}
	delete(k.data, key)
	if time.Now().After(e.expiresAt) {
		return nil, false
	}
	return append([]byte(nil), e.value...), true
}

func (k *KV) Delete(key string) {
	k.mu.Lock()
	defer k.mu.Unlock()
	delete(k.data, key)
}
