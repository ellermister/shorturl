package handler

import (
	"errors"
	"net/http"
	"strconv"

	"github.com/chauncey/shorturl/server/internal/service"
	"github.com/chauncey/shorturl/server/internal/util"
	"github.com/go-chi/chi/v5"
)

func (a *API) adminLogin(w http.ResponseWriter, r *http.Request) {
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
	if res.User == nil || res.User.Role != "admin" {
		util.Fail(w, 403, "需要管理员账号")
		return
	}
	util.OK(w, res)
}

func (a *API) adminStats(w http.ResponseWriter, r *http.Request) {
	days, _ := strconv.Atoi(r.URL.Query().Get("days"))
	data, err := a.stats.Dashboard(days)
	if err != nil {
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, data)
}

func (a *API) adminListLinks(w http.ResponseWriter, r *http.Request) {
	page, _ := strconv.Atoi(r.URL.Query().Get("page"))
	pageSize, _ := strconv.Atoi(r.URL.Query().Get("page_size"))
	kw := r.URL.Query().Get("keyword")
	q := service.ListLinksQuery{Page: page, PageSize: pageSize, Keyword: kw}
	if uidStr := r.URL.Query().Get("user_id"); uidStr != "" {
		uid, err := strconv.ParseUint(uidStr, 10, 64)
		if err == nil {
			q.UserID = &uid
		}
	}
	res, err := a.links.List(q)
	if err != nil {
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, res)
}

func (a *API) adminGetLink(w http.ResponseWriter, r *http.Request) {
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
	util.OK(w, link)
}

func (a *API) adminUpdateLink(w http.ResponseWriter, r *http.Request) {
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	var body service.UpdateLinkInput
	if err := util.DecodeJSON(r, &body); err != nil {
		util.Fail(w, 400, "invalid json")
		return
	}
	link, err := a.links.UpdateAdmin(id, body)
	if err != nil {
		a.mapLinkErr(w, err)
		return
	}
	util.OK(w, link)
}

func (a *API) adminDeleteLink(w http.ResponseWriter, r *http.Request) {
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	if err := a.links.SoftDelete(id); err != nil {
		if errors.Is(err, service.ErrNotFound) {
			util.Fail(w, 404, "not found")
			return
		}
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, nil)
}

func (a *API) adminLinkVisits(w http.ResponseWriter, r *http.Request) {
	id, _ := strconv.ParseUint(chi.URLParam(r, "id"), 10, 64)
	page, _ := strconv.Atoi(r.URL.Query().Get("page"))
	pageSize, _ := strconv.Atoi(r.URL.Query().Get("page_size"))
	total, items, err := a.links.ListVisits(id, page, pageSize)
	if err != nil {
		util.Fail(w, 500, err.Error())
		return
	}
	util.OK(w, map[string]interface{}{"total": total, "items": items})
}
