package webui

import (
	"embed"
	"io/fs"
	"os"
)

//go:embed all:dist
var distEmbed embed.FS

// FS returns the frontend file tree.
// If WEB_DIST is set, use that directory (local Vite build).
// Otherwise serve the files embedded at compile time.
func FS() (fs.FS, error) {
	if dir := os.Getenv("WEB_DIST"); dir != "" {
		return os.DirFS(dir), nil
	}
	return fs.Sub(distEmbed, "dist")
}
