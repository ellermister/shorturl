package service

import (
	"errors"
	"fmt"
	"net/url"
	"strings"
	"time"
	"unicode/utf8"

	"github.com/chauncey/shorturl/server/internal/config"
	"github.com/chauncey/shorturl/server/internal/model"
	"github.com/chauncey/shorturl/server/internal/util"
	"gorm.io/gorm"
)

var (
	ErrEmptyURL             = errors.New("url cannot be empty")
	ErrURLTooLong           = errors.New("url too long")
	ErrNotFound             = errors.New("link not found")
	ErrDisabled             = errors.New("link disabled")
	ErrExpired              = errors.New("link expired")
	ErrBurned               = errors.New("link already used")
	ErrBadPassword          = errors.New("invalid password")
	ErrQuotaExceeded        = errors.New("link quota exceeded")
	ErrCustomCodeDeny       = errors.New("custom code not allowed")
	ErrEditTargetDeny       = errors.New("edit target not allowed")
	ErrCodeTaken            = errors.New("code already taken")
	ErrBadCode              = errors.New("invalid custom code")
	ErrBadExpire            = errors.New("invalid expire setting")
	ErrForbidden            = errors.New("forbidden")
	ErrWhisperLoginRequired = errors.New("whisper requires login")
	ErrGuestCreateLimit     = errors.New("guest create rate limit")
	ErrGuestActiveLimit     = errors.New("guest active link limit")
)

type LinkService struct {
	db    *gorm.DB
	cfg   config.Config
	geo   *GeoIP
	stats *StatsService
	plans *PlanService
	guest *GuestLimitsService
}

func NewLinkService(db *gorm.DB, cfg config.Config, geo *GeoIP, stats *StatsService, plans *PlanService, guest *GuestLimitsService) *LinkService {
	return &LinkService{db: db, cfg: cfg, geo: geo, stats: stats, plans: plans, guest: guest}
}

type CreateLinkInput struct {
	UserID     uint64                 `json:"-"`
	PlanID     string                 `json:"-"`
	ClientIP   string                 `json:"-"`
	URL        string                 `json:"url"`
	Features   []string               `json:"features"`
	Extent     map[string]interface{} `json:"extent"`
	CustomCode string                 `json:"custom_code"`
	ExpireDays *int                   `json:"expire_days"` // nil=plan default; 0=never if allowed; >0=days
}

type CreateLinkResult struct {
	Code      string     `json:"code"`
	ShortURL  string     `json:"short_url"`
	Features  []string   `json:"features"`
	Password  string     `json:"password,omitempty"`
	ExpiresAt *time.Time `json:"expires_at"`
}

func (s *LinkService) planFeatures(userID uint64, planID string) PlanFeatures {
	if userID == 0 {
		return GuestFeatures()
	}
	if s.plans != nil {
		return s.plans.FeaturesFor(planID)
	}
	return GuestFeatures()
}

func (s *LinkService) CountUserLinks(userID uint64) (int64, error) {
	var n int64
	err := s.db.Model(&model.ShortLink{}).
		Where("user_id = ? AND deleted_at IS NULL", userID).
		Count(&n).Error
	return n, err
}

func (s *LinkService) checkGuestQuota(ip string) error {
	ip = strings.TrimSpace(ip)
	if ip == "" || s.guest == nil {
		return nil
	}
	lim := s.guest.Get()
	if lim.MaxCreatePerIP24h > 0 {
		since := time.Now().Add(-24 * time.Hour)
		var n int64
		// Count all creates (including soft-deleted) to block create→delete spam.
		err := s.db.Model(&model.ShortLink{}).
			Where("user_id = 0 AND creator_ip = ? AND created_at >= ?", ip, since).
			Count(&n).Error
		if err != nil {
			return err
		}
		if n >= int64(lim.MaxCreatePerIP24h) {
			return ErrGuestCreateLimit
		}
	}
	if lim.MaxActivePerIP > 0 {
		now := time.Now()
		var n int64
		err := s.db.Model(&model.ShortLink{}).
			Where("user_id = 0 AND creator_ip = ? AND deleted_at IS NULL AND status = ?", ip, model.LinkStatusActive).
			Where("(expires_at IS NULL OR expires_at > ?)", now).
			Count(&n).Error
		if err != nil {
			return err
		}
		if n >= int64(lim.MaxActivePerIP) {
			return ErrGuestActiveLimit
		}
	}
	return nil
}

func validateCustomCode(code string) error {
	code = strings.TrimSpace(code)
	n := utf8.RuneCountInString(code)
	if n < 3 || n > 32 {
		return ErrBadCode
	}
	for _, r := range code {
		if (r >= 'a' && r <= 'z') || (r >= 'A' && r <= 'Z') || (r >= '0' && r <= '9') || r == '_' || r == '-' {
			continue
		}
		return ErrBadCode
	}
	return nil
}

func (s *LinkService) resolveExpiresAt(feat PlanFeatures, expireDays *int) (*time.Time, error) {
	now := time.Now()
	// default: max days (or 3 for guest)
	defaultDays := feat.MaxExpireDays
	if defaultDays <= 0 && !feat.AllowNeverExpire {
		defaultDays = 3
	}

	if expireDays == nil {
		if defaultDays <= 0 {
			if feat.AllowNeverExpire {
				return nil, nil
			}
			t := now.Add(3 * 24 * time.Hour)
			return &t, nil
		}
		t := now.Add(time.Duration(defaultDays) * 24 * time.Hour)
		return &t, nil
	}

	days := *expireDays
	if days == 0 {
		if !feat.AllowNeverExpire {
			return nil, ErrBadExpire
		}
		return nil, nil
	}
	if days < 1 {
		return nil, ErrBadExpire
	}
	maxDays := feat.MaxExpireDays
	if maxDays <= 0 {
		maxDays = 3
	}
	if days > maxDays {
		return nil, ErrBadExpire
	}
	t := now.Add(time.Duration(days) * 24 * time.Hour)
	return &t, nil
}

func (s *LinkService) Create(in CreateLinkInput) (*CreateLinkResult, error) {
	raw := strings.TrimSpace(in.URL)
	if raw == "" {
		return nil, ErrEmptyURL
	}
	if utf8.RuneCountInString(raw) > 2047 {
		return nil, ErrURLTooLong
	}
	if !strings.Contains(raw, "://") {
		raw = "http://" + raw
	}
	u, err := url.ParseRequestURI(raw)
	if err != nil || u.Host == "" {
		return nil, fmt.Errorf("invalid url")
	}

	feat := s.planFeatures(in.UserID, in.PlanID)
	if in.UserID > 0 && feat.MaxLinks >= 0 {
		n, err := s.CountUserLinks(in.UserID)
		if err != nil {
			return nil, err
		}
		if n >= int64(feat.MaxLinks) {
			return nil, ErrQuotaExceeded
		}
	}
	if in.UserID == 0 {
		if err := s.checkGuestQuota(in.ClientIP); err != nil {
			return nil, err
		}
	}

	expiresAt, err := s.resolveExpiresAt(feat, in.ExpireDays)
	if err != nil {
		return nil, err
	}

	features := NormalizeFeatures(in.Features)
	password := ""
	whisper := ""
	maxVisits := int64(0)

	if FeaturesHas(FeaturesMarshal(features), FeatPassword) {
		if in.Extent != nil {
			if p, ok := in.Extent["password"].(string); ok {
				password = strings.TrimSpace(p)
			}
		}
		if password == "" {
			password, _ = util.RandomString(8)
		}
	}
	if FeaturesHas(FeaturesMarshal(features), FeatWhisper) {
		if in.UserID == 0 {
			return nil, ErrWhisperLoginRequired
		}
		if in.Extent != nil {
			if w, ok := in.Extent["whisper"].(string); ok {
				whisper = w
			}
		}
		if utf8.RuneCountInString(whisper) > 10000 {
			return nil, fmt.Errorf("whisper too long")
		}
	}
	if FeaturesHas(FeaturesMarshal(features), FeatOnce) {
		maxVisits = 1
	}

	var code string
	custom := strings.TrimSpace(in.CustomCode)
	if custom != "" {
		if !feat.CustomCode {
			return nil, ErrCustomCodeDeny
		}
		if err := validateCustomCode(custom); err != nil {
			return nil, err
		}
		var count int64
		if err := s.db.Model(&model.ShortLink{}).Where("code = ?", custom).Count(&count).Error; err != nil {
			return nil, err
		}
		if count > 0 {
			return nil, ErrCodeTaken
		}
		code = custom
	} else {
		for i := 0; i < 8; i++ {
			c, err := util.RandomCode(s.cfg.CodeLength)
			if err != nil {
				return nil, err
			}
			var count int64
			if err := s.db.Model(&model.ShortLink{}).Where("code = ?", c).Count(&count).Error; err != nil {
				return nil, err
			}
			if count == 0 {
				code = c
				break
			}
		}
		if code == "" {
			return nil, fmt.Errorf("failed to allocate code")
		}
	}

	now := time.Now()
	link := model.ShortLink{
		UserID:     in.UserID,
		CreatorIP:  strings.TrimSpace(in.ClientIP),
		Code:       code,
		TargetURL:  raw,
		Features:   FeaturesMarshal(features),
		Password:   password,
		Whisper:    whisper,
		VisitCount: 0,
		MaxVisits:  maxVisits,
		Status:     model.LinkStatusActive,
		CreatedAt:  &now,
		UpdatedAt:  &now,
		ExpiresAt:  expiresAt,
	}
	if err := s.db.Create(&link).Error; err != nil {
		return nil, err
	}
	_ = s.stats.IncrLinkCreated(now)

	return &CreateLinkResult{
		Code:      code,
		ShortURL:  fmt.Sprintf("%s/s/%s", s.cfg.BaseURL, code),
		Features:  features,
		Password:  password,
		ExpiresAt: expiresAt,
	}, nil
}

type UpdateLinkInput struct {
	TargetURL *string `json:"target_url"`
}

func (s *LinkService) UpdateOwned(id, userID uint64, planID string, in UpdateLinkInput) (*model.ShortLink, error) {
	link, err := s.GetByID(id)
	if err != nil {
		return nil, err
	}
	if link.UserID != userID {
		return nil, ErrForbidden
	}
	feat := s.planFeatures(userID, planID)
	if in.TargetURL != nil {
		if !feat.EditTarget {
			return nil, ErrEditTargetDeny
		}
		raw := strings.TrimSpace(*in.TargetURL)
		if raw == "" {
			return nil, ErrEmptyURL
		}
		if utf8.RuneCountInString(raw) > 2047 {
			return nil, ErrURLTooLong
		}
		if !strings.Contains(raw, "://") {
			raw = "http://" + raw
		}
		u, err := url.ParseRequestURI(raw)
		if err != nil || u.Host == "" {
			return nil, fmt.Errorf("invalid url")
		}
		now := time.Now()
		if err := s.db.Model(&model.ShortLink{}).Where("id = ?", id).Updates(map[string]interface{}{
			"target_url": raw, "updated_at": &now,
		}).Error; err != nil {
			return nil, err
		}
		link.TargetURL = raw
		link.UpdatedAt = &now
	}
	return link, nil
}

func (s *LinkService) SoftDeleteOwned(id, userID uint64) error {
	link, err := s.GetByID(id)
	if err != nil {
		return err
	}
	if link.UserID != userID {
		return ErrForbidden
	}
	return s.SoftDelete(id)
}

func (s *LinkService) GetByCode(code string) (*model.ShortLink, error) {
	var link model.ShortLink
	err := s.db.Where("code = ? AND deleted_at IS NULL", code).First(&link).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, ErrNotFound
	}
	return &link, err
}

func (s *LinkService) SoftDelete(id uint64) error {
	now := time.Now()
	res := s.db.Model(&model.ShortLink{}).Where("id = ? AND deleted_at IS NULL", id).Updates(map[string]interface{}{
		"status":     0,
		"deleted_at": &now,
		"updated_at": &now,
	})
	if res.Error != nil {
		return res.Error
	}
	if res.RowsAffected == 0 {
		return ErrNotFound
	}
	return nil
}

func (s *LinkService) RecordSuccessVisit(link *model.ShortLink, ip, ua, referer string, client *ClientInfo) {
	region := RegionInfo{}
	if s.geo != nil {
		region = s.geo.Lookup(ip)
	}
	ctx := ResolveVisitCtx{IP: ip, UA: ua, Referer: referer, Client: client}
	s.recordVisitSimple(link, ctx, region, 1, "")
	s.bumpSuccess(link)
}

type ResolveVisitCtx struct {
	IP, UA, Referer string
	Client          *ClientInfo
}

func (s *LinkService) bumpSuccess(link *model.ShortLink) {
	now := time.Now()
	updates := map[string]interface{}{
		"visit_count": gorm.Expr("visit_count + 1"),
		"updated_at":  &now,
	}
	if link.MaxVisits == 1 || FeaturesHas(link.Features, FeatOnce) {
		updates["status"] = model.LinkStatusBurned
	}
	_ = s.db.Model(&model.ShortLink{}).Where("id = ?", link.ID).Updates(updates).Error
}

func (s *LinkService) recordVisitSimple(link *model.ShortLink, ctx ResolveVisitCtx, region RegionInfo, success int, reason string) {
	now := time.Now()
	isChina := 0
	if region.IsChina {
		isChina = 1
	}
	v := model.LinkVisit{
		LinkID: link.ID, Code: link.Code, IP: ctx.IP,
		UserAgent: truncate(ctx.UA, 512), Referer: truncate(ctx.Referer, 512),
		IsChina: isChina, Country: truncate(region.Country, 64), Province: truncate(region.Province, 64),
		City: truncate(region.City, 64), ISP: truncate(region.ISP, 64), RegionRaw: truncate(region.Raw, 255),
		Success: success, FailReason: reason, CreatedAt: &now,
	}
	client := ctx.Client
	if client == nil {
		client = ClientInfoFromUA(ctx.UA)
	}
	if client != nil {
		if client.Platform == "" {
			client.Platform = InferPlatformFromUA(ctx.UA)
		}
		v.Platform = truncate(client.Platform, 64)
		v.DeviceType = string(DetectDeviceType(*client))
		w, h := pickScreen(*client)
		v.ScreenWidth, v.ScreenHeight = w, h
	}
	_ = s.db.Create(&v).Error
}

func truncate(s string, n int) string {
	if len(s) <= n {
		return s
	}
	return s[:n]
}

type ListLinksQuery struct {
	Page     int
	PageSize int
	Keyword  string
	UserID   *uint64 // nil = all users (admin); set = filter owner
}

type ListLinksResult struct {
	Total int64             `json:"total"`
	Items []model.ShortLink `json:"items"`
}

func (s *LinkService) List(q ListLinksQuery) (*ListLinksResult, error) {
	if q.Page < 1 {
		q.Page = 1
	}
	if q.PageSize < 1 || q.PageSize > 100 {
		q.PageSize = 20
	}
	tx := s.db.Model(&model.ShortLink{}).Where("deleted_at IS NULL")
	if q.UserID != nil {
		tx = tx.Where("user_id = ?", *q.UserID)
	}
	if kw := strings.TrimSpace(q.Keyword); kw != "" {
		like := "%" + kw + "%"
		tx = tx.Where("code LIKE ? OR target_url LIKE ?", like, like)
	}
	var total int64
	if err := tx.Count(&total).Error; err != nil {
		return nil, err
	}
	var items []model.ShortLink
	err := tx.Order("id DESC").Offset((q.Page - 1) * q.PageSize).Limit(q.PageSize).Find(&items).Error
	return &ListLinksResult{Total: total, Items: items}, err
}

func (s *LinkService) GetByID(id uint64) (*model.ShortLink, error) {
	var link model.ShortLink
	err := s.db.Where("id = ? AND deleted_at IS NULL", id).First(&link).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, ErrNotFound
	}
	return &link, err
}

func (s *LinkService) ListVisits(linkID uint64, page, pageSize int) (int64, []model.LinkVisit, error) {
	if page < 1 {
		page = 1
	}
	if pageSize < 1 || pageSize > 100 {
		pageSize = 20
	}
	var total int64
	q := s.db.Model(&model.LinkVisit{}).Where("link_id = ?", linkID)
	if err := q.Count(&total).Error; err != nil {
		return 0, nil, err
	}
	var items []model.LinkVisit
	err := q.Order("id DESC").Offset((page - 1) * pageSize).Limit(pageSize).Find(&items).Error
	return total, items, err
}

func (s *LinkService) PublicSummary() map[string]int64 {
	var active, history int64
	_ = s.db.Model(&model.ShortLink{}).Where("deleted_at IS NULL AND status = 1").Count(&active).Error
	_ = s.db.Model(&model.ShortLink{}).Count(&history).Error
	return map[string]int64{
		"active":  active,
		"history": history,
	}
}
