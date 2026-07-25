package service

import (
	"log"
	"time"

	"github.com/chauncey/shorturl/server/internal/model"
	"gorm.io/gorm"
)

// CleanupService periodically purges burned / expired / soft-deleted links and their visits.
type CleanupService struct {
	db       *gorm.DB
	interval time.Duration
	stop     chan struct{}
}

func NewCleanupService(db *gorm.DB, interval time.Duration) *CleanupService {
	if interval <= 0 {
		interval = time.Hour
	}
	return &CleanupService{db: db, interval: interval, stop: make(chan struct{})}
}

func (s *CleanupService) Start() {
	go func() {
		// run once shortly after boot
		time.Sleep(15 * time.Second)
		s.runOnce()
		t := time.NewTicker(s.interval)
		defer t.Stop()
		for {
			select {
			case <-t.C:
				s.runOnce()
			case <-s.stop:
				return
			}
		}
	}()
}

func (s *CleanupService) Stop() {
	close(s.stop)
}

func (s *CleanupService) runOnce() {
	n, err := s.Purge()
	if err != nil {
		log.Printf("cleanup error: %v", err)
		return
	}
	if n > 0 {
		log.Printf("cleanup purged %d short links", n)
	}
}

func (s *CleanupService) Purge() (int64, error) {
	now := time.Now()
	var links []model.ShortLink
	err := s.db.Where(
		"deleted_at IS NOT NULL OR status = ? OR (expires_at IS NOT NULL AND expires_at < ?)",
		model.LinkStatusBurned, now,
	).Limit(500).Find(&links).Error
	if err != nil {
		return 0, err
	}
	if len(links) == 0 {
		return 0, nil
	}
	ids := make([]uint64, 0, len(links))
	codes := make([]string, 0, len(links))
	for _, l := range links {
		ids = append(ids, l.ID)
		codes = append(codes, l.Code)
	}
	tx := s.db.Begin()
	if err := tx.Where("link_id IN ? OR code IN ?", ids, codes).Delete(&model.LinkVisit{}).Error; err != nil {
		tx.Rollback()
		return 0, err
	}
	res := tx.Where("id IN ?", ids).Delete(&model.ShortLink{})
	if res.Error != nil {
		tx.Rollback()
		return 0, res.Error
	}
	if err := tx.Commit().Error; err != nil {
		return 0, err
	}
	return res.RowsAffected, nil
}
