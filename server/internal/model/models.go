package model

import "time"

const (
	RoleUser  = "user"
	RoleAdmin = "admin"

	UserStatusActive   = 1
	UserStatusDisabled = 0

	LinkStatusActive   = 1
	LinkStatusDisabled = 0
	LinkStatusBurned   = 2

	ConfigKeyPlans       = "membership_plans"
	ConfigKeyGuestLimits = "guest_limits"
)

// User is a unified account (normal user or admin).
type User struct {
	ID           uint64     `gorm:"primaryKey;autoIncrement" json:"id"`
	Username     string     `gorm:"size:64;not null;default:'';uniqueIndex" json:"username"`
	PasswordHash string     `gorm:"size:255;not null;default:''" json:"-"`
	Role         string     `gorm:"size:16;not null;default:'user'" json:"role"` // user|admin
	PlanID       string     `gorm:"size:32;not null;default:'free'" json:"plan_id"`
	Status       int        `gorm:"not null;default:1" json:"status"`
	CreatedAt    *time.Time `json:"created_at"`
	UpdatedAt    *time.Time `json:"updated_at"`
}

func (User) TableName() string { return "users" }

// ShortLink stores a short URL and its access policies.
// No foreign keys by design. user_id=0 means guest-created.
type ShortLink struct {
	ID         uint64     `gorm:"primaryKey;autoIncrement" json:"id"`
	UserID     uint64     `gorm:"not null;default:0;index" json:"user_id"`
	CreatorIP  string     `gorm:"size:64;not null;default:'';index" json:"creator_ip,omitempty"`
	Code       string     `gorm:"size:32;not null;default:'';uniqueIndex" json:"code"`
	TargetURL  string     `gorm:"size:2048;not null;default:''" json:"target_url"`
	Features   string     `gorm:"size:512;not null;default:'[]'" json:"features"`
	Password   string     `gorm:"size:128;not null;default:''" json:"password,omitempty"`
	Whisper    string     `gorm:"type:text;not null;default:''" json:"whisper,omitempty"`
	GeoPolicy  string     `gorm:"type:text;not null;default:'{}'" json:"geo_policy,omitempty"`
	VisitCount int64      `gorm:"not null;default:0" json:"visit_count"`
	MaxVisits  int64      `gorm:"not null;default:0" json:"max_visits"`
	Status     int        `gorm:"not null;default:1" json:"status"`
	CreatedAt  *time.Time `json:"created_at"`
	UpdatedAt  *time.Time `json:"updated_at"`
	ExpiresAt  *time.Time `json:"expires_at"`
	DeletedAt  *time.Time `json:"deleted_at"`
}

func (ShortLink) TableName() string { return "short_links" }

// LinkVisit records one access attempt / successful visit.
type LinkVisit struct {
	ID           uint64     `gorm:"primaryKey;autoIncrement" json:"id"`
	LinkID       uint64     `gorm:"not null;default:0;index" json:"link_id"`
	Code         string     `gorm:"size:32;not null;default:'';index" json:"code"`
	IP           string     `gorm:"size:64;not null;default:''" json:"ip"`
	UserAgent    string     `gorm:"size:512;not null;default:''" json:"user_agent"`
	Referer      string     `gorm:"size:512;not null;default:''" json:"referer"`
	Platform     string     `gorm:"size:64;not null;default:''" json:"platform"`
	DeviceType   string     `gorm:"size:32;not null;default:''" json:"device_type"`
	ScreenWidth  int        `gorm:"not null;default:0" json:"screen_width"`
	ScreenHeight int        `gorm:"not null;default:0" json:"screen_height"`
	IsChina      int        `gorm:"not null;default:0" json:"is_china"`
	Country      string     `gorm:"size:64;not null;default:''" json:"country"`
	Province     string     `gorm:"size:64;not null;default:''" json:"province"`
	City         string     `gorm:"size:64;not null;default:''" json:"city"`
	ISP          string     `gorm:"size:64;not null;default:''" json:"isp"`
	RegionRaw    string     `gorm:"size:255;not null;default:''" json:"region_raw"`
	Success      int        `gorm:"not null;default:0" json:"success"`
	FailReason   string     `gorm:"size:128;not null;default:''" json:"fail_reason"`
	CreatedAt    *time.Time `json:"created_at"`
}

func (LinkVisit) TableName() string { return "link_visits" }

type SiteStatDaily struct {
	ID          uint64     `gorm:"primaryKey;autoIncrement" json:"id"`
	StatDate    string     `gorm:"size:16;not null;default:'';uniqueIndex" json:"stat_date"`
	PV          int64      `gorm:"not null;default:0" json:"pv"`
	UV          int64      `gorm:"not null;default:0" json:"uv"`
	LinkCreated int64      `gorm:"not null;default:0" json:"link_created"`
	CreatedAt   *time.Time `json:"created_at"`
	UpdatedAt   *time.Time `json:"updated_at"`
}

func (SiteStatDaily) TableName() string { return "site_stats_daily" }

type SiteVisitor struct {
	ID         uint64     `gorm:"primaryKey;autoIncrement" json:"id"`
	StatDate   string     `gorm:"size:16;not null;default:'';uniqueIndex:idx_visitor_day" json:"stat_date"`
	VisitorKey string     `gorm:"size:64;not null;default:'';uniqueIndex:idx_visitor_day" json:"visitor_key"`
	CreatedAt  *time.Time `json:"created_at"`
}

func (SiteVisitor) TableName() string { return "site_visitors" }

// AppConfig stores whole JSON blobs (e.g. membership plans).
type AppConfig struct {
	ID        uint64     `gorm:"primaryKey;autoIncrement" json:"id"`
	Key       string     `gorm:"size:64;not null;default:'';uniqueIndex" json:"key"`
	Value     string     `gorm:"type:text;not null;default:''" json:"value"`
	UpdatedAt *time.Time `json:"updated_at"`
	CreatedAt *time.Time `json:"created_at"`
}

func (AppConfig) TableName() string { return "app_configs" }
