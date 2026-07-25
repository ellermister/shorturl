package service

import (
	"strings"

	"github.com/chauncey/shorturl/server/internal/util"
)

// Common crawler / automation UA fragments.
var crawlerKeywords = []string{
	"bot", "spider", "crawl", "slurp", "scrapy", "httpclient", "http-client",
	"python-requests", "python-urllib", "go-http-client", "java/", "okhttp",
	"curl/", "wget", "libwww", "httpunit", "nutch", "phpcrawl", "mechanize",
	"headless", "phantomjs", "selenium", "puppeteer", "playwright",
	"bytespider", "baiduspider", "googlebot", "bingbot", "yandex", "duckduckbot",
	"facebookexternalhit", "twitterbot", "linkedinbot", "embedly",
	"outbrain", "pinterest/", "redditbot", "applebot", "semrush",
	"ahrefs", "mj12bot", "dotbot", "petalbot", "sogou", "360spider", "haosouspider",
	"yodaobot", "sosospider", "youdaobot", "ia_archiver", "archive.org_bot",
	"httrack",
}

// IsCrawler reports obvious non-browser / automation clients.
func IsCrawler(ua string) bool {
	ua = strings.TrimSpace(ua)
	if ua == "" {
		return true
	}
	lower := strings.ToLower(ua)
	for _, kw := range crawlerKeywords {
		if strings.Contains(lower, kw) {
			return true
		}
	}
	if len(ua) < 12 {
		return true
	}
	return false
}

// Decoy destinations for unauthorized traffic (external redirect, not HTML embed).
var decoySites = []string{
	"https://www.jd.com/",
	"https://item.jd.com/100000%s.html",
	"https://www.taobao.com/",
	"https://www.tmall.com/",
	"https://www.amazon.com/",
	"https://www.wikipedia.org/",
	"https://news.yahoo.com/",
	"https://www.bbc.com/news",
	"https://www.microsoft.com/",
	"https://www.apple.com/",
}

// FakeDecoyURL picks a public decoy URL.
func FakeDecoyURL() string {
	n, err := util.RandomCode(7)
	if err != nil || n == "" {
		return "https://www.jd.com/"
	}
	idx := int(n[0]+n[1]+n[2]) % len(decoySites)
	tpl := decoySites[idx]
	if strings.Contains(tpl, "%s") {
		return strings.Replace(tpl, "%s", n, 1)
	}
	return tpl
}
