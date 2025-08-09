#!/bin/bash

# GCPプロジェクトのセットアップスクリプト

PROJECT_ID="your-project-id"
REGION="asia-northeast1"
DB_INSTANCE="focj-db-instance"
DB_NAME="focj_db"
DB_USER="focj_user"
BUCKET_NAME="focj-user-images"

echo "1. プロジェクトの設定..."
gcloud config set project $PROJECT_ID

echo "2. 必要なAPIを有効化..."
gcloud services enable \
    cloudbuild.googleapis.com \
    run.googleapis.com \
    sqladmin.googleapis.com \
    storage-api.googleapis.com \
    secretmanager.googleapis.com

echo "3. Cloud SQLインスタンスの作成..."
gcloud sql instances create $DB_INSTANCE \
    --database-version=MYSQL_8_0 \
    --tier=db-f1-micro \
    --region=$REGION \
    --network=default

echo "4. データベースの作成..."
gcloud sql databases create $DB_NAME \
    --instance=$DB_INSTANCE \
    --charset=utf8mb4 \
    --collation=utf8mb4_unicode_ci

echo "5. データベースユーザーの作成..."
gcloud sql users create $DB_USER \
    --instance=$DB_INSTANCE \
    --password=your-secure-password

echo "6. Cloud Storageバケットの作成..."
gsutil mb -p $PROJECT_ID -l $REGION gs://$BUCKET_NAME/

echo "7. バケットの権限設定..."
gsutil iam ch allUsers:objectViewer gs://$BUCKET_NAME

echo "8. Secret Managerでパスワードを保存..."
echo -n "your-secure-password" | gcloud secrets create db-password --data-file=-

echo "9. Cloud Buildサービスアカウントに権限付与..."
PROJECT_NUMBER=$(gcloud projects describe $PROJECT_ID --format='value(projectNumber)')
gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member=serviceAccount:$PROJECT_NUMBER@cloudbuild.gserviceaccount.com \
    --role=roles/run.admin

gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member=serviceAccount:$PROJECT_NUMBER@cloudbuild.gserviceaccount.com \
    --role=roles/cloudsql.client

gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member=serviceAccount:$PROJECT_NUMBER@cloudbuild.gserviceaccount.com \
    --role=roles/secretmanager.secretAccessor

echo "セットアップ完了!"
echo "次のステップ："
echo "1. cloudbuild.yamlの環境変数を実際の値に更新"
echo "2. git push でCloud Buildトリガーを実行"