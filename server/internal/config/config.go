package config

import (
	"os"
	"strconv"
	"strings"
	"time"
)

type Config struct {
	Addr            string
	DBPath          string
	BaseURL         string
	JWTSecret       string
	AdminUser       string
	AdminPass       string
	CodeLength      int
	IP2RegionDB     string
	EncryptPass     string
	CORSOrigins     []string
	JumpTTLSec      int
	CleanupInterval time.Duration
}

func Load() Config {
	return Config{
		Addr:            getEnv("ADDR", ":8080"),
		DBPath:          getEnv("DB_PATH", "data/shorturl.db"),
		BaseURL:         strings.TrimRight(getEnv("BASE_URL", "http://localhost:8080"), "/"),
		JWTSecret:       getEnv("JWT_SECRET", "change-me-shorturl-secret"),
		AdminUser:       getEnv("ADMIN_USER", "admin"),
		AdminPass:       getEnv("ADMIN_PASS", "admin123"),
		CodeLength:      getEnvInt("CODE_LENGTH", 6),
		IP2RegionDB:     getEnv("IP2REGION_DB", "data/ip2region_v4.xdb"),
		EncryptPass:     getEnv("ENCRYPT_PASSPHRASE", "applicationPassword"),
		CORSOrigins:     strings.Split(getEnv("CORS_ORIGINS", "http://localhost:5173,http://127.0.0.1:5173"), ","),
		JumpTTLSec:      getEnvInt("JUMP_TTL_SEC", 60),
		CleanupInterval: time.Duration(getEnvInt("CLEANUP_INTERVAL_MIN", 60)) * time.Minute,
	}
}

func getEnv(key, def string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return def
}

func getEnvInt(key string, def int) int {
	if v := os.Getenv(key); v != "" {
		if n, err := strconv.Atoi(v); err == nil {
			return n
		}
	}
	return def
}
