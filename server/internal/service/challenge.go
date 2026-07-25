package service

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"errors"
	"sort"
	"strings"
	"time"

	"github.com/chauncey/shorturl/server/internal/config"
	"github.com/chauncey/shorturl/server/internal/util"
	"github.com/golang-jwt/jwt/v5"
)

const (
	challengeKeyPrefix = "challenge:"
	ticketKeyPrefix    = "ticket:"
	jumpCookieName     = "su_jump"

	challengeTTL = 5 * time.Minute
	ticketTTL    = 30 * time.Second
	jumpJWTTTL   = 30 * time.Second

	scorePassThreshold = 70
	devtoolsPenalty    = 60
)

var (
	ErrChallengeGone   = errors.New("challenge expired")
	ErrChallengeFail   = errors.New("challenge failed")
	ErrBadHash         = errors.New("bad client hash")
	ErrBadTicket       = errors.New("bad ticket")
	ErrDevToolsCache   = errors.New("devtools cache disabled")
)

type ChallengeService struct {
	kv  *KV
	cfg config.Config
}

func NewChallengeService(kv *KV, cfg config.Config) *ChallengeService {
	return &ChallengeService{kv: kv, cfg: cfg}
}

type ChallengeSession struct {
	ID           string `json:"id"`
	Code         string `json:"code"`
	Seed         string `json:"seed"`
	NeedPassword bool   `json:"need_password"`
	NoReferrer   bool   `json:"no_referrer"`
	CollectTrack bool   `json:"collect_track"`
}

type Ticket struct {
	Code         string `json:"code"`
	FPHash       string `json:"fp_hash"`
	NoReferrer   bool   `json:"no_referrer"`
	Platform     string `json:"platform,omitempty"`
	ScreenWidth  int    `json:"screen_width,omitempty"`
	ScreenHeight int    `json:"screen_height,omitempty"`
	MaxTouch     int    `json:"max_touch,omitempty"`
	MobileHint   bool   `json:"mobile_hint,omitempty"`
}

type JumpClaims struct {
	Code   string `json:"code"`
	FPHash string `json:"fp_hash"`
	jwt.RegisteredClaims
}

func (s *ChallengeService) Create(code string, needPassword, noReferrer, collectTrack bool) (*ChallengeSession, error) {
	id, err := util.RandomString(24)
	if err != nil {
		return nil, err
	}
	seed, err := util.RandomString(32)
	if err != nil {
		return nil, err
	}
	ch := ChallengeSession{
		ID: id, Code: code, Seed: seed,
		NeedPassword: needPassword, NoReferrer: noReferrer, CollectTrack: collectTrack,
	}
	raw, _ := json.Marshal(ch)
	s.kv.Set(challengeKeyPrefix+id, raw, challengeTTL)
	return &ch, nil
}

func (s *ChallengeService) Get(id string) (*ChallengeSession, bool) {
	raw, ok := s.kv.Get(challengeKeyPrefix + id)
	if !ok {
		return nil, false
	}
	var ch ChallengeSession
	if json.Unmarshal(raw, &ch) != nil {
		return nil, false
	}
	return &ch, true
}

func (s *ChallengeService) Consume(id string) {
	s.kv.Delete(challengeKeyPrefix + id)
}

type VerifyInput struct {
	ChallengeID string                 `json:"challenge_id"`
	Code        string                 `json:"code"`
	Password    string                 `json:"password"`
	Fingerprint map[string]interface{} `json:"fingerprint"`
	ClientHash  string                 `json:"client_hash"`
	MouseTrack  []MousePoint           `json:"mouse_track"`
	CacheControl string                `json:"-"`
	Pragma       string                `json:"-"`
}

type MousePoint struct {
	X int   `json:"x"`
	Y int   `json:"y"`
	T int64 `json:"t"`
}

type VerifyResult struct {
	Nonce   string `json:"nonce"`
	Whisper string `json:"whisper,omitempty"`
	Token   string `json:"-"` // JWT for Set-Cookie
	FPHash  string `json:"-"`
}

func (s *ChallengeService) Verify(in VerifyInput, linkPassword, whisper string) (*VerifyResult, error) {
	ch, ok := s.Get(in.ChallengeID)
	if !ok || ch.Code != in.Code {
		return nil, ErrChallengeGone
	}

	// DevTools "Disable cache" heuristic
	if hasNoCacheHeader(in.CacheControl, in.Pragma) {
		return nil, ErrDevToolsCache
	}

	canon, err := StableJSON(in.Fingerprint)
	if err != nil || len(canon) == 0 {
		return nil, ErrChallengeFail
	}
	expect := HMACHex(ch.Seed, canon)
	if !hmac.Equal([]byte(strings.ToLower(in.ClientHash)), []byte(strings.ToLower(expect))) {
		return nil, ErrBadHash
	}

	if ch.NeedPassword {
		if in.Password == "" || in.Password != linkPassword {
			return nil, ErrBadPassword
		}
	}

	score := ScoreBehavior(in.Fingerprint, in.MouseTrack, ch.CollectTrack)
	if score < scorePassThreshold {
		return nil, ErrChallengeFail
	}

	fpHash := SHA256Hex(canon)
	nonce, err := util.RandomString(16)
	if err != nil {
		return nil, err
	}
	ticket := Ticket{Code: ch.Code, FPHash: fpHash, NoReferrer: ch.NoReferrer}
	fillTicketClient(&ticket, in.Fingerprint)
	traw, _ := json.Marshal(ticket)
	s.kv.Set(ticketKeyPrefix+nonce, traw, ticketTTL)

	token, err := s.issueJumpJWT(ch.Code, fpHash)
	if err != nil {
		return nil, err
	}

	s.Consume(in.ChallengeID)
	return &VerifyResult{Nonce: nonce, Whisper: whisper, Token: token, FPHash: fpHash}, nil
}

func (s *ChallengeService) issueJumpJWT(code, fpHash string) (string, error) {
	claims := JumpClaims{
		Code: code, FPHash: fpHash,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(jumpJWTTTL)),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
		},
	}
	t := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return t.SignedString([]byte(s.cfg.JWTSecret))
}

func (s *ChallengeService) ParseJumpJWT(tokenStr string) (*JumpClaims, error) {
	tok, err := jwt.ParseWithClaims(tokenStr, &JumpClaims{}, func(t *jwt.Token) (interface{}, error) {
		return []byte(s.cfg.JWTSecret), nil
	})
	if err != nil {
		return nil, err
	}
	claims, ok := tok.Claims.(*JumpClaims)
	if !ok || !tok.Valid {
		return nil, ErrBadTicket
	}
	return claims, nil
}

func (s *ChallengeService) Redeem(code, nonce, sig string, claims *JumpClaims) (*Ticket, error) {
	if claims == nil || claims.Code != code {
		return nil, ErrBadTicket
	}
	raw, ok := s.kv.Take(ticketKeyPrefix + nonce)
	if !ok {
		return nil, ErrBadTicket
	}
	var ticket Ticket
	if json.Unmarshal(raw, &ticket) != nil {
		return nil, ErrBadTicket
	}
	if ticket.Code != code || ticket.FPHash != claims.FPHash {
		return nil, ErrBadTicket
	}
	expect := HMACHex(ticket.FPHash, []byte(nonce))
	if !hmac.Equal([]byte(strings.ToLower(sig)), []byte(strings.ToLower(expect))) {
		return nil, ErrBadTicket
	}
	return &ticket, nil
}

func JumpCookieName() string { return jumpCookieName }

func hasNoCacheHeader(cacheControl, pragma string) bool {
	cc := strings.ToLower(cacheControl)
	if strings.Contains(cc, "no-cache") {
		return true
	}
	return strings.EqualFold(strings.TrimSpace(pragma), "no-cache")
}

func HMACHex(key string, msg []byte) string {
	m := hmac.New(sha256.New, []byte(key))
	_, _ = m.Write(msg)
	return hex.EncodeToString(m.Sum(nil))
}

func SHA256Hex(msg []byte) string {
	sum := sha256.Sum256(msg)
	return hex.EncodeToString(sum[:])
}

// StableJSON encodes maps with sorted keys (matches frontend stableStringify).
func StableJSON(v interface{}) ([]byte, error) {
	switch x := v.(type) {
	case nil:
		return []byte("null"), nil
	case map[string]interface{}:
		keys := make([]string, 0, len(x))
		for k := range x {
			keys = append(keys, k)
		}
		sort.Strings(keys)
		var b strings.Builder
		b.WriteByte('{')
		for i, k := range keys {
			if i > 0 {
				b.WriteByte(',')
			}
			kb, _ := json.Marshal(k)
			b.Write(kb)
			b.WriteByte(':')
			vb, err := StableJSON(x[k])
			if err != nil {
				return nil, err
			}
			b.Write(vb)
		}
		b.WriteByte('}')
		return []byte(b.String()), nil
	case []interface{}:
		var b strings.Builder
		b.WriteByte('[')
		for i, item := range x {
			if i > 0 {
				b.WriteByte(',')
			}
			vb, err := StableJSON(item)
			if err != nil {
				return nil, err
			}
			b.Write(vb)
		}
		b.WriteByte(']')
		return []byte(b.String()), nil
	default:
		return json.Marshal(v)
	}
}

func ScoreBehavior(fp map[string]interface{}, track []MousePoint, collectTrack bool) int {
	score := 40
	if fp != nil {
		if _, ok := fp["canvas"]; ok {
			score += 20
		}
		if _, ok := fp["platform"]; ok {
			score += 10
		}
		if _, ok := fp["screen"]; ok {
			score += 10
		}
	}
	if !collectTrack {
		score += 20
		return score
	}
	if len(track) >= 8 {
		score += 20
	} else if len(track) >= 3 {
		score += 10
	}
	return score
}

func fillTicketClient(t *Ticket, fp map[string]interface{}) {
	if t == nil || fp == nil {
		return
	}
	if p, ok := fp["platform"].(string); ok {
		t.Platform = p
	}
	if s, ok := fp["screen"].(map[string]interface{}); ok {
		t.ScreenWidth = jsonInt(s["width"])
		t.ScreenHeight = jsonInt(s["height"])
	}
	if m, ok := fp["max_touch_points"].(float64); ok {
		t.MaxTouch = int(m)
	}
	if m, ok := fp["mobile_hint"].(bool); ok {
		t.MobileHint = m
	}
}

// ClientInfo rebuilds browser signals captured at challenge verify for visit logging.
func (t *Ticket) ClientInfo(ua string) *ClientInfo {
	if t == nil {
		return nil
	}
	return &ClientInfo{
		UserAgent:      ua,
		Platform:       t.Platform,
		ScreenWidth:    t.ScreenWidth,
		ScreenHeight:   t.ScreenHeight,
		MaxTouchPoints: t.MaxTouch,
		MobileHint:     t.MobileHint,
	}
}

func jsonInt(v interface{}) int {
	switch x := v.(type) {
	case float64:
		return int(x)
	case int:
		return x
	case json.Number:
		n, _ := x.Int64()
		return int(n)
	default:
		return 0
	}
}
