package handler

import (
	"errors"
	"net/http"
	"strconv"

	"github.com/chauncey/shorturl/server/internal/model"
	"github.com/chauncey/shorturl/server/internal/service"
	"github.com/chauncey/shorturl/server/internal/util"
	"github.com/go-chi/chi/v5"
)

func (a *API) authRegister(w http.ResponseWriter, r *http.Request) {
	var body struct {
		Username string `json:"username"`
		Password string `json:"password"`
	}
	if err := util.DecodeJSON(r, &body); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	res, err := a.auth.Register(body.Username, body.Password)
	if err != nil {
		switch {
		case errors.Is(err, service.ErrUserExists):
			util.Fail(w, 409, "用户名已存在")
		case errors.Is(err, service.ErrWeakPassword):
			util.Fail(w, 400, "密码至少 6 位")
		case errors.Is(err, service.ErrInvalidUsername):
			util.Fail(w, 400, "用户名长度需为 3–32")
		default:
			util.Fail(w, 500, err.Error())
		}
		return
	}
	util.OK(w, res)
}

func (a *API) authLogin(w http.ResponseWriter, r *http.Request) {
	var body struct {
		Username string `json:"username"`
		Password string `json:"password"`
	}
	if err := util.DecodeJSON(r, &body); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	res, err := a.auth.Login(body.Username, body.Password)
	if err != nil {
		if errors.Is(err, service.ErrAuthFailed) {
			util.Fail(w, 401, "用户名或密码错误")
			return
		}
		if errors.Is(err, service.ErrUserDisabled) {
			util.Fail(w, 403, "账号已禁用")
			return
		}
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, res)
}

func (a *API) authMe(w http.ResponseWriter, r *http.Request) {
	c := claimsFrom(r.Context())
	user, err := a.auth.GetUser(c.UserID)
	if err != nil {
		util.Fail(w, 401, "unauthorized")
		return
	}
	plan, _ := a.plans.GetPlan(user.PlanID)
	used, _ := a.links.CountUserLinks(user.ID)
	util.OK(w, map[string]interface{}{
		"user":       user,
		"plan":       plan,
		"links_used": used,
	})
}

func (a *API) publicPlans(w http.ResponseWriter, _ *http.Request) {
	util.OK(w, a.plans.Get())
}

func (a *API) meListLinks(w http.ResponseWriter, r *http.Request) {
	c := claimsFrom(r.Context())
	uid := c.UserID
	page, _ := strconv.Atoi(r.URL.Query().Get("page"))
	pageSize, _ := strconv.Atoi(r.URL.Query().Get("page_size"))
	kw := r.URL.Query().Get("keyword")
	res, err := a.links.List(service.ListLinksQuery{
		Page: page, PageSize: pageSize, Keyword: kw, UserID: &uid,
	})
	if err != nil {
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, res)
}

func (a *API) meGetLink(w http.ResponseWriter, r *http.Request) {
	c := claimsFrom(r.Context())
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	link, err := a.links.GetByID(id)
	if err != nil {
		if errors.Is(err, service.ErrNotFound) {
			util.Fail(w, 404, "not found")
			return
		}
		util.Fail(w, 500, err.Error())
		return
	}
	if link.UserID != c.UserID {
		util.Fail(w, 403, "forbidden")
		return
	}
	util.OK(w, link)
}

func (a *API) meUpdateLink(w http.ResponseWriter, r *http.Request) {
	c := claimsFrom(r.Context())
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	var body service.UpdateLinkInput
	if err := util.DecodeJSON(r, &body); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	planID := c.PlanID
	if user, err := a.auth.GetUser(c.UserID); err == nil {
		planID = user.PlanID
	}
	link, err := a.links.UpdateOwned(id, c.UserID, planID, body)
	if err != nil {
		a.mapLinkErr(w, err)
		return
	}
	util.OK(w, link)
}

func (a *API) meDeleteLink(w http.ResponseWriter, r *http.Request) {
	c := claimsFrom(r.Context())
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	if err := a.links.SoftDeleteOwned(id, c.UserID); err != nil {
		a.mapLinkErr(w, err)
		return
	}
	util.OK(w, nil)
}

func (a *API) meLinkVisits(w http.ResponseWriter, r *http.Request) {
	c := claimsFrom(r.Context())
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	link, err := a.links.GetByID(id)
	if err != nil {
		a.mapLinkErr(w, err)
		return
	}
	if link.UserID != c.UserID {
		util.Fail(w, 403, "forbidden")
		return
	}
	page, _ := strconv.Atoi(r.URL.Query().Get("page"))
	pageSize, _ := strconv.Atoi(r.URL.Query().Get("page_size"))
	total, items, err := a.links.ListVisits(id, page, pageSize)
	if err != nil {
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, map[string]interface{}{"total": total, "items": items})
}

// --- admin users / plans ---

func (a *API) adminListUsers(w http.ResponseWriter, r *http.Request) {
	page, _ := strconv.Atoi(r.URL.Query().Get("page"))
	pageSize, _ := strconv.Atoi(r.URL.Query().Get("page_size"))
	kw := r.URL.Query().Get("keyword")
	total, items, err := a.auth.ListUsers(service.ListUsersQuery{Page: page, PageSize: pageSize, Keyword: kw})
	if err != nil {
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, map[string]interface{}{"total": total, "items": items})
}

func (a *API) adminGetUser(w http.ResponseWriter, r *http.Request) {
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	user, err := a.auth.GetUser(id)
	if err != nil {
		if errors.Is(err, service.ErrNotFound) {
			util.Fail(w, 404, "user not found")
			return
		}
		util.Fail(w, 500, err.Error())
		return
	}
	plan, _ := a.plans.GetPlan(user.PlanID)
	util.OK(w, map[string]interface{}{"user": user, "plan": plan})
}

func (a *API) adminGetGuestLimits(w http.ResponseWriter, _ *http.Request) {
	util.OK(w, a.guest.Get())
}

func (a *API) adminSaveGuestLimits(w http.ResponseWriter, r *http.Request) {
	var cfg service.GuestLimits
	if err := util.DecodeJSON(r, &cfg); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	if err := a.guest.Save(cfg); err != nil {
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, a.guest.Get())
}

func (a *API) adminSetUserPlan(w http.ResponseWriter, r *http.Request) {
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	var body struct {
		PlanID string `json:"plan_id"`
	}
	if err := util.DecodeJSON(r, &body); err != nil || body.PlanID == "" {
		util.Fail(w, 400, "plan_id required")
		return
	}
	if err := a.auth.SetUserPlan(id, body.PlanID); err != nil {
		if errors.Is(err, service.ErrNotFound) {
			util.Fail(w, 404, "user not found")
			return
		}
		util.Fail(w, 400, err.Error())
		return
	}
	util.OK(w, nil)
}

func (a *API) adminSetUserStatus(w http.ResponseWriter, r *http.Request) {
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	var body struct {
		Status int `json:"status"`
	}
	if err := util.DecodeJSON(r, &body); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	if body.Status != model.UserStatusActive && body.Status != model.UserStatusDisabled {
		util.Fail(w, 400, "invalid status")
		return
	}
	if err := a.auth.SetUserStatus(id, body.Status); err != nil {
		if errors.Is(err, service.ErrNotFound) {
			util.Fail(w, 404, "user not found")
			return
		}
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, nil)
}

func (a *API) adminGetPlans(w http.ResponseWriter, _ *http.Request) {
	util.OK(w, a.plans.Get())
}

func (a *API) adminSavePlans(w http.ResponseWriter, r *http.Request) {
	var cfg service.PlansConfig
	if err := util.DecodeJSON(r, &cfg); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	if len(cfg.Plans) == 0 {
		util.Fail(w, 400, "plans required")
		return
	}
	if err := a.plans.Save(cfg); err != nil {
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, a.plans.Get())
}
