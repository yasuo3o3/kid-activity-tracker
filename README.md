# Kid Activity Tracker (PHP + SQLite + PWA)

- 子どもの「勉強/遊び/休憩」をワンタップ記録
- 親が「今/今日/昨日/今月」の累計を数値で確認
- **ビルド不要**：PHP + SQLiteのみ、Xserver等の共有ホスティングで動作

## デプロイ先
- 本番: https://netservice.jp/kid-activity-tracker

## セットアップ

### 1. 基本設定
```bash
cp config.example.php config.php
# config.php を編集してPushoverトークンと子供の名前を設定
```

### 2. 子供データの登録
config.php の `kids_setup` 配列に子供の名前を設定：
```php
'kids_setup' => [
  '太郎',
  '花子'
],
```

### 3. 初期化実行
以下のいずれかでセットアップ：

**新規セットアップ：**
```
https://yourdomain.com/kid-activity-tracker/setup.php
```

**全リセットしてやり直し：**
```
https://yourdomain.com/kid-activity-tracker/setup.php?reset=1
```

**後から1名追加：**
```
https://yourdomain.com/kid-activity-tracker/setup.php?add=三郎
```

### 4. 設定の完了
1. setup.php の出力から `kids` 配列をコピーして config.php に貼り付け
2. セキュリティのため `kids_setup` 配列を config.php から削除
3. 各子供専用のURLをブックマークまたはPWAとして保存

### 5. 動作確認
- 子供用URL: `?kid=UUID` で各子供の専用画面
- 管理画面: `/admin.php` (開発予定)
- ボタンを押して通知が届くことを確認

## API
- `POST /api/switch.php { kid_id, label }` - 状態切替 + Pushover通知送信
- `GET /api/stats.php?kid_id=...` - 今日/昨日/今月の累計取得

## Pushover通知
- 状態切替時に「やすお君が勉強を開始しました（14:30）」形式で通知
- `config.php` で子どもの名前とPushoverトークンを設定
- 通知失敗してもAPI動作には影響なし

※ Alexa連携は AWS Lambda から switch.php をPOSTで呼ぶのが簡単です。
