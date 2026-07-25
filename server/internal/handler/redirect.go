package handler

import (
	"encoding/json"
	"io"
	"io/fs"
	"net/http"
	"path"
	"strings"
)

// SPAFromFS serves the Vue static build (hashed assets + SPA fallback to index.html).
func SPAFromFS(root fs.FS) http.HandlerFunc {
	fileServer := http.FileServer(http.FS(root))
	return func(w http.ResponseWriter, r *http.Request) {
		p := path.Clean("/" + r.URL.Path)
		if p == "/" {
			serveIndex(w, root)
			return
		}
		rel := strings.TrimPrefix(p, "/")
		if st, err := fs.Stat(root, rel); err == nil && !st.IsDir() {
			fileServer.ServeHTTP(w, r)
			return
		}
		serveIndex(w, root)
	}
}

func serveIndex(w http.ResponseWriter, root fs.FS) {
	f, err := root.Open("index.html")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusServiceUnavailable)
		_ = json.NewEncoder(w).Encode(map[string]string{
			"msg": "frontend not available; build web into server/internal/webui/dist or set WEB_DIST",
		})
		return
	}
	defer f.Close()
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	_, _ = io.Copy(w, f)
}
