package handler

import (
	"html"
	"net/http"
	"strconv"
	"strings"

	"github.com/chauncey/shorturl/server/internal/model"
	"github.com/chauncey/shorturl/server/internal/service"
	"github.com/chauncey/shorturl/server/internal/util"
	"github.com/go-chi/chi/v5"
)

// Entry handles GET /s/{code}: normal → 302; encrypt → challenge SPA.
func (a *API) Entry(w http.ResponseWriter, r *http.Request) {
	code := chi.URLParam(r, "code")
	link, err := a.links.GetByCode(code)
	if err != nil {
		a.failOutbound(w, r, nil)
		return
	}
	if service.LinkUsable(link) != service.GateOK {
		a.failOutbound(w, r, link)
		return
	}

	ip := service.ClientIP(r.RemoteAddr, r.Header.Get("X-Forwarded-For"), r.Header.Get("X-Real-IP"))
	ua := r.UserAgent()

	if !service.IsEncryptedJump(link.Features) {
		if service.CheckUAIP(link, ua, ip, a.geo, nil) != service.GateOK {
			a.failOutbound(w, r, link)
			return
		}
		target, ok := a.resolveOutboundURL(link, ip)
		if !ok {
			a.failOutbound(w, r, link)
			return
		}
		a.links.RecordSuccessVisit(link, ip, ua, r.Referer(), nil)
		http.Redirect(w, r, target, http.StatusFound)
		return
	}

	if service.FeaturesHas(link.Features, service.FeatFakePage) && service.IsCrawler(ua) {
		a.failOutbound(w, r, link)
		return
	}
	if service.CheckUAIP(link, ua, ip, a.geo, nil) != service.GateOK {
		a.failOutbound(w, r, link)
		return
	}
	// Deny mainland/overseas early so we never start a challenge for blocked regions.
	if _, ok := a.resolveOutboundURL(link, ip); !ok {
		a.failOutbound(w, r, link)
		return
	}

	ch, err := a.challenge.Create(
		link.Code,
		service.FeaturesHas(link.Features, service.FeatPassword),
		service.FeaturesHas(link.Features, service.FeatDynamic),
		true,
	)
	if err != nil {
		util.Fail(w, 500, "challenge create failed")
		return
	}
	// Redirect into SPA so Vite HMR and production embed both load their own assets.
	// Seed is fetched via GET /api/v1/challenge/{id} (not embedded in HTML).
	http.Redirect(w, r, "/challenge?c="+ch.ID, http.StatusFound)
}

// challengeBoot returns challenge public fields for the SPA (seed included).
func (a *API) challengeBoot(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	ch, ok := a.challenge.Get(id)
	if !ok {
		util.Fail(w, 404, "challenge expired")
		return
	}
	w.Header().Set("Cache-Control", "no-store")
	util.OK(w, map[string]interface{}{
		"challenge_id":  ch.ID,
		"code":          ch.Code,
		"seed":          ch.Seed,
		"need_password": ch.NeedPassword,
		"collect_track": ch.CollectTrack,
	})
}

// JumpOut handles GET /j/{code}?sig=&n=
func (a *API) JumpOut(w http.ResponseWriter, r *http.Request) {
	code := chi.URLParam(r, "code")
	sig := r.URL.Query().Get("sig")
	nonce := r.URL.Query().Get("n")
	cookie, err := r.Cookie(service.JumpCookieName())
	if err != nil || sig == "" || nonce == "" {
		a.failOutbound(w, r, nil)
		return
	}
	claims, err := a.challenge.ParseJumpJWT(cookie.Value)
	if err != nil {
		a.failOutbound(w, r, nil)
		return
	}
	ticket, err := a.challenge.Redeem(code, nonce, sig, claims)
	if err != nil {
		a.failOutbound(w, r, nil)
		return
	}
	link, err := a.links.GetByCode(code)
	if err != nil || service.LinkUsable(link) != service.GateOK {
		a.failOutbound(w, r, link)
		return
	}

	ip := service.ClientIP(r.RemoteAddr, r.Header.Get("X-Forwarded-For"), r.Header.Get("X-Real-IP"))
	ua := r.UserAgent()
	target, ok := a.resolveOutboundURL(link, ip)
	if !ok {
		a.failOutbound(w, r, link)
		return
	}
	a.links.RecordSuccessVisit(link, ip, ua, r.Referer(), ticket.ClientInfo(ua))

	http.SetCookie(w, &http.Cookie{
		Name: service.JumpCookieName(), Value: "", Path: "/j", MaxAge: -1,
	})

	if ticket.NoReferrer {
		writeNoReferrerHTML(w, target)
		return
	}
	writeReferrerHTML(w, code, target)
}

func writeNoReferrerHTML(w http.ResponseWriter, target string) {
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Header().Set("Cache-Control", "no-store")
	esc := html.EscapeString(target)
	_, _ = w.Write([]byte(`<!doctype html><html><head><meta charset="utf-8"></head><body>
<a id="g" rel="noreferrer" href="` + esc + `"></a>
<script>document.getElementById("g").click()</script>
</body></html>`))
}

func writeReferrerHTML(w http.ResponseWriter, code, target string) {
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Header().Set("Cache-Control", "no-store")
	_, _ = w.Write([]byte(`<!doctype html><html><head>
<meta charset="utf-8">
<meta name="referrer" content="no-referrer-when-downgrade">
</head><body><script>
history.replaceState(null,"",` + strconv.Quote("/s/"+code) + `);
location.replace(` + strconv.Quote(target) + `);
</script></body></html>`))
}

// resolveOutboundURL applies Require + geo rules. ok=false means visitor denied.
func (a *API) resolveOutboundURL(link *model.ShortLink, ip string) (string, bool) {
	region := service.RegionInfo{}
	if a.geo != nil {
		region = a.geo.Lookup(ip)
	}
	d := service.ResolveOutbound(link, region)
	if d.Denied || strings.TrimSpace(d.URL) == "" {
		return "", false
	}
	return d.URL, true
}

// failOutbound prefers GeoPolicy.fallback_url, then fake decoy, then 404.
func (a *API) failOutbound(w http.ResponseWriter, r *http.Request, link *model.ShortLink) {
	if link != nil {
		if u := service.GeoFallbackURL(link); u != "" {
			http.Redirect(w, r, u, http.StatusFound)
			return
		}
		if service.FeaturesHas(link.Features, service.FeatFakePage) {
			http.Redirect(w, r, service.FakeDecoyURL(), http.StatusFound)
			return
		}
	}
	http.NotFound(w, r)
}

func (a *API) challengeVerify(w http.ResponseWriter, r *http.Request) {
	var body service.VerifyInput
	if err := util.DecodeJSON(r, &body); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	body.CacheControl = r.Header.Get("Cache-Control")
	body.Pragma = r.Header.Get("Pragma")

	link, err := a.links.GetByCode(body.Code)
	if err != nil || service.LinkUsable(link) != service.GateOK {
		a.respondChallengeFail(w, link)
		return
	}

	ip := service.ClientIP(r.RemoteAddr, r.Header.Get("X-Forwarded-For"), r.Header.Get("X-Real-IP"))
	client := fingerprintToClient(body.Fingerprint, r.UserAgent())
	if service.CheckUAIP(link, r.UserAgent(), ip, a.geo, client) != service.GateOK {
		a.respondChallengeFail(w, link)
		return
	}
	if _, ok := a.resolveOutboundURL(link, ip); !ok {
		a.respondChallengeFail(w, link)
		return
	}

	res, err := a.challenge.Verify(body, link.Password, link.Whisper)
	if err != nil {
		if err == service.ErrBadPassword {
			util.Fail(w, 403, "bad password")
			return
		}
		a.respondChallengeFail(w, link)
		return
	}

	secure := r.TLS != nil || r.Header.Get("X-Forwarded-Proto") == "https"
	http.SetCookie(w, &http.Cookie{
		Name:     service.JumpCookieName(),
		Value:    res.Token,
		Path:     "/j",
		HttpOnly: true,
		Secure:   secure,
		SameSite: http.SameSiteLaxMode,
		MaxAge:   30,
	})
	w.Header().Set("Cache-Control", "no-store")
	out := map[string]string{"nonce": res.Nonce}
	if res.Whisper != "" {
		out["whisper"] = res.Whisper
	}
	util.OK(w, out)
}

func (a *API) respondChallengeFail(w http.ResponseWriter, link *model.ShortLink) {
	if link != nil {
		if u := service.GeoFallbackURL(link); u != "" {
			util.OK(w, map[string]string{"action": "fake", "url": u})
			return
		}
		if service.FeaturesHas(link.Features, service.FeatFakePage) {
			util.OK(w, map[string]string{"action": "fake", "url": service.FakeDecoyURL()})
			return
		}
	}
	util.Fail(w, 403, "challenge failed")
}

func fingerprintToClient(fp map[string]interface{}, ua string) *service.ClientInfo {
	if fp == nil {
		return nil
	}
	c := &service.ClientInfo{UserAgent: ua}
	if p, ok := fp["platform"].(string); ok {
		c.Platform = p
	}
	if s, ok := fp["screen"].(map[string]interface{}); ok {
		c.ScreenWidth = toInt(s["width"])
		c.ScreenHeight = toInt(s["height"])
	}
	if m, ok := fp["max_touch_points"].(float64); ok {
		c.MaxTouchPoints = int(m)
	}
	if m, ok := fp["mobile_hint"].(bool); ok {
		c.MobileHint = m
	}
	return c
}

func toInt(v interface{}) int {
	switch x := v.(type) {
	case float64:
		return int(x)
	case int:
		return x
	default:
		return 0
	}
}
