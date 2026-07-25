package service

import "encoding/json"

const (
	FeatNormal          = "normal"
	FeatDynamic         = "dynamic" // no-referrer outbound (encrypt only)
	FeatEncrypt         = "encrypt"
	FeatWhisper         = "whisper"
	FeatOnce            = "once"
	FeatPassword        = "password"
	FeatPCOnly          = "pc_only"
	FeatMobileOnly      = "mobile_only"
	FeatChinaOnly       = "china_only"
	FeatNonChinaOnly    = "non_china_only"
	FeatBanChinaBrowser = "ban_china_browser"
	FeatFakePage        = "fake_page"
)

var allFeatures = map[string]bool{
	FeatNormal: true, FeatDynamic: true, FeatEncrypt: true, FeatWhisper: true,
	FeatOnce: true, FeatPassword: true, FeatPCOnly: true, FeatMobileOnly: true,
	FeatChinaOnly: true, FeatNonChinaOnly: true, FeatBanChinaBrowser: true, FeatFakePage: true,
}

// NormalizeFeatures: jump mode is normal XOR encrypt (default encrypt).
// dynamic / whisper / password only make sense with encrypt.
func NormalizeFeatures(in []string) []string {
	seen := map[string]bool{}
	var out []string
	mode := ""
	for _, f := range in {
		if !allFeatures[f] || seen[f] {
			continue
		}
		if f == FeatNormal || f == FeatEncrypt {
			if mode != "" {
				continue
			}
			mode = f
		}
		if f == FeatPCOnly && seen[FeatMobileOnly] {
			continue
		}
		if f == FeatMobileOnly && seen[FeatPCOnly] {
			continue
		}
		if f == FeatChinaOnly && seen[FeatNonChinaOnly] {
			continue
		}
		if f == FeatNonChinaOnly && seen[FeatChinaOnly] {
			continue
		}
		seen[f] = true
		out = append(out, f)
	}
	if mode == "" {
		out = append(out, FeatEncrypt)
		mode = FeatEncrypt
	}
	if mode == FeatNormal {
		// strip encrypt-only extras
		filtered := out[:0]
		for _, f := range out {
			if f == FeatDynamic || f == FeatWhisper || f == FeatPassword {
				continue
			}
			filtered = append(filtered, f)
		}
		out = filtered
	}
	return out
}

func FeaturesHas(featuresJSON string, name string) bool {
	var arr []string
	_ = json.Unmarshal([]byte(featuresJSON), &arr)
	for _, f := range arr {
		if f == name {
			return true
		}
	}
	return false
}

func FeaturesMarshal(features []string) string {
	b, _ := json.Marshal(features)
	return string(b)
}

func FeaturesUnmarshal(s string) []string {
	var arr []string
	_ = json.Unmarshal([]byte(s), &arr)
	return arr
}
