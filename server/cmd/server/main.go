package main

import (
	"log"
	"net/http"
	"time"

	"github.com/chauncey/shorturl/server/internal/config"
	"github.com/chauncey/shorturl/server/internal/database"
	"github.com/chauncey/shorturl/server/internal/handler"
	"github.com/chauncey/shorturl/server/internal/service"
	"github.com/chauncey/shorturl/server/internal/webui"
	"github.com/go-chi/chi/v5"
	chimw "github.com/go-chi/chi/v5/middleware"
	"github.com/go-chi/cors"
)

func main() {
	cfg := config.Load()

	db, err := database.Open(cfg)
	if err != nil {
		log.Fatalf("db: %v", err)
	}

	geo, err := service.NewGeoIP(cfg.IP2RegionDB)
	if err != nil {
		log.Fatalf("ip2region: %v (set IP2REGION_DB or place xdb at data/ip2region_v4.xdb)", err)
	}
	defer geo.Close()

	plans, err := service.NewPlanService(db)
	if err != nil {
		log.Fatalf("plans: %v", err)
	}
	guest, err := service.NewGuestLimitsService(db)
	if err != nil {
		log.Fatalf("guest limits: %v", err)
	}

	kv := service.NewKV()
	challenge := service.NewChallengeService(kv, cfg)
	stats := service.NewStatsService(db)
	links := service.NewLinkService(db, cfg, geo, stats, plans, guest)
	auth := service.NewAuthService(db, cfg, plans)

	webFS, err := webui.FS()
	if err != nil {
		log.Fatalf("webui: %v", err)
	}

	api := handler.NewAPI(cfg, links, stats, auth, plans, guest, geo, challenge)

	cleanup := service.NewCleanupService(db, cfg.CleanupInterval)
	cleanup.Start()

	r := chi.NewRouter()
	r.Use(chimw.RequestID)
	r.Use(chimw.RealIP)
	r.Use(chimw.Logger)
	r.Use(chimw.Recoverer)
	r.Use(chimw.Timeout(60 * time.Second))
	r.Use(cors.Handler(cors.Options{
		AllowedOrigins:   cfg.CORSOrigins,
		AllowedMethods:   []string{"GET", "POST", "PUT", "DELETE", "OPTIONS"},
		AllowedHeaders:   []string{"Accept", "Authorization", "Content-Type", "Cache-Control", "Pragma", "X-Challenge"},
		AllowCredentials: true,
		MaxAge:           300,
	}))

	api.Routes(r)
	r.Get("/s/{code}", api.Entry)
	r.Get("/j/{code}", api.JumpOut)
	r.Get("/*", handler.SPAFromFS(webFS))

	log.Printf("shorturl server listening on %s (db=%s, ip2region=%s)", cfg.Addr, cfg.DBPath, cfg.IP2RegionDB)
	if err := http.ListenAndServe(cfg.Addr, r); err != nil {
		log.Fatal(err)
	}
}
