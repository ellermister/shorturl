package util

import (
	"encoding/json"
	"net/http"
)

type Body struct {
	Msg  string      `json:"msg"`
	Code int         `json:"code"`
	Data interface{} `json:"data"`
}

func JSON(w http.ResponseWriter, code int, msg string, data interface{}) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(http.StatusOK)
	_ = json.NewEncoder(w).Encode(Body{Msg: msg, Code: code, Data: data})
}

func OK(w http.ResponseWriter, data interface{}) {
	JSON(w, 200, "ok", data)
}

func Fail(w http.ResponseWriter, code int, msg string) {
	JSON(w, code, msg, nil)
}

func DecodeJSON(r *http.Request, dst interface{}) error {
	defer r.Body.Close()
	dec := json.NewDecoder(r.Body)
	dec.DisallowUnknownFields()
	return dec.Decode(dst)
}
