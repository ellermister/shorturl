package handler

import (
	"context"
	"net/http"
	"strings"

	"github.com/chauncey/shorturl/server/internal/service"
	"github.com/chauncey/shorturl/server/internal/util"
)

type ctxKey string

const claimsKey ctxKey = "claims"

func claimsFrom(ctx context.Context) *service.Claims {
	c, _ := ctx.Value(claimsKey).(*service.Claims)
	return c
}

// OptionalAuth parses Bearer token when present; does not reject anonymous.
func (a *API) OptionalAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		h := r.Header.Get("Authorization")
		if strings.HasPrefix(h, "Bearer ") {
			if claims, err := a.auth.Parse(strings.TrimPrefix(h, "Bearer ")); err == nil {
				r = r.WithContext(context.WithValue(r.Context(), claimsKey, claims))
			}
		}
		next.ServeHTTP(w, r)
	})
}

// UserAuth requires a valid logged-in user (any role).
func (a *API) UserAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		h := r.Header.Get("Authorization")
		if !strings.HasPrefix(h, "Bearer ") {
			util.Fail(w, 401, "unauthorized")
			return
		}
		claims, err := a.auth.Parse(strings.TrimPrefix(h, "Bearer "))
		if err != nil {
			util.Fail(w, 401, "unauthorized")
			return
		}
		next.ServeHTTP(w, r.WithContext(context.WithValue(r.Context(), claimsKey, claims)))
	})
}

// AdminAuth requires role=admin.
func (a *API) AdminAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		h := r.Header.Get("Authorization")
		if !strings.HasPrefix(h, "Bearer ") {
			util.Fail(w, 401, "unauthorized")
			return
		}
		claims, err := a.auth.Parse(strings.TrimPrefix(h, "Bearer "))
		if err != nil || !claims.IsAdmin() {
			util.Fail(w, 401, "unauthorized")
			return
		}
		next.ServeHTTP(w, r.WithContext(context.WithValue(r.Context(), claimsKey, claims)))
	})
}

func (a *API) SiteTrack(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		path := r.URL.Path
		// Skip admin / me / auth APIs from site PV.
		if strings.Contains(path, "/admin") || strings.Contains(path, "/me") || strings.Contains(path, "/auth") || strings.Contains(path, "/challenge") {
			next.ServeHTTP(w, r)
			return
		}
		ip := service.ClientIP(r.RemoteAddr, r.Header.Get("X-Forwarded-For"), r.Header.Get("X-Real-IP"))
		a.stats.TrackSiteVisit(ip, r.UserAgent())
		next.ServeHTTP(w, r)
	})
}
