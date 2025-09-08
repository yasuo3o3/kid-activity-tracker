# Kid Activity Tracker - セットアップガイド

## 概要
子供ごとのURL分離方式で、各子供専用の画面を提供します。

## セットアップ手順

### Step 1: 設定ファイルの準備
```bash
cp config.example.php config.php
```

### Step 2: 子供の名前を設定
`config.php` の `kids_setup` 配列を編集：
```php
'kids_setup' => [
  'ゆうた',    // 実際の子供の名前に変更
  'けいた'     // 実際の子供の名前に変更
],
```

### Step 3: 初期化の実行

#### 新規セットアップ
```
https://yourdomain.com/kid-activity-tracker/setup.php
```

#### 全データリセット（やり直し）
```
https://yourdomain.com/kid-activity-tracker/setup.php?reset=1
```

#### 後から1名追加
```
https://yourdomain.com/kid-activity-tracker/setup.php?add=三郎
```

### Step 4: 設定の完了
1. setup.php の実行結果から `'kids' => [...]` 部分をコピー
2. `config.php` の `kids` 配列に貼り付け
3. **セキュリティのため** `kids_setup` 配列を削除

### Step 5: 専用URLの作成
setup.php の出力例：
```
各子供専用のURL:
  - ゆうた: ?kid=12345678-1234-1234-1234-123456789abc
  - けいた: ?kid=87654321-4321-4321-4321-ba9876543210
```

### Step 6: PWAとして保存
各子供のスマホ/タブレットで：
1. 専用URLを開く
2. 「ホーム画面に追加」でPWAアプリ化
3. タイトルが「子供名：これから何する？」になることを確認

## トラブルシューティング

### SQLiteエラー
- PHPのSQLite拡張が必要
- 権限でSQLiteファイル作成ができない場合があります

### 通知が届かない
- `config.php` のPushover設定を確認
- アプリトークンとユーザーキーが正しいか確認

### 既存データの管理
- 全削除：`setup.php?reset=1`
- 追加のみ：`setup.php?add=名前`
- データベース直接操作も可能

## セキュリティ注意点
- `setup.php` は公開ディレクトリに配置されるため、設定完了後は削除またはアクセス制限を推奨
- `config.php` に機密情報（Pushoverトークン）が含まれるため適切に保護してください