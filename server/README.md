# ShortURL Server

Go 后端（chi + GORM + SQLite）。产品能力与部署见根目录 [`README.md`](../README.md)；跳转语义见 [`加密跳转.md`](../加密跳转.md)。本文面向开发与运维。

## 本地启动

```bash
# IP 库（地区限制需要）
./scripts/download-ip2region.sh

# API（默认 :8080）
go run ./cmd/server

# 另开终端：前端（默认 :5173，代理 /api、/s、/j）
cd ../web && pnpm install && pnpm run dev
```

环境变量见 [`.env.example`](.env.example)。默认管理员：`ADMIN_USER` / `ADMIN_PASS`。

生产可将 Vue 构建产物复制到 `internal/webui/dist`，由 **go:embed** 打进二进制；本地也可用 `WEB_DIST` 指向磁盘 `dist`。

```bash
cd ../web && pnpm run build
rm -rf ../server/internal/webui/dist && cp -R dist ../server/internal/webui/dist
cd ../server && go build -o bin/shorturl ./cmd/server
```

Docker 镜像：`ellermister/shorturl`（见根 README）。

## 跳转相关路径

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/s/{code}` | 入口：普通 → 检查后 302；加密 → 进入挑战页 |
| GET | `/api/v1/challenge/{id}` | 挑战引导（seed 等公开字段） |
| POST | `/api/v1/challenge/verify` | 校验指纹 / 密码 / 环境；发 Cookie + nonce |
| GET | `/j/{code}?sig=&n=` | 核销凭证后出站（无来源或可带来源） |

短时挑战态使用进程内 KV（带 TTL），适合单机；进程重启后未完成的挑战失效，重新打开 `/s/{code}` 即可。

## 主要 API

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/health` | 健康检查 |
| GET | `/api/v1/plans` | 公开套餐列表 |
| POST | `/api/v1/auth/register` | 注册 |
| POST | `/api/v1/auth/login` | 登录 |
| GET | `/api/v1/auth/me` | 当前用户与套餐 |
| POST | `/api/v1/links` | 创建短链（可选 Bearer；游客受 IP 配额限制） |
| GET | `/api/v1/links/summary` | 全站概要计数 |
| GET | `/api/v1/me/links` | 我的短链 |
| GET/PUT/DELETE | `/api/v1/me/links/{id}` | 短链详情 / 改目标 / 删除 |
| GET | `/api/v1/me/links/{id}/visits` | 访问明细 |
| POST | `/api/v1/admin/login` | 管理员登录 |
| GET | `/api/v1/admin/stats` | 全站统计 |
| GET/DELETE | `/api/v1/admin/links`… | 短链管理 |
| GET/PUT | `/api/v1/admin/users`… | 用户列表、详情、套餐与启停 |
| GET/PUT | `/api/v1/admin/plans` | 权益套餐 JSON |
| GET/PUT | `/api/v1/admin/guest-limits` | 匿名创建配额（单 IP 24h / 有效数） |

创建时 `features` 常见取值：`normal` / `encrypt`、`dynamic`（无来源）、`whisper`、`password`、`once`、`fake_page`、`ban_china_browser`、`pc_only` / `mobile_only`、`china_only` / `non_china_only`。留言（`whisper`）仅登录用户可用。

可选 `geo_policy`（地理路由）：

```json
{
  "require": "mainland",
  "fallback_url": "https://example.com/denied",
  "rules": [
    { "province": "广东", "city": "深圳", "isp": "telecom", "url": "https://a.example" },
    { "isp": "telecom", "url": "https://b.example" }
  ]
}
```

- `require`：`""` | `mainland` | `overseas`（快捷准入；也可由 `china_only` / `non_china_only` 推导）
- `rules`：省 / 市 / 运营商可空（空=任意）；更细规则优先；未命中用创建时的默认 `url`
- 拒绝时优先 `fallback_url`，否则伪装页 / 404

## 技术栈

| 层 | 技术 |
|----|------|
| HTTP | chi |
| 存储 | GORM + SQLite |
| 认证 | JWT |
| 地理 | [ip2region](https://github.com/lionsoul2014/ip2region) |
| 前端 | Vue 3 + Vite（`../web`） |
