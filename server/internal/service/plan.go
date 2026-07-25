package service

import (
	"encoding/json"
	"sync"
	"time"

	"github.com/chauncey/shorturl/server/internal/model"
	"gorm.io/gorm"
)

// PlanFeatures are sellable membership entitlements.
type PlanFeatures struct {
	CustomCode       bool `json:"custom_code"`
	EditTarget       bool `json:"edit_target"`
	MaxLinks         int  `json:"max_links"`          // -1 = unlimited
	MaxExpireDays    int  `json:"max_expire_days"`    // max selectable expiry; 0 with never=false means must expire
	AllowNeverExpire bool `json:"allow_never_expire"` // expires_at null
}

// Plan is one 权益套餐 (membership plan) sold to users.
type Plan struct {
	ID          string       `json:"id"`
	Name        string       `json:"name"`
	NameEN      string       `json:"name_en"`
	NameJA      string       `json:"name_ja"`
	Description string       `json:"description"`
	Sort        int          `json:"sort"`
	Features    PlanFeatures `json:"features"`
}

// PlansConfig is the whole JSON document stored in app_configs.
type PlansConfig struct {
	DefaultPlanID string `json:"default_plan_id"`
	Plans         []Plan `json:"plans"`
}

type PlanService struct {
	db    *gorm.DB
	mu    sync.RWMutex
	cache PlansConfig
}

func NewPlanService(db *gorm.DB) (*PlanService, error) {
	s := &PlanService{db: db}
	if err := s.ensureAndLoad(); err != nil {
		return nil, err
	}
	return s, nil
}

func DefaultPlansConfig() PlansConfig {
	return PlansConfig{
		DefaultPlanID: "free",
		Plans: []Plan{
			{
				ID: "free", Name: "免费版", NameEN: "Free", NameJA: "無料プラン",
				Description: "入门体验，适合偶尔分享", Sort: 10,
				Features: PlanFeatures{
					CustomCode: false, EditTarget: false,
					MaxLinks: 50, MaxExpireDays: 3, AllowNeverExpire: false,
				},
			},
			{
				ID: "pro", Name: "专业版", NameEN: "Pro", NameJA: "プロ",
				Description: "自定义短码、修改目标、更长有效期", Sort: 20,
				Features: PlanFeatures{
					CustomCode: true, EditTarget: true,
					MaxLinks: 100, MaxExpireDays: 7, AllowNeverExpire: true,
				},
			},
			{
				ID: "flagship", Name: "旗舰版", NameEN: "Flagship", NameJA: "フラッグシップ",
				Description: "不限创建量，全权益放开", Sort: 30,
				Features: PlanFeatures{
					CustomCode: true, EditTarget: true,
					MaxLinks: -1, MaxExpireDays: 365, AllowNeverExpire: true,
				},
			},
		},
	}
}

func (s *PlanService) ensureAndLoad() error {
	var row model.AppConfig
	err := s.db.Where("key = ?", model.ConfigKeyPlans).First(&row).Error
	if err == gorm.ErrRecordNotFound {
		cfg := DefaultPlansConfig()
		raw, _ := json.Marshal(cfg)
		now := time.Now()
		row = model.AppConfig{
			Key: model.ConfigKeyPlans, Value: string(raw),
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
	var cfg PlansConfig
	if err := json.Unmarshal([]byte(row.Value), &cfg); err != nil || len(cfg.Plans) == 0 {
		cfg = DefaultPlansConfig()
	}
	if cfg.DefaultPlanID == "" {
		cfg.DefaultPlanID = "free"
	}
	s.mu.Lock()
	s.cache = cfg
	s.mu.Unlock()
	return nil
}

func (s *PlanService) Get() PlansConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return clonePlans(s.cache)
}

func (s *PlanService) GetPlan(id string) (Plan, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	for _, p := range s.cache.Plans {
		if p.ID == id {
			return p, true
		}
	}
	// fallback default
	for _, p := range s.cache.Plans {
		if p.ID == s.cache.DefaultPlanID {
			return p, true
		}
	}
	if len(s.cache.Plans) > 0 {
		return s.cache.Plans[0], true
	}
	return Plan{}, false
}

func (s *PlanService) FeaturesFor(planID string) PlanFeatures {
	if p, ok := s.GetPlan(planID); ok {
		return p.Features
	}
	return DefaultPlansConfig().Plans[0].Features
}

func (s *PlanService) Save(cfg PlansConfig) error {
	if cfg.DefaultPlanID == "" {
		cfg.DefaultPlanID = "free"
	}
	raw, err := json.Marshal(cfg)
	if err != nil {
		return err
	}
	now := time.Now()
	var row model.AppConfig
	err = s.db.Where("key = ?", model.ConfigKeyPlans).First(&row).Error
	if err == gorm.ErrRecordNotFound {
		row = model.AppConfig{Key: model.ConfigKeyPlans, Value: string(raw), CreatedAt: &now, UpdatedAt: &now}
		if err := s.db.Create(&row).Error; err != nil {
			return err
		}
	} else if err != nil {
		return err
	} else {
		if err := s.db.Model(&row).Updates(map[string]interface{}{
			"value": string(raw), "updated_at": &now,
		}).Error; err != nil {
			return err
		}
	}
	s.mu.Lock()
	s.cache = cfg
	s.mu.Unlock()
	return nil
}

func (s *PlanService) Reload() error {
	return s.ensureAndLoad()
}

func clonePlans(in PlansConfig) PlansConfig {
	out := in
	out.Plans = append([]Plan(nil), in.Plans...)
	return out
}

// GuestFeatures: unauthenticated creators — hard capped at 3 days.
func GuestFeatures() PlanFeatures {
	return PlanFeatures{
		CustomCode: false, EditTarget: false,
		MaxLinks: -1, // no account quota for guests
		MaxExpireDays: 3, AllowNeverExpire: false,
	}
}
