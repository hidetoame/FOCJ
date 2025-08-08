# Ferrari Owners' Club Japan API v2.0

Ferrari Owners' Club Japanの会員登録システムのバックエンドAPI

## 🚀 新機能

### v2.0 追加機能
- **管理画面システム**: 申請管理、会員管理、メール管理機能
- **申請ステータス管理**: 申請中→審査中→承認済み/却下のワークフロー
- **会員番号自動採番**: 承認時にFOCJ2000番台の会員番号を自動付与
- **メールテンプレート管理**: 承認通知、却下通知、案内メールのテンプレート管理
- **ファイルアップロード**: 運転免許証、車検証、名刺のアップロード機能
- **申請履歴管理**: 全てのステータス変更を履歴として記録
- **統計情報**: 申請状況の統計表示

## 📋 機能

### 一般ユーザー向け
- 会員情報の登録・取得・更新・削除
- フェラーリ車両情報の管理
- 紹介者情報の管理
- 添付書類の管理
- 会費情報の管理

### 管理者向け
- 申請一覧・詳細表示
- 申請承認・却下処理
- 会員一覧・詳細表示
- 会員情報編集
- メールテンプレート管理
- メール送信履歴管理
- 統計情報表示

## 🛠 技術スタック

- **Backend**: FastAPI 0.104.1
- **Database**: PostgreSQL
- **ORM**: SQLAlchemy 2.0.23
- **Validation**: Pydantic 2.4.2
- **Server**: Uvicorn 0.24.0

## 📦 セットアップ

### 1. 依存関係のインストール

```bash
pip install -r requirements.txt
```

### 2. データベース設定

PostgreSQLがインストールされていることを確認し、`focj_db`データベースを作成してください。

```bash
# データベース作成
PGPASSWORD=postgres psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE DATABASE focj_db;"

# テーブル作成
PGPASSWORD=postgres psql -h 127.0.0.1 -U postgres -d focj_db -f create_tables.sql
```

### 3. 環境設定

データベース接続情報は`app/database.py`で設定されています：

```python
SQLALCHEMY_DATABASE_URL = "postgresql://postgres:postgres@localhost:5432/focj_db"
```

## 🚀 起動方法

### 方法1: 統合サーバー（推奨）

ポート8020でフロントエンドとバックエンドの両方を配信：

```bash
python start_server.py
```

アクセス先：
- 🌐 メインサイト: http://localhost:8020
- 📝 会員登録フォーム: http://localhost:8020/registration-form
- 🔧 管理者画面: http://localhost:8020/admin
- 📚 API ドキュメント: http://localhost:8020/docs

### 方法2: 分離サーバー

#### フロントエンドサーバー（ポート8010）
```bash
python start_frontend.py
```

#### バックエンドサーバー（ポート8020）
```bash
python start_server.py
```

## 🔐 管理者認証

- **ユーザー名**: admin
- **パスワード**: user

## 📁 プロジェクト構造

```
FOCJ_admin/
├── app/
│   ├── main.py          # FastAPIアプリケーション
│   ├── models.py        # SQLAlchemyモデル
│   ├── schemas.py       # Pydanticスキーマ
│   ├── crud.py          # CRUD操作
│   └── database.py      # データベース設定
├── registration-form/   # 一般ユーザー向けフロントエンド
├── member-management/   # 管理者向けフロントエンド
├── create_tables.sql    # テーブル作成SQL
├── database_design_v2.md # データベース設計書
├── start_server.py      # 統合サーバー起動スクリプト
├── start_frontend.py    # フロントエンドサーバー起動スクリプト
└── requirements.txt     # Python依存関係
```

## 📚 API エンドポイント

### 一般ユーザー向けAPI

- `POST /api/members/` - 会員登録
- `POST /api/upload-file/` - ファイルアップロード
- `GET /api/members/` - 会員一覧取得
- `GET /api/members/{member_id}` - 会員詳細取得
- `PUT /api/members/{member_id}` - 会員情報更新
- `DELETE /api/members/{member_id}` - 会員削除

### 管理者向けAPI

- `POST /api/admin/login` - 管理者ログイン
- `GET /api/admin/members/` - 会員一覧取得（管理者用）
- `GET /api/admin/applications/` - 申請一覧取得
- `GET /api/admin/members/{member_id}` - 会員詳細取得（管理者用）
- `PUT /api/admin/members/{member_id}/status` - 会員ステータス更新
- `GET /api/admin/statistics` - 統計情報取得
- `GET /api/admin/mail-templates/` - メールテンプレート一覧
- `POST /api/admin/mail-templates/` - メールテンプレート作成
- `PUT /api/admin/mail-templates/{template_id}` - メールテンプレート更新
- `DELETE /api/admin/mail-templates/{template_id}` - メールテンプレート削除
- `POST /api/admin/send-mail/` - メール送信
- `GET /api/admin/mail-history/` - メール送信履歴
- `GET /api/admin/search/` - 会員検索

## 🗄 データベース設計

詳細なデータベース設計は`database_design_v2.md`を参照してください。

### 主要テーブル

1. **members** - 会員基本情報
2. **ferrari_vehicles** - フェラーリ車両情報
3. **referrers** - 紹介者情報
4. **attachments** - 添付書類
5. **membership_fees** - 会費情報
6. **application_history** - 申請履歴
7. **mail_templates** - メールテンプレート
8. **mail_history** - メール送信履歴

## 🔧 開発者向け情報

### データベース接続確認

```bash
PGPASSWORD=postgres psql -h 127.0.0.1 -U postgres -d focj_db -c "\dt"
```

### ログ確認

サーバー起動時に詳細なログが表示されます。

### ホットリロード

開発中は`reload=True`が設定されているため、ファイル変更時に自動的にサーバーが再起動されます。

## 📝 注意事項

- メール送信機能はダミー実装です（実際のメール送信は行いません）
- 管理者認証は固定認証（admin/user）です
- ファイルアップロードは`uploads/`ディレクトリに保存されます
- プロジェクト以外のディレクトリは一切触りません
- GITアップロードは行いません

## 🆘 トラブルシューティング

### データベース接続エラー
- PostgreSQLが起動していることを確認
- 接続情報（ホスト、ポート、ユーザー名、パスワード）を確認
- `focj_db`データベースが存在することを確認

### ポート使用中エラー
- 他のアプリケーションがポート8010または8020を使用していないか確認
- 必要に応じてポート番号を変更

### ファイルが見つからないエラー
- フロントエンドファイル（registration-form/、member-management/）が存在することを確認
- ファイルパスが正しいことを確認

---

**バージョン**: 2.0.0  
**最終更新**: 2024年12月