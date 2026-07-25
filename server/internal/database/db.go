package database

import (
	"fmt"
	"os"
	"path/filepath"
	"time"

	"github.com/chauncey/shorturl/server/internal/config"
	"github.com/chauncey/shorturl/server/internal/model"
	"golang.org/x/crypto/bcrypt"
	"gorm.io/driver/sqlite"
	"gorm.io/gorm"
	"gorm.io/gorm/logger"
)

func Open(cfg config.Config) (*gorm.DB, error) {
	if err := os.MkdirAll(filepath.Dir(cfg.DBPath), 0o755); err != nil {
		return nil, err
	}

	db, err := gorm.Open(sqlite.Open(cfg.DBPath+"?_pragma=foreign_keys(OFF)&_busy_timeout=5000"), &gorm.Config{
		Logger: logger.Default.LogMode(logger.Warn),
	})
	if err != nil {
		return nil, err
	}

	sqlDB, err := db.DB()
	if err != nil {
		return nil, err
	}
	sqlDB.SetMaxOpenConns(1)

	if err := db.AutoMigrate(
		&model.User{},
		&model.ShortLink{},
		&model.LinkVisit{},
		&model.SiteStatDaily{},
		&model.SiteVisitor{},
		&model.AppConfig{},
	); err != nil {
		return nil, err
	}

	if err := ensureAdmin(db, cfg); err != nil {
		return nil, err
	}
	return db, nil
}

func ensureAdmin(db *gorm.DB, cfg config.Config) error {
	var count int64
	if err := db.Model(&model.User{}).Where("role = ?", model.RoleAdmin).Count(&count).Error; err != nil {
		return err
	}
	if count > 0 {
		return nil
	}
	hash, err := bcrypt.GenerateFromPassword([]byte(cfg.AdminPass), bcrypt.DefaultCost)
	if err != nil {
		return err
	}
	now := time.Now()
	admin := model.User{
		Username:     cfg.AdminUser,
		PasswordHash: string(hash),
		Role:         model.RoleAdmin,
		PlanID:       "flagship",
		Status:       model.UserStatusActive,
		CreatedAt:    &now,
		UpdatedAt:    &now,
	}
	if err := db.Create(&admin).Error; err != nil {
		return fmt.Errorf("create default admin: %w", err)
	}
	return nil
}
