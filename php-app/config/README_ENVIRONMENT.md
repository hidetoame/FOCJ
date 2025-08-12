# 環境設定ガイド

## 概要
このアプリケーションは環境設定ファイルを使用して、開発環境と本番環境でのパスを柔軟に管理できるようになっています。

## 設定手順

### 1. 環境設定ファイルの作成
```bash
cd php-app/config/
cp environment.example.php environment.php
```

### 2. environment.php の編集
環境に合わせて以下の2つの定数を設定してください：

```php
// ドキュメントルート（ファイルシステム上の絶対パス）
define('DOCUMENT_ROOT', '/var/www/html');

// Webルート（URLのベースパス）
define('WEB_ROOT', '');
```

## 環境別の設定例

### 本番環境（Linux）
```php
define('DOCUMENT_ROOT', '/var/www/html');
define('WEB_ROOT', '');
```

### 開発環境（XAMPP on Windows）
```php
define('DOCUMENT_ROOT', 'C:/xampp/htdocs');
define('WEB_ROOT', '');
```

### 開発環境（MAMP on Mac）
```php
define('DOCUMENT_ROOT', '/Applications/MAMP/htdocs');
define('WEB_ROOT', '');
```

### サブディレクトリにインストールする場合
```php
define('DOCUMENT_ROOT', '/var/www/html');
define('WEB_ROOT', '/focj_admin');  // http://example.com/focj_admin/
```

## ディレクトリ構造

設定により以下のディレクトリパスが自動的に設定されます：

```
DOCUMENT_ROOT/
├── php-app/          # PHPアプリケーション
│   ├── config/       # 設定ファイル
│   ├── uploads/      # アップロードファイル
│   └── uploads/temp/ # 一時アップロード
├── templates/        # HTMLテンプレート
│   ├── registration-form/   # 登録フォーム用
│   └── member-management/    # 管理画面用
└── user_images/      # ユーザー画像
    └── temp/         # 一時保存画像
```

## 設定のテスト

設定が正しく動作しているか確認するには：

```bash
cd php-app/
php test-environment.php
```

このコマンドで：
- 設定された定数の値
- ヘルパー関数の動作
- 必要なディレクトリの存在と書き込み権限
- テンプレートファイルの存在

が確認できます。

## トラブルシューティング

### ディレクトリが見つからない
- `DOCUMENT_ROOT` が正しく設定されているか確認
- 必要なディレクトリが存在するか確認
- ディレクトリの権限を確認（Webサーバーが読み書きできる必要があります）

### 画像やCSSが表示されない
- `WEB_ROOT` が正しく設定されているか確認
- サブディレクトリで動作させる場合は、`WEB_ROOT` にパスを設定（例：`/focj_admin`）

### テンプレートが読み込めない
- `templates` ディレクトリが `DOCUMENT_ROOT` 直下に存在するか確認
- ファイルの読み取り権限を確認

## セキュリティ上の注意

- `environment.php` はGitにコミットしないでください（.gitignoreに追加済み）
- 本番環境では適切なファイル権限を設定してください
- `uploads` と `user_images` ディレクトリは書き込み可能にする必要がありますが、PHPの実行は無効にしてください