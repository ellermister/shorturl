# syntax=docker/dockerfile:1

# ---- frontend ----
FROM node:22-bookworm AS web
WORKDIR /web
RUN corepack enable
COPY web/package.json web/pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile
COPY web/ ./
RUN pnpm run build

# ---- backend ----
FROM golang:1.26-bookworm AS build
WORKDIR /src
RUN apt-get update \
  && apt-get install -y --no-install-recommends gcc libc6-dev \
  && rm -rf /var/lib/apt/lists/*

COPY server/go.mod server/go.sum ./
RUN go mod download

COPY server/ ./
# Replace placeholder dist with the Vite production build (go:embed).
RUN rm -rf internal/webui/dist
COPY --from=web /web/dist ./internal/webui/dist

ENV CGO_ENABLED=1
RUN go build -trimpath -ldflags="-s -w" -o /out/shorturl ./cmd/server

# ---- runtime ----
FROM debian:bookworm-slim
RUN apt-get update \
  && apt-get install -y --no-install-recommends ca-certificates curl \
  && rm -rf /var/lib/apt/lists/* \
  && useradd --system --uid 10001 --home /app --shell /usr/sbin/nologin shorturl

WORKDIR /app

COPY --from=build /out/shorturl /app/shorturl
COPY server/scripts/download-ip2region.sh /app/download-ip2region.sh

RUN mkdir -p /app/data \
  && IP2REGION_OUT=/app/data/ip2region_v4.xdb \
     bash -c 'curl -fsSL -o "$IP2REGION_OUT" \
       https://github.com/lionsoul2014/ip2region/raw/master/data/ip2region_v4.xdb' \
  && chown -R shorturl:shorturl /app \
  && chmod +x /app/shorturl /app/download-ip2region.sh

USER shorturl

ENV ADDR=:8080 \
    DB_PATH=/app/data/shorturl.db \
    IP2REGION_DB=/app/data/ip2region_v4.xdb \
    BASE_URL=http://localhost:8080 \
    CORS_ORIGINS=http://localhost:8080,http://127.0.0.1:8080

EXPOSE 8080
VOLUME ["/app/data"]

CMD ["/app/shorturl"]
