package service

import (
	"encoding/json"
	"fmt"
	"net/url"
	"strings"
	"unicode/utf8"

	"github.com/chauncey/shorturl/server/internal/model"
)

// Normalized ISP codes used in geo rules.
const (
	ISPTelecom = "telecom"
	ISPUnicom  = "unicom"
	ISPMobile  = "mobile"
	ISPOther   = "other"
)

// GeoRequire is a quick allow-list of visitors (deny others → fallback).
const (
	GeoRequireNone     = ""
	GeoRequireMainland = "mainland" // 仅大陆
	GeoRequireOverseas = "overseas" // 仅海外（非大陆）
)

// GeoPolicy controls region/ISP-based routing.
//
// Matching order:
//  1. Require mainland/overseas — if fail → denied (caller uses FallbackURL / fake / 404)
//  2. Rules scored by specificity — highest matching rule wins
//  3. Else → link.TargetURL (default)
//
// A rule field left empty means "any" for that dimension.
type GeoPolicy struct {
	Require     string    `json:"require,omitempty"`      // "" | mainland | overseas
	FallbackURL string    `json:"fallback_url,omitempty"` // denied / hard fail
	Rules       []GeoRule `json:"rules,omitempty"`
}

// GeoRule is one routing row. Empty dimensions are wildcards.
// Mainland: nil=any, true=must mainland CN, false=must non-mainland.
type GeoRule struct {
	Mainland *bool  `json:"mainland,omitempty"`
	Country  string `json:"country,omitempty"`  // ISO-3166 alpha-2, e.g. CN / JP / US
	Province string `json:"province,omitempty"` // matched after NormalizePlace
	City     string `json:"city,omitempty"`
	ISP      string `json:"isp,omitempty"` // telecom | unicom | mobile | other
	URL      string `json:"url"`
}

// OutboundDecision is the result of geo resolution for a visit.
type OutboundDecision struct {
	URL    string // redirect target when !Denied
	Denied bool   // visitor blocked by Require (or empty region under strict require)
}

func ParseGeoPolicy(raw string) GeoPolicy {
	raw = strings.TrimSpace(raw)
	if raw == "" || raw == "{}" {
		return GeoPolicy{}
	}
	var p GeoPolicy
	if json.Unmarshal([]byte(raw), &p) != nil {
		return GeoPolicy{}
	}
	return p
}

func MarshalGeoPolicy(p GeoPolicy) string {
	b, err := json.Marshal(p)
	if err != nil {
		return "{}"
	}
	return string(b)
}

func (p GeoPolicy) IsZero() bool {
	return p.Require == "" && p.FallbackURL == "" && len(p.Rules) == 0
}

// GeoPolicyFromFeatures maps legacy china_only / non_china_only into Require
// when the explicit policy has no require set.
func GeoPolicyFromFeatures(features []string, base GeoPolicy) GeoPolicy {
	if base.Require != "" {
		return base
	}
	hasMainland, hasOverseas := false, false
	for _, f := range features {
		if f == FeatChinaOnly {
			hasMainland = true
		}
		if f == FeatNonChinaOnly {
			hasOverseas = true
		}
	}
	if hasMainland && !hasOverseas {
		base.Require = GeoRequireMainland
	} else if hasOverseas && !hasMainland {
		base.Require = GeoRequireOverseas
	}
	return base
}

// NormalizeISP maps ip2region ISP strings (and UI values) to a stable code.
func NormalizeISP(raw string) string {
	s := strings.ToLower(strings.TrimSpace(raw))
	if s == "" || s == "0" {
		return ""
	}
	switch s {
	case ISPTelecom, ISPUnicom, ISPMobile, ISPOther:
		return s
	}
	switch {
	case strings.Contains(s, "电信") || strings.Contains(s, "telecom") || strings.Contains(s, "chinanet") || strings.Contains(s, "ctcc"):
		return ISPTelecom
	case strings.Contains(s, "联通") || strings.Contains(s, "unicom") || strings.Contains(s, "cucc") || strings.Contains(s, "china unicom"):
		return ISPUnicom
	case strings.Contains(s, "移动") || strings.Contains(s, "mobile") || strings.Contains(s, "cmcc") || strings.Contains(s, "china mobile"):
		return ISPMobile
	default:
		return ISPOther
	}
}

// NormalizePlace strips common suffixes so "广东省" ≈ "广东".
func NormalizePlace(raw string) string {
	s := strings.TrimSpace(raw)
	if s == "" || s == "0" {
		return ""
	}
	for _, suf := range []string{"特别行政区", "维吾尔自治区", "壮族自治区", "回族自治区", "自治区", "省", "市", "地区", "盟"} {
		if strings.HasSuffix(s, suf) && len([]rune(s)) > len([]rune(suf)) {
			s = strings.TrimSuffix(s, suf)
			break
		}
	}
	return strings.ToLower(s)
}

func NormalizeCountry(raw string) string {
	s := strings.TrimSpace(raw)
	if s == "" || s == "0" {
		return ""
	}
	return strings.ToUpper(s)
}

func placeEqual(rule, got string) bool {
	a, b := NormalizePlace(rule), NormalizePlace(got)
	if a == "" || b == "" {
		return false
	}
	return a == b || strings.Contains(b, a) || strings.Contains(a, b)
}

// ruleScore: higher = more specific. Empty dimensions add 0.
func ruleScore(r GeoRule) int {
	score := 0
	if r.Mainland != nil {
		score += 10
	}
	if strings.TrimSpace(r.Country) != "" {
		score += 20
	}
	if strings.TrimSpace(r.Province) != "" {
		score += 40
	}
	if strings.TrimSpace(r.City) != "" {
		score += 80
	}
	if strings.TrimSpace(r.ISP) != "" {
		score += 15
	}
	return score
}

func ruleMatches(r GeoRule, region RegionInfo) bool {
	if strings.TrimSpace(r.URL) == "" {
		return false
	}
	if r.Mainland != nil {
		if *r.Mainland && !region.IsChina {
			return false
		}
		if !*r.Mainland && region.IsChina {
			return false
		}
	}
	if c := NormalizeCountry(r.Country); c != "" {
		got := NormalizeCountry(region.ISOCode)
		if got != "" {
			if c != got {
				return false
			}
		} else if !(c == "CN" && region.IsChina) {
			return false
		}
	}
	if p := strings.TrimSpace(r.Province); p != "" {
		if !placeEqual(p, region.Province) {
			return false
		}
	}
	if city := strings.TrimSpace(r.City); city != "" {
		if !placeEqual(city, region.City) {
			return false
		}
	}
	if isp := NormalizeISP(r.ISP); isp != "" {
		if NormalizeISP(region.ISP) != isp {
			return false
		}
	}
	return true
}

// MatchGeoRules returns the URL of the most specific matching rule, or "".
func MatchGeoRules(rules []GeoRule, region RegionInfo) string {
	bestScore := -1
	bestURL := ""
	for _, r := range rules {
		if !ruleMatches(r, region) {
			continue
		}
		sc := ruleScore(r)
		if sc > bestScore {
			bestScore = sc
			bestURL = strings.TrimSpace(r.URL)
		}
	}
	return bestURL
}

// ResolveOutbound picks the redirect URL (or Denied) for this visitor region.
func ResolveOutbound(link *model.ShortLink, region RegionInfo) OutboundDecision {
	if link == nil {
		return OutboundDecision{Denied: true}
	}
	policy := GeoPolicyFromFeatures(FeaturesUnmarshal(link.Features), ParseGeoPolicy(link.GeoPolicy))

	switch policy.Require {
	case GeoRequireMainland:
		if !region.IsChina {
			return OutboundDecision{Denied: true}
		}
	case GeoRequireOverseas:
		if region.IsChina {
			return OutboundDecision{Denied: true}
		}
	}

	if u := MatchGeoRules(policy.Rules, region); u != "" {
		return OutboundDecision{URL: u}
	}
	return OutboundDecision{URL: link.TargetURL}
}

// GeoFallbackURL is used when the visitor is denied (or soft-fail path).
func GeoFallbackURL(link *model.ShortLink) string {
	if link == nil {
		return ""
	}
	p := ParseGeoPolicy(link.GeoPolicy)
	return strings.TrimSpace(p.FallbackURL)
}

// NormalizeHTTPURL accepts a user-entered URL and returns a canonical http(s) form.
func NormalizeHTTPURL(raw string) (string, error) {
	raw = strings.TrimSpace(raw)
	if raw == "" {
		return "", fmt.Errorf("empty url")
	}
	if utf8.RuneCountInString(raw) > 2047 {
		return "", ErrURLTooLong
	}
	if !strings.Contains(raw, "://") {
		raw = "http://" + raw
	}
	u, err := url.ParseRequestURI(raw)
	if err != nil || u.Host == "" {
		return "", fmt.Errorf("invalid url")
	}
	if u.Scheme != "http" && u.Scheme != "https" {
		return "", fmt.Errorf("invalid url scheme")
	}
	return raw, nil
}

// SanitizeGeoPolicy trims fields, normalizes URLs, and drops empty rules.
// Invalid rule/fallback URLs return an error (do not silently drop misconfigured targets).
func SanitizeGeoPolicy(p GeoPolicy) (GeoPolicy, error) {
	p.Require = strings.TrimSpace(p.Require)
	if p.Require != GeoRequireNone && p.Require != GeoRequireMainland && p.Require != GeoRequireOverseas {
		p.Require = GeoRequireNone
	}
	fb := strings.TrimSpace(p.FallbackURL)
	if fb != "" {
		u, err := NormalizeHTTPURL(fb)
		if err != nil {
			return GeoPolicy{}, fmt.Errorf("fallback_url: %w", err)
		}
		p.FallbackURL = u
	} else {
		p.FallbackURL = ""
	}
	out := make([]GeoRule, 0, len(p.Rules))
	for i, r := range p.Rules {
		raw := strings.TrimSpace(r.URL)
		if raw == "" {
			continue
		}
		u, err := NormalizeHTTPURL(raw)
		if err != nil {
			return GeoPolicy{}, fmt.Errorf("rules[%d].url: %w", i, err)
		}
		r.URL = u
		r.Country = NormalizeCountry(r.Country)
		r.Province = strings.TrimSpace(r.Province)
		r.City = strings.TrimSpace(r.City)
		r.ISP = NormalizeISP(r.ISP)
		out = append(out, r)
	}
	p.Rules = out
	return p, nil
}

// SyncChinaFeatures keeps legacy china_only / non_china_only flags aligned with Require.
func SyncChinaFeatures(features []string, require string) []string {
	out := make([]string, 0, len(features)+1)
	for _, f := range features {
		if f == FeatChinaOnly || f == FeatNonChinaOnly {
			continue
		}
		out = append(out, f)
	}
	switch require {
	case GeoRequireMainland:
		out = append(out, FeatChinaOnly)
	case GeoRequireOverseas:
		out = append(out, FeatNonChinaOnly)
	}
	return out
}
