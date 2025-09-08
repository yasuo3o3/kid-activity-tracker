# Kid Activity Tracker (PHP + SQLite + PWA)

- 子どもの「勉強/遊び/休憩」をワンタップ記録
- 親が「今/今日/昨日/今月」の累計を数値で確認
- **ビルド不要**：PHP + SQLiteのみ、Xserver等の共有ホスティングで動作

## デプロイ先
- 本番: https://netservice.jp/kid-activity-tracker

## セットアップ
1) `kids` に手で1レコード追加（SQL例）  
   ```sql
   -- SQLiteが新規なら最初にアクセスした時点でテーブルは作成済み
   -- kid_id（UUID）は任意で生成してください
   INSERT INTO kids (id, display_name) VALUES ('<UUID>', 'やすお君');
   ```
2) ブラウザでトップを開き、kid_idにUUIDを保存 → ボタンで切替
3) 親端末で開き、今日 の表示が増えることを確認

## API
- `POST /api/switch.php { kid_id, label }`
- `GET /api/stats.php?kid_id=...`

※ Alexa連携は AWS Lambda から switch.php をPOSTで呼ぶのが簡単です。
