# Kid Activity Tracker (PWA + Supabase)

子どもの「勉強/遊び/休憩」をワンタップで申告 → 親が「今日/昨日/今月」の累計を確認。通知はPushover。

## 特徴
- **でっかい3ボタン**（勉強/遊び/休憩）で誤タップ減
- **リアルタイム反映**（Supabase Realtime）
- **今日/昨日/今月** の累計だけを即表示（グラフ無し）
- **Pushover通知**：切替時に「◯◯くんが勉強を開始」などを送信
- **複数の子ども**に対応：追加・改名・アーカイブOK
- **PWA**：iPhoneホームに追加して"アプリ感覚"で使える
- **無料枠で運用可**（Supabase / Cloudflare or Vercel）

## Quickstart (2025-09, Supabase新UIで実際に動いた手順)

❗ **2025年9月時点のSupabase新UIで動作確認済み**の手順です。古い記事と画面名称が異なります。

### Supabaseプロジェクト作成
- Project Settings → API で Project URL / anon key / service_role key を取得
- Functions Secrets は `DB_URL` / `SERVICE_ROLE_KEY` / `PUSHOVER_*` を登録（`SUPABASE_` プレフィックスはNG）
- SQL Editor で DB 作成 & RLS ON
- Edge Functions は「Deploy a new function → Via Editor」から作成
- エンドポイントは `/functions/v1/<name>`

### Edge Functions API
- `POST /functions/v1/switch` … 状態切替 + Pushover通知  
- `GET  /functions/v1/stats?kid_id=...` … 今日/昨日/今月（JST基準）の秒数

### .env設定（フロント用）
```env
VITE_SUPABASE_URL=https://xxxx.supabase.co
VITE_SUPABASE_ANON_KEY=<anon key>
```

❗ service_role key と Pushover tokens は **Functions Secrets のみ** に置く（フロント禁止）

> **詳細手順は [docs/SETUP-2025-09.md](docs/SETUP-2025-09.md) を参照**

## アーキテクチャ
