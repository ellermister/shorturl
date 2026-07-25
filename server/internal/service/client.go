package service

import (
	"regexp"
	"strconv"
	"strings"
)

// ClientInfo is collected from browser fingerprinting (improved over navigator.platform alone).
type ClientInfo struct {
	UserAgent        string  `json:"user_agent"`
	Platform         string  `json:"platform"`
	Vendor           string  `json:"vendor"`
	AppVersion       string  `json:"app_version"`
	AppName          string  `json:"app_name"`
	OuterWidth       int     `json:"outer_width"`
	OuterHeight      int     `json:"outer_height"`
	ScreenWidth      int     `json:"screen_width"`
	ScreenHeight     int     `json:"screen_height"`
	AvailWidth       int     `json:"avail_width"`
	AvailHeight      int     `json:"avail_height"`
	DevicePixelRatio float64 `json:"device_pixel_ratio"`
	MaxTouchPoints   int     `json:"max_touch_points"`
	MobileHint       bool    `json:"mobile_hint"` // navigator.userAgentData.mobile
	PointerCoarse    bool    `json:"pointer_coarse"`
	HoverNone        bool    `json:"hover_none"`
}

type DeviceType string

const (
	DeviceMobile  DeviceType = "mobile"
	DeviceTablet  DeviceType = "tablet"
	DevicePC      DeviceType = "pc"
	DeviceUnknown DeviceType = "unknown"
)

var (
	reMobileUA       = regexp.MustCompile(`(?i)(iphone|ipod|android.*mobile|windows phone|blackberry|bb10|opera mini|webos|fennec|iemobile)`)
	reTabletUA       = regexp.MustCompile(`(?i)(ipad|tablet|kindle|silk|playbook)`)
	reAndroid        = regexp.MustCompile(`(?i)android`)
	reAndroidMobile  = regexp.MustCompile(`(?i)android.*mobile`)
	reMobilePlatform = regexp.MustCompile(`(?i)^(iphone|ipod|android|blackberry|mobile)$`)
	rePCPlatform     = regexp.MustCompile(`(?i)^(win32|win64|macintel|linux x86|linux amd64|freebsd|cros)$`)
)

// DetectDeviceType combines UA, platform, screen and touch signals.
func DetectDeviceType(c ClientInfo) DeviceType {
	ua := strings.ToLower(c.UserAgent)
	platform := strings.ToLower(strings.TrimSpace(c.Platform))

	scoreMobile := 0
	scorePC := 0
	scoreTablet := 0

	if c.MobileHint {
		scoreMobile += 3
	}
	if reMobileUA.MatchString(ua) {
		scoreMobile += 3
	}
	if reTabletUA.MatchString(ua) || (reAndroid.MatchString(ua) && !reAndroidMobile.MatchString(ua)) {
		scoreTablet += 3
	}
	if reMobilePlatform.MatchString(platform) {
		scoreMobile += 2
	}
	if rePCPlatform.MatchString(platform) {
		scorePC += 2
	}
	if strings.Contains(platform, "mac") && c.MaxTouchPoints > 1 {
		// iPadOS reports MacIntel
		scoreTablet += 3
	}

	w, h := pickScreen(c)
	shortSide, longSide := w, h
	if shortSide > longSide {
		shortSide, longSide = longSide, shortSide
	}

	// Portrait phone-like screens
	if w > 0 && h > 0 {
		if shortSide <= 500 && longSide <= 1000 {
			scoreMobile += 2
		} else if shortSide <= 834 && longSide <= 1194 {
			scoreTablet += 1
		}
		if h > w && shortSide <= 500 {
			scoreMobile += 1
		}
		// Large desktop
		if w >= 1280 && h >= 720 && c.MaxTouchPoints == 0 {
			scorePC += 2
		}
		if w >= 1920 && h >= 1024 {
			scorePC += 2
		}
	}

	if c.MaxTouchPoints > 0 {
		scoreMobile += 1
	}
	if c.PointerCoarse || c.HoverNone {
		scoreMobile += 1
	}
	if c.MaxTouchPoints == 0 && !c.PointerCoarse && !c.HoverNone {
		scorePC += 1
	}
	if c.DevicePixelRatio >= 2 && shortSide > 0 && shortSide <= 500 {
		scoreMobile += 1
	}

	// Classic spoof: iPhone platform with Windows UA → treat as mobile (legacy behavior)
	if platform == "iphone" && strings.Contains(ua, "windows") {
		scoreMobile += 2
	}

	switch {
	case scoreTablet >= scoreMobile && scoreTablet >= scorePC && scoreTablet > 0:
		return DeviceTablet
	case scoreMobile >= scorePC && scoreMobile > 0:
		return DeviceMobile
	case scorePC > 0:
		return DevicePC
	default:
		return DeviceUnknown
	}
}

func pickScreen(c ClientInfo) (int, int) {
	w, h := c.OuterWidth, c.OuterHeight
	if w == 0 || h == 0 {
		w, h = c.ScreenWidth, c.ScreenHeight
	}
	if w == 0 || h == 0 {
		w, h = c.AvailWidth, c.AvailHeight
	}
	return w, h
}

// IsMobileAccess allows phones (and optionally small tablets in portrait).
func IsMobileAccess(c ClientInfo) bool {
	dt := DetectDeviceType(c)
	if dt == DeviceMobile {
		return true
	}
	// Legacy PHP heuristic fallback
	w, h := pickScreen(c)
	if w > 0 && h > 0 && w <= 1284 && h <= 2778 && h > w {
		return true
	}
	return false
}

// IsPCAccess allows desktops / large screens without phone signals.
func IsPCAccess(c ClientInfo) bool {
	dt := DetectDeviceType(c)
	if dt == DevicePC {
		return true
	}
	if dt == DeviceMobile {
		return false
	}
	w, h := pickScreen(c)
	if w >= 1280 && h >= 720 && c.MaxTouchPoints == 0 {
		return true
	}
	// Legacy PHP heuristic
	if w >= 1920 && h >= 1024 {
		return true
	}
	platform := strings.ToLower(c.Platform)
	ua := strings.ToLower(c.UserAgent)
	if (platform == "win32" || platform == "win64") && strings.Contains(ua, "windows") {
		return true
	}
	return false
}

// ParseLegacyClient parses "appVersion|||appName|||platform|||outerWidth|||outerHeight".
func ParseLegacyClient(raw string) (ClientInfo, bool) {
	parts := strings.Split(raw, "|||")
	if len(parts) < 5 {
		return ClientInfo{}, false
	}
	ow, _ := strconv.Atoi(parts[3])
	oh, _ := strconv.Atoi(parts[4])
	c := ClientInfo{
		AppVersion:  parts[0],
		AppName:     parts[1],
		Platform:    parts[2],
		OuterWidth:  ow,
		OuterHeight: oh,
		UserAgent:   parts[0],
	}
	if len(parts) >= 10 {
		c.ScreenWidth, _ = strconv.Atoi(parts[5])
		c.ScreenHeight, _ = strconv.Atoi(parts[6])
		c.MaxTouchPoints, _ = strconv.Atoi(parts[7])
		c.DevicePixelRatio, _ = strconv.ParseFloat(parts[8], 64)
		c.MobileHint = parts[9] == "1" || strings.EqualFold(parts[9], "true")
	}
	if len(parts) >= 12 {
		c.PointerCoarse = parts[10] == "1" || strings.EqualFold(parts[10], "true")
		c.HoverNone = parts[11] == "1" || strings.EqualFold(parts[11], "true")
	}
	return c, true
}

// China browser UA keywords (from original BanChinaBrowser).
var chinaBrowserKeywords = []string{
	"bidubrowser", "metasr", "tencenttraveler", "micromessenger", "miuibrowser",
	"yodaobot", "iqiyiapp", "weibo", "qqbrowser", "quark", "snebuy-app",
	"alipayclient", "aliapp", "115browser", "2345explorer", "mb2345browser",
	"2345chrome", "qihoobrowser", "qhbrowser", "360spider", "haosouspider",
	"baidubrowser", "baiduboxapp", "baidud", "dingtalk", "douban.frodo",
	"aweme", "huaweibrowser", "hbpc", "lbbrowser", "liebaofast", "mzbrowser",
	"heytapbrowser", "vivobrowser",
}

func IsChinaBrowser(ua string) bool {
	lower := strings.ToLower(ua)
	for _, kw := range chinaBrowserKeywords {
		if strings.Contains(lower, strings.ToLower(kw)) {
			return true
		}
	}
	// bare "qq/" app often appears; avoid matching all "qq" substrings too aggressively
	if strings.Contains(lower, " qq/") || strings.Contains(lower, "qq/") {
		return true
	}
	return false
}
