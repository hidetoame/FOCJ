# Cloud Run デプロイメントガイド

## 概要
このドキュメントでは、FOCJアプリケーションをGoogle Cloud Runにデプロイする手順を説明します。

## アーキテクチャ
- **Cloud Run**: PHPアプリケーションのホスティング
- **Cloud SQL**: PostgreSQLデータベース
- **Cloud Storage**: 画像ファイルの保存
- **Secret Manager**: データベースパスワードの管理

## 前提条件
- Google Cloud アカウント
- gcloud CLI インストール済み
- プロジェクトの作成済み

## セットアップ手順

### 1. GCPプロジェクトの準備
```bash
# プロジェクトIDを設定
export PROJECT_ID="your-project-id"
gcloud config set project $PROJECT_ID

# 必要なAPIを有効化
gcloud services enable \
    cloudbuild.googleapis.com \
    run.googleapis.com \
    sqladmin.googleapis.com \
    storage-api.googleapis.com \
    secretmanager.googleapis.com
```

### 2. Cloud SQLのセットアップ
```bash
# インスタンス作成
gcloud sql instances create focj-db \
    --database-version=POSTGRES_14 \
    --tier=db-f1-micro \
    --region=asia-northeast1

# データベース作成
gcloud sql databases create focj_db --instance=focj-db

# ユーザー作成
gcloud sql users create focj_user \
    --instance=focj-db \
    --password=YOUR_SECURE_PASSWORD
```

### 3. Cloud Storageのセットアップ
```bash
# バケット作成
gsutil mb -p $PROJECT_ID -l asia-northeast1 gs://focj-user-images/

# 公開読み取り権限を設定（画像配信用）
gsutil iam ch allUsers:objectViewer gs://focj-user-images
```

### 4. データベースマイグレーション
```bash
# Cloud SQL Proxyをインストール
curl -o cloud_sql_proxy https://dl.google.com/cloudsql/cloud_sql_proxy.linux.amd64
chmod +x cloud_sql_proxy

# プロキシ起動
./cloud_sql_proxy -instances=$PROJECT_ID:asia-northeast1:focj-db=tcp:5432 &

# スキーマ適用
psql -h localhost -U focj_user -d focj_db < sql/schema.sql
psql -h localhost -U focj_user -d focj_db < sql/add_image_columns.sql
```

### 5. Cloud Build設定
```bash
# GitHubリポジトリと連携
gcloud builds triggers create github \
    --repo-name=FOCJ \
    --repo-owner=hidetoame \
    --branch-pattern="^main$" \
    --build-config=cloudbuild.yaml
```

### 6. 環境変数の設定
Cloud Buildトリガーで以下の変数を設定：
- `_CLOUD_SQL_CONNECTION`: プロジェクトID:リージョン:インスタンス名
- `_DB_NAME`: focj_db
- `_DB_USER`: focj_user
- `_DB_PASSWORD`: Secret Managerから参照
- `_STORAGE_BUCKET`: focj-user-images

### 7. デプロイ
```bash
# 手動デプロイ
gcloud builds submit --config cloudbuild.yaml

# または、GitHubにpushで自動デプロイ
git push origin main
```

## 画像アップロード機能の変更点

### ローカル環境との違い
1. **保存先**: `/var/www/html/user_images/` → Cloud Storage
2. **一時ファイル**: `/var/www/html/user_images/temp/` → `/tmp/`
3. **画像URL**: ローカルパス → `https://storage.googleapis.com/バケット名/`

### 必要なコード変更
画像保存部分を以下のように変更する必要があります：

```php
// ローカル（現在）
$userDir = '/var/www/html/user_images/' . $userId . '/';
move_uploaded_file($_FILES['image']['tmp_name'], $userDir . $filename);

// Cloud Storage（変更後）
use Google\Cloud\Storage\StorageClient;
$storage = new StorageClient();
$bucket = $storage->bucket('focj-user-images');
$bucket->upload(
    fopen($_FILES['image']['tmp_name'], 'r'),
    ['name' => 'user_images/' . $userId . '/' . $filename]
);
```

## 監視とログ

### ログの確認
```bash
# Cloud Runのログ
gcloud run services logs read focj-app --region=asia-northeast1

# Cloud SQLのログ
gcloud sql operations list --instance=focj-db
```

### メトリクスの監視
- Cloud Console → Cloud Run → サービス詳細
- CPU使用率、メモリ使用率、リクエスト数を確認

## トラブルシューティング

### よくある問題
1. **データベース接続エラー**
   - Cloud SQL Admin APIが有効か確認
   - Cloud SQL接続名が正しいか確認
   
2. **画像アップロードエラー**
   - Cloud Storageバケットの権限確認
   - サービスアカウントの権限確認

3. **メモリ不足**
   - Cloud Runのメモリ設定を増やす（512Mi → 1Gi）

## コスト最適化
- Cloud SQL: 開発時はdb-f1-microを使用
- Cloud Run: 最小インスタンス数を0に設定
- Cloud Storage: ライフサイクルルールで古い一時ファイルを削除

## セキュリティ
- Secret Managerでパスワード管理
- Cloud IAPで管理画面へのアクセス制限
- Cloud Armorでセキュリティルール設定