package handler

import (
	"errors"
	"net/http"

	"github.com/chauncey/shorturl/server/internal/config"
	"github.com/chauncey/shorturl/server/internal/service"
	"github.com/chauncey/shorturl/server/internal/util"
	"github.com/go-chi/chi/v5"
)

type API struct {
	cfg       config.Config
	links     *service.LinkService
	stats     *service.StatsService
	auth      *service.AuthService
	plans     *service.PlanService
	guest     *service.GuestLimitsService
	geo       *service.GeoIP
	challenge *service.ChallengeService
}

func NewAPI(
	cfg config.Config,
	links *service.LinkService,
	stats *service.StatsService,
	auth *service.AuthService,
	plans *service.PlanService,
	guest *service.GuestLimitsService,
	geo *service.GeoIP,
	challenge *service.ChallengeService,
) *API {
	return &API{
		cfg: cfg, links: links, stats: stats, auth: auth, plans: plans,
		guest: guest, geo: geo, challenge: challenge,
	}
}

func (a *API) Routes(r chi.Router) {
	r.With(a.SiteTrack).Route("/api/v1", func(r chi.Router) {
		r.Get("/health", func(w http.ResponseWriter, _ *http.Request) {
			util.OK(w, map[string]string{"status": "up"})
		})
		r.Get("/plans", a.publicPlans)

		r.Post("/auth/register", a.authRegister)
		r.Post("/auth/login", a.authLogin)

		r.With(a.OptionalAuth).Post("/links", a.createLink)
		r.Get("/links/summary", a.publicSummary)
		r.Get("/challenge/{id}", a.challengeBoot)
		r.Post("/challenge/verify", a.challengeVerify)

		r.Group(func(r chi.Router) {
			r.Use(a.UserAuth)
			r.Get("/auth/me", a.authMe)
			r.Get("/me/links", a.meListLinks)
			r.Get("/me/links/{id}", a.meGetLink)
			r.Put("/me/links/{id}", a.meUpdateLink)
			r.Delete("/me/links/{id}", a.meDeleteLink)
			r.Get("/me/links/{id}/visits", a.meLinkVisits)
		})

		r.Post("/admin/login", a.adminLogin)
		r.Group(func(r chi.Router) {
			r.Use(a.AdminAuth)
			r.Get("/admin/stats", a.adminStats)
			r.Get("/admin/links", a.adminListLinks)
			r.Get("/admin/links/{id}", a.adminGetLink)
			r.Put("/admin/links/{id}", a.adminUpdateLink)
			r.Delete("/admin/links/{id}", a.adminDeleteLink)
			r.Get("/admin/links/{id}/visits", a.adminLinkVisits)
			r.Get("/admin/users", a.adminListUsers)
			r.Get("/admin/users/{id}", a.adminGetUser)
			r.Put("/admin/users/{id}/plan", a.adminSetUserPlan)
			r.Put("/admin/users/{id}/status", a.adminSetUserStatus)
			r.Get("/admin/plans", a.adminGetPlans)
			r.Put("/admin/plans", a.adminSavePlans)
			r.Get("/admin/guest-limits", a.adminGetGuestLimits)
			r.Put("/admin/guest-limits", a.adminSaveGuestLimits)
		})
	})
}

type createBody struct {
	URL        string                 `json:"url"`
	Features   []string               `json:"features"`
	Extent     map[string]interface{} `json:"extent"`
	CustomCode string                 `json:"custom_code"`
	ExpireDays *int                   `json:"expire_days"`
	GeoPolicy  service.GeoPolicy      `json:"geo_policy"`
}

func (a *API) createLink(w http.ResponseWriter, r *http.Request) {
	var body createBody
	if err := util.DecodeJSON(r, &body); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	in := service.CreateLinkInput{
		URL: body.URL, Features: body.Features, Extent: body.Extent,
		CustomCode: body.CustomCode, ExpireDays: body.ExpireDays,
		GeoPolicy: body.GeoPolicy,
		ClientIP:  service.ClientIP(r.RemoteAddr, r.Header.Get("X-Forwarded-For"), r.Header.Get("X-Real-IP")),
	}
	if c := claimsFrom(r.Context()); c != nil {
		in.UserID = c.UserID
		in.PlanID = c.PlanID
		if user, err := a.auth.GetUser(c.UserID); err == nil {
			in.PlanID = user.PlanID
		}
	}
	res, err := a.links.Create(in)
	if err != nil {
		a.mapLinkErr(w, err)
		return
	}
	util.OK(w, res)
}

func (a *API) publicSummary(w http.ResponseWriter, _ *http.Request) {
	util.OK(w, a.links.PublicSummary())
}

func (a *API) mapLinkErr(w http.ResponseWriter, err error) {
	switch {
	case errors.Is(err, service.ErrNotFound):
		util.Fail(w, 404, "not found")
	case errors.Is(err, service.ErrForbidden):
		util.Fail(w, 403, "forbidden")
	case errors.Is(err, service.ErrQuotaExceeded):
		util.Fail(w, 403, "已达创建上限，请升级套餐")
	case errors.Is(err, service.ErrWhisperLoginRequired):
		util.Fail(w, 403, "留言功能需登录后使用")
	case errors.Is(err, service.ErrGuestCreateLimit):
		util.Fail(w, 429, "访客创建过于频繁，请稍后再试或登录后创建")
	case errors.Is(err, service.ErrGuestActiveLimit):
		util.Fail(w, 429, "当前 IP 有效短链已达上限，请登录后创建或等待过期")
	case errors.Is(err, service.ErrCustomCodeDeny):
		util.Fail(w, 403, "当前套餐不支持自定义短码")
	case errors.Is(err, service.ErrEditTargetDeny):
		util.Fail(w, 403, "当前套餐不支持修改目标地址")
	case errors.Is(err, service.ErrCodeTaken):
		util.Fail(w, 409, "短码已被占用")
	case errors.Is(err, service.ErrBadCode):
		util.Fail(w, 400, "短码格式无效（3–32 位字母数字_-）")
	case errors.Is(err, service.ErrBadExpire):
		util.Fail(w, 400, "过期设置超出套餐权益")
	case errors.Is(err, service.ErrEmptyURL), errors.Is(err, service.ErrURLTooLong):
		util.Fail(w, 400, err.Error())
	default:
		util.Fail(w, 400, err.Error())
	}
}
