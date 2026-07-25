package service

import (
	"encoding/json"
	"sync"
	"time"

	"github.com/chauncey/shorturl/server/internal/model"
	"gorm.io/gorm"
)

// GuestLimits are anonymous-create quotas (stored in app_configs).
// 0 on a field means that check is disabled.
type GuestLimits struct {
	MaxCreatePerIP24h int `json:"max_create_per_ip_24h"` // default 10
	MaxActivePerIP    int `json:"max_active_per_ip"`     // default 10
}

func DefaultGuestLimits() GuestLimits {
	return GuestLimits{MaxCreatePerIP24h: 10, MaxActivePerIP: 10}
}

type GuestLimitsService struct {
	db    *gorm.DB
	mu    sync.RWMutex
	cache GuestLimits
}

func NewGuestLimitsService(db *gorm.DB) (*GuestLimitsService, error) {
	s := &GuestLimitsService{db: db}
	if err := s.ensureAndLoad(); err != nil {
		return nil, err
	}
	return s, nil
}

func (s *GuestLimitsService) ensureAndLoad() error {
	var row model.AppConfig
	err := s.db.Where("key = ?", model.ConfigKeyGuestLimits).First(&row).Error
	if err == gorm.ErrRecordNotFound {
		cfg := DefaultGuestLimits()
		raw, _ := json.Marshal(cfg)
		now := time.Now()
		row = model.AppConfig{
			Key: model.ConfigKeyGuestLimits, Value: string(raw),
			CreatedAt: &now, UpdatedAt: &now,
		}
		if err := s.db.Create(&row).Error; err != nil {
			return err
		}
		s.mu.Lock()
		s.cache = cfg
		s.mu.Unlock()
		return nil
	}
	if err != nil {
		return err
	}
	cfg := DefaultGuestLimits()
	_ = json.Unmarshal([]byte(row.Value), &cfg)
	s.normalize(&cfg)
	s.mu.Lock()
	s.cache = cfg
	s.mu.Unlock()
	return nil
}

func (s *GuestLimitsService) normalize(cfg *GuestLimits) {
	if cfg.MaxCreatePerIP24h < 0 {
		cfg.MaxCreatePerIP24h = 0
	}
	if cfg.MaxActivePerIP < 0 {
		cfg.MaxActivePerIP = 0
	}
}

func (s *GuestLimitsService) Get() GuestLimits {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.cache
}

func (s *GuestLimitsService) Save(cfg GuestLimits) error {
	s.normalize(&cfg)
	raw, err := json.Marshal(cfg)
	if err != nil {
		return err
	}
	now := time.Now()
	var row model.AppConfig
	err = s.db.Where("key = ?", model.ConfigKeyGuestLimits).First(&row).Error
	if err == gorm.ErrRecordNotFound {
		row = model.AppConfig{
			Key: model.ConfigKeyGuestLimits, Value: string(raw),
			CreatedAt: &now, UpdatedAt: &now,
		}
		if err := s.db.Create(&row).Error; err != nil {
			return err
		}
	} else if err != nil {
		return err
	} else {
		row.Value = string(raw)
		row.UpdatedAt = &now
		if err := s.db.Save(&row).Error; err != nil {
			return err
		}
	}
	s.mu.Lock()
	s.cache = cfg
	s.mu.Unlock()
	return nil
}
