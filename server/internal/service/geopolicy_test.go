package service

import (
	"testing"

	"github.com/chauncey/shorturl/server/internal/model"
)

func TestNormalizeISP(t *testing.T) {
	cases := map[string]string{
		"中国电信":    ISPTelecom,
		"Chinanet":  ISPTelecom,
		"联通":      ISPUnicom,
		"中国移动":    ISPMobile,
		"telecom":   ISPTelecom,
		"":          "",
	}
	for in, want := range cases {
		if got := NormalizeISP(in); got != want {
			t.Fatalf("NormalizeISP(%q)=%q want %q", in, got, want)
		}
	}
}

func TestMatchGeoRulesSpecificity(t *testing.T) {
	yes := true
	rules := []GeoRule{
		{ISP: ISPTelecom, URL: "https://any-telecom.example"},
		{Mainland: &yes, Province: "广东", City: "深圳", ISP: ISPTelecom, URL: "https://sz-telecom.example"},
		{ISP: ISPUnicom, URL: "https://any-unicom.example"},
	}
	region := RegionInfo{
		Country: "中国", ISOCode: "CN", IsChina: true,
		Province: "广东省", City: "深圳市", ISP: "电信",
	}
	if got := MatchGeoRules(rules, region); got != "https://sz-telecom.example" {
		t.Fatalf("got %q", got)
	}
	region.City = "广州市"
	if got := MatchGeoRules(rules, region); got != "https://any-telecom.example" {
		t.Fatalf("guangzhou got %q", got)
	}
}

func TestResolveOutboundRequire(t *testing.T) {
	link := &model.ShortLink{
		TargetURL: "https://default.example",
		GeoPolicy: `{"require":"mainland"}`,
		Features:  `[]`,
	}
	if d := ResolveOutbound(link, RegionInfo{IsChina: false, ISOCode: "US"}); !d.Denied {
		t.Fatal("expected denied for overseas under mainland require")
	}
	d := ResolveOutbound(link, RegionInfo{IsChina: true, ISOCode: "CN", Province: "北京", ISP: "联通"})
	if d.Denied || d.URL != "https://default.example" {
		t.Fatalf("mainland: %+v", d)
	}
}

func TestGeoPolicyFromFeatures(t *testing.T) {
	p := GeoPolicyFromFeatures([]string{FeatChinaOnly}, GeoPolicy{})
	if p.Require != GeoRequireMainland {
		t.Fatalf("require=%q", p.Require)
	}
}
