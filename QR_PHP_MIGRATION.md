# QRコード生成のPHP移行ガイド

## 概要

JavaScript（qr-grid.js + qrcode.min.js）でのQRコード描画をPHPサーバー側でのPNG生成方式に変更しました。これによりDOM生成タイミングの問題が解決され、より安定したQRコード表示が可能になります。

## 変更内容

### 新規ファイル

1. **`assets/phpqrcode.php`**
   - 軽量なQRコード生成ライブラリ
   - エラー訂正レベル L 対応
   - PNG画像出力機能

2. **`assets/qr.php`**
   - QRコードPNG生成エンドポイント
   - GET パラメータ: `text`（QR化する文字列）、`s`（サイズ 80-1024px、デフォルト180）
   - セキュリティ対策: XSS防止、入力検証、レート制限
   - キャッシュヘッダ: `Cache-Control: public, max-age=31536000, immutable`

### 変更されたファイル

1. **`assets/qr-grid.js`**
   - QRCode ライブラリ依存を削除
   - PHP エンドポイント経由での画像読み込みに変更
   - エラーハンドリング改善

2. **`index.php`**
   - `qrcode.min.js` の読み込みを削除
   - qr-grid.js の直接読み込みに変更

3. **`kid-qr-grid/kid-qr-grid.php`** (WordPressプラグイン)
   - CDN からのQRライブラリ読み込みを削除
   - HTMLクラス名を `.qr-canvas` に統一

### 削除されたファイル

- `assets/qrcode.min.js` (21KB) - 不要になったため削除

## 使用方法

### 基本的な使い方

HTMLに以下のクラスを持つ要素を配置するだけで自動的にQRコードが生成されます：

```html
<div class="qr-canvas" data-url="https://example.com" data-size="180"></div>
```

### パラメータ

- `data-url`: QRコード化するURL（必須）
- `data-size`: QRコードのサイズ（px）、80-1024の範囲、デフォルト180

### JavaScript API

```javascript
// 手動でQRコードを再スキャン・生成
window.initQRGrid();

// 特定のコンテナ内のみスキャン
window.initQRGrid(document.getElementById('my-container'));
```

## 技術仕様

### QRコード生成

- **エラー訂正レベル**: L（約7%）
- **ピクセル倍率**: `size / 45` を基準に自動調整
- **出力形式**: PNG画像
- **最大データ長**: 1000文字（セキュリティ制限）

### キャッシュ戦略

- **ブラウザキャッシュ**: 1年間（`max-age=31536000`）
- **キャッシュ無効化**: URLパラメータ変更時のみ
- **イミュータブル**: `immutable` フラグで最適化

### セキュリティ

- **XSS対策**: 入力値のHTMLエスケープ
- **CSRF対策**: GETリクエストのみ許可
- **レート制限**: 文字数・サイズ制限
- **セキュリティヘッダ**: X-Content-Type-Options, X-Frame-Options等

## トラブルシューティング

### QRコードが表示されない場合

1. **PHPエラーログを確認**
   ```bash
   tail -f /var/log/php/error.log
   ```

2. **エンドポイントの動作確認**
   ```
   https://yoursite.com/path/to/assets/qr.php?text=test&s=180
   ```

3. **ファイル権限の確認**
   ```bash
   chmod 644 assets/phpqrcode.php
   chmod 644 assets/qr.php
   ```

### パフォーマンス最適化

- **サーバーキャッシュ**: Nginx/Apache レベルでの静的ファイルキャッシュ設定
- **CDN配信**: CloudflareやAmazon CloudFrontでの画像配信
- **圧縮**: gzipでのレスポンス圧縮

## 移行の利点

1. **安定性向上**: DOM生成タイミングに依存しない
2. **軽量化**: クライアント側のJSライブラリ不要（21KB削減）
3. **キャッシュ効率**: サーバー側で生成されたPNG画像の長期キャッシュ
4. **SEO対応**: 画像として認識されるためクローラーフレンドリー
5. **アクセシビリティ**: altテキスト対応

## ライセンス

MIT License - 既存プロジェクトと同様