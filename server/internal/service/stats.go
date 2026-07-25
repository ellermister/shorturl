package service

import (
	"crypto/sha256"
	"encoding/hex"
	"time"

	"github.com/chauncey/shorturl/server/internal/model"
	"gorm.io/gorm"
	"gorm.io/gorm/clause"
)

type StatsService struct {
	db *gorm.DB
}

func NewStatsService(db *gorm.DB) *StatsService {
	return &StatsService{db: db}
}

func (s *StatsService) TrackSiteVisit(ip, ua string) {
	now := time.Now()
	day := now.Format("2006-01-02")
	key := hashVisitor(day, ip, ua)

	// ensure daily row
	stat := model.SiteStatDaily{StatDate: day, CreatedAt: &now, UpdatedAt: &now}
	_ = s.db.Clauses(clause.OnConflict{
		Columns:   []clause.Column{{Name: "stat_date"}},
		DoUpdates: clause.Assignments(map[string]interface{}{"updated_at": now}),
	}).Create(&stat).Error

	_ = s.db.Model(&model.SiteStatDaily{}).Where("stat_date = ?", day).
		UpdateColumn("pv", gorm.Expr("pv + 1")).Error

	visitor := model.SiteVisitor{StatDate: day, VisitorKey: key, CreatedAt: &now}
	res := s.db.Clauses(clause.OnConflict{DoNothing: true}).Create(&visitor)
	if res.Error == nil && res.RowsAffected > 0 {
		_ = s.db.Model(&model.SiteStatDaily{}).Where("stat_date = ?", day).
			UpdateColumn("uv", gorm.Expr("uv + 1")).Error
	}
}

func (s *StatsService) IncrLinkCreated(t time.Time) error {
	day := t.Format("2006-01-02")
	now := t
	stat := model.SiteStatDaily{StatDate: day, CreatedAt: &now, UpdatedAt: &now}
	_ = s.db.Clauses(clause.OnConflict{
		Columns:   []clause.Column{{Name: "stat_date"}},
		DoUpdates: clause.Assignments(map[string]interface{}{"updated_at": now}),
	}).Create(&stat).Error
	return s.db.Model(&model.SiteStatDaily{}).Where("stat_date = ?", day).
		UpdateColumn("link_created", gorm.Expr("link_created + 1")).Error
}

type DashboardStats struct {
	Today      model.SiteStatDaily   `json:"today"`
	Days       []model.SiteStatDaily `json:"days"`
	LinkTotal  int64                 `json:"link_total"`
	LinkActive int64                 `json:"link_active"`
	VisitTotal int64                 `json:"visit_total"`
}

func (s *StatsService) Dashboard(days int) (*DashboardStats, error) {
	if days <= 0 || days > 90 {
		days = 14
	}
	today := time.Now().Format("2006-01-02")
	var todayStat model.SiteStatDaily
	_ = s.db.Where("stat_date = ?", today).First(&todayStat).Error
	if todayStat.StatDate == "" {
		todayStat.StatDate = today
	}

	since := time.Now().AddDate(0, 0, -(days - 1)).Format("2006-01-02")
	var list []model.SiteStatDaily
	if err := s.db.Where("stat_date >= ?", since).Order("stat_date ASC").Find(&list).Error; err != nil {
		return nil, err
	}

	var linkTotal, linkActive, visitTotal int64
	_ = s.db.Model(&model.ShortLink{}).Count(&linkTotal).Error
	_ = s.db.Model(&model.ShortLink{}).Where("deleted_at IS NULL AND status = 1").Count(&linkActive).Error
	_ = s.db.Model(&model.LinkVisit{}).Where("success = 1").Count(&visitTotal).Error

	return &DashboardStats{
		Today:      todayStat,
		Days:       list,
		LinkTotal:  linkTotal,
		LinkActive: linkActive,
		VisitTotal: visitTotal,
	}, nil
}

func hashVisitor(day, ip, ua string) string {
	sum := sha256.Sum256([]byte(day + "|" + ip + "|" + ua))
	return hex.EncodeToString(sum[:16])
}
