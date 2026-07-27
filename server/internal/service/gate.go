package service

import (
	"time"

	"github.com/chauncey/shorturl/server/internal/model"
)

// GateResult is the outcome of environment / lifecycle checks at /s or verify.
type GateResult int

const (
	GateOK GateResult = iota
	GateNotFound
	GateUnavailable // expired / burned / disabled
	GateDenied      // region / device / browser policy
)

// LinkUsable checks expiry, burn quota, and status (no feature policy yet).
func LinkUsable(link *model.ShortLink) GateResult {
	if link == nil {
		return GateNotFound
	}
	if link.Status == model.LinkStatusDisabled {
		return GateUnavailable
	}
	if link.Status == model.LinkStatusBurned {
		return GateUnavailable
	}
	if link.ExpiresAt != nil && link.ExpiresAt.Before(time.Now()) {
		return GateUnavailable
	}
	if link.MaxVisits > 0 && link.VisitCount >= link.MaxVisits {
		return GateUnavailable
	}
	return GateOK
}

// CheckUAIP applies ban_china_browser and device limits (UA / fingerprint).
// Mainland / overseas / geo rules are handled by ResolveOutbound.
// For encrypt verify, pass fingerprint-derived ClientInfo for stricter device checks.
func CheckUAIP(link *model.ShortLink, ua, ip string, geo *GeoIP, client *ClientInfo) GateResult {
	_ = ip
	_ = geo
	feats := link.Features
	if FeaturesHas(feats, FeatBanChinaBrowser) && IsChinaBrowser(ua) {
		return GateDenied
	}

	needDevice := FeaturesHas(feats, FeatPCOnly) || FeaturesHas(feats, FeatMobileOnly)
	if !needDevice {
		return GateOK
	}
	if client != nil {
		if FeaturesHas(feats, FeatMobileOnly) && !IsMobileAccess(*client) {
			return GateDenied
		}
		if FeaturesHas(feats, FeatPCOnly) && !IsPCAccess(*client) {
			return GateDenied
		}
		return GateOK
	}
	// UA-only coarse check (normal jump / encrypt pre-screen)
	c := ClientInfo{UserAgent: ua}
	if FeaturesHas(feats, FeatMobileOnly) && !IsMobileAccess(c) {
		return GateDenied
	}
	if FeaturesHas(feats, FeatPCOnly) && !IsPCAccess(c) {
		return GateDenied
	}
	return GateOK
}

func IsEncryptedJump(featuresJSON string) bool {
	return FeaturesHas(featuresJSON, FeatEncrypt)
}

func IsNormalJump(featuresJSON string) bool {
	return FeaturesHas(featuresJSON, FeatNormal) && !FeaturesHas(featuresJSON, FeatEncrypt)
}
