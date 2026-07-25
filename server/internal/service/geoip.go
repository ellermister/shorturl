package service

import (
	"fmt"
	"log"
	"net"
	"strings"
	"sync"

	"github.com/lionsoul2014/ip2region/binding/golang/xdb"
)

// RegionInfo is parsed from ip2region: Country|Province|City|ISP|iso-alpha2
type RegionInfo struct {
	Raw      string `json:"raw"`
	Country  string `json:"country"`
	Province string `json:"province"`
	City     string `json:"city"`
	ISP      string `json:"isp"`
	ISOCode  string `json:"iso_code"`
	IsChina  bool   `json:"is_china"`
}

type GeoIP struct {
	mu       sync.RWMutex
	searcher *xdb.Searcher
	path     string
}

func NewGeoIP(dbPath string) (*GeoIP, error) {
	g := &GeoIP{path: dbPath}
	if err := g.open(); err != nil {
		return nil, err
	}
	return g, nil
}

func (g *GeoIP) open() error {
	if g.path == "" {
		return fmt.Errorf("ip2region db path empty")
	}
	if err := xdb.VerifyFromFile(g.path); err != nil {
		log.Printf("ip2region verify warning: %v", err)
	}
	cBuff, err := xdb.LoadContentFromFile(g.path)
	if err != nil {
		return fmt.Errorf("load ip2region xdb: %w", err)
	}
	searcher, err := xdb.NewWithBuffer(xdb.IPv4, cBuff)
	if err != nil {
		return fmt.Errorf("create ip2region searcher: %w", err)
	}
	g.mu.Lock()
	if g.searcher != nil {
		g.searcher.Close()
	}
	g.searcher = searcher
	g.mu.Unlock()
	log.Printf("ip2region loaded: %s", g.path)
	return nil
}

func (g *GeoIP) Close() {
	g.mu.Lock()
	defer g.mu.Unlock()
	if g.searcher != nil {
		g.searcher.Close()
		g.searcher = nil
	}
}

func (g *GeoIP) Lookup(ipStr string) RegionInfo {
	info := RegionInfo{}
	ip := net.ParseIP(ipStr)
	if ip == nil {
		return info
	}
	// IPv4-mapped IPv6
	if v4 := ip.To4(); v4 != nil {
		ipStr = v4.String()
	} else {
		// v4-only db for now
		return info
	}

	g.mu.RLock()
	searcher := g.searcher
	g.mu.RUnlock()
	if searcher == nil {
		return info
	}

	raw, err := searcher.Search(ipStr)
	if err != nil || raw == "" {
		return info
	}
	return ParseRegion(raw)
}

func ParseRegion(raw string) RegionInfo {
	parts := strings.Split(raw, "|")
	for len(parts) < 5 {
		parts = append(parts, "")
	}
	info := RegionInfo{
		Raw:      raw,
		Country:  cleanRegionPart(parts[0]),
		Province: cleanRegionPart(parts[1]),
		City:     cleanRegionPart(parts[2]),
		ISP:      cleanRegionPart(parts[3]),
		ISOCode:  cleanRegionPart(parts[4]),
	}
	info.IsChina = info.Country == "中国" || strings.EqualFold(info.ISOCode, "CN")
	return info
}

func cleanRegionPart(s string) string {
	s = strings.TrimSpace(s)
	if s == "0" || s == "内网IP" {
		return ""
	}
	return s
}

func (g *GeoIP) IsChina(ipStr string) bool {
	return g.Lookup(ipStr).IsChina
}

// ClientIP extracts real client IP from headers.
func ClientIP(remoteAddr, xff, xRealIP string) string {
	if xRealIP != "" {
		return strings.TrimSpace(xRealIP)
	}
	if xff != "" {
		parts := strings.Split(xff, ",")
		return strings.TrimSpace(parts[0])
	}
	host, _, err := net.SplitHostPort(remoteAddr)
	if err != nil {
		return remoteAddr
	}
	return host
}
