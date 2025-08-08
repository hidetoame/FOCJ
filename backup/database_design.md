# FOCJ会員登録システム データベース設計書

## 概要
Ferrari Owners' Club Japan (FOCJ) の会員登録システムのデータベース設計書です。
フロントエンドテンプレートの要件に基づいて設計されています。

## データベース概要
- **データベース名**: focj_db
- **エンジン**: PostgreSQL
- **文字エンコーディング**: UTF-8

## テーブル設計

### 1. members（会員基本情報）

| カラム名 | データ型 | 制約 | 説明 |
|---------|---------|------|------|
| member_id | SERIAL | PRIMARY KEY | 会員ID（自動採番） |
| family_name | VARCHAR(50) | NOT NULL | 姓 |
| first_name | VARCHAR(50) | NOT NULL | 名 |
| family_name_kana | VARCHAR(50) | NOT NULL | 姓（フリガナ） |
| first_name_kana | VARCHAR(50) | NOT NULL | 名（フリガナ） |
| name_alphabet | VARCHAR(100) | NOT NULL | 氏名（ローマ字表記） |
| postal_code | VARCHAR(8) | NOT NULL | 郵便番号 |
| prefecture | VARCHAR(10) | NOT NULL | 都道府県 |
| city_address | TEXT | NOT NULL | 市区町村・番地 |
| building_name | TEXT | NULL | 建物名・部屋番号 |
| address_type | VARCHAR(10) | NOT NULL, CHECK | 住所種別（自宅/勤務先） |
| mobile_number | VARCHAR(20) | NOT NULL | 携帯電話番号 |
| phone_number | VARCHAR(20) | NULL | 電話番号 |
| birth_date | DATE | NOT NULL | 生年月日 |
| email | VARCHAR(255) | NOT NULL, UNIQUE | メールアドレス |
| occupation | TEXT | NOT NULL | 職業 |
| self_introduction | TEXT | NOT NULL | 自己紹介 |
| relationship_dealer | VARCHAR(50) | NULL | お付き合いのあるディーラー |
| sales_person | VARCHAR(100) | NULL | 担当セールス名 |
| privacy_agreement | BOOLEAN | NOT NULL, DEFAULT FALSE | 個人情報取り扱い同意 |
| application_status | VARCHAR(20) | NOT NULL, DEFAULT '申請中' | 申請ステータス |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 作成日時 |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 更新日時 |

**制約**:
- `address_type` CHECK制約: `address_type IN ('自宅', '勤務先')`
- `application_status` CHECK制約: `application_status IN ('申請中', '承認済み', '却下')`

### 2. ferrari_vehicles（フェラーリ車両情報）

| カラム名 | データ型 | 制約 | 説明 |
|---------|---------|------|------|
| vehicle_id | SERIAL | PRIMARY KEY | 車両ID（自動採番） |
| member_id | INTEGER | NOT NULL, FOREIGN KEY | 会員ID |
| model_name | VARCHAR(100) | NOT NULL | 車種・Model名 |
| year | INTEGER | NOT NULL | 年式 |
| color | VARCHAR(50) | NOT NULL | 車体色 |
| registration_number | VARCHAR(20) | NOT NULL | 登録No |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 作成日時 |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 更新日時 |

**外部キー制約**:
- `member_id` → `members.member_id`

### 3. referrers（紹介者情報）

| カラム名 | データ型 | 制約 | 説明 |
|---------|---------|------|------|
| referrer_id | SERIAL | PRIMARY KEY | 紹介者ID（自動採番） |
| member_id | INTEGER | NOT NULL, FOREIGN KEY | 会員ID |
| referrer_name | VARCHAR(100) | NOT NULL | 紹介者名 |
| referrer_dealer | VARCHAR(50) | NULL | 紹介者ディーラー名 |
| is_director | BOOLEAN | NOT NULL, DEFAULT FALSE | 理事フラグ |
| referrer_order | INTEGER | NOT NULL, DEFAULT 1 | 紹介者順序（1: 紹介者-1, 2: 紹介者-2） |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 作成日時 |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 更新日時 |

**外部キー制約**:
- `member_id` → `members.member_id`

### 4. attachments（添付書類）

| カラム名 | データ型 | 制約 | 説明 |
|---------|---------|------|------|
| attachment_id | SERIAL | PRIMARY KEY | 添付書類ID（自動採番） |
| member_id | INTEGER | NOT NULL, FOREIGN KEY | 会員ID |
| document_type | VARCHAR(20) | NOT NULL | 書類種別 |
| file_name | VARCHAR(255) | NOT NULL | ファイル名 |
| file_path | TEXT | NOT NULL | ファイルパス |
| file_size | INTEGER | NOT NULL | ファイルサイズ（バイト） |
| mime_type | VARCHAR(100) | NOT NULL | MIMEタイプ |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 作成日時 |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 更新日時 |

**制約**:
- `document_type` CHECK制約: `document_type IN ('運転免許証', '車検証', '名刺')`

**外部キー制約**:
- `member_id` → `members.member_id`

### 5. membership_fees（会費情報）

| カラム名 | データ型 | 制約 | 説明 |
|---------|---------|------|------|
| fee_id | SERIAL | PRIMARY KEY | 会費ID（自動採番） |
| member_id | INTEGER | NOT NULL, FOREIGN KEY | 会員ID |
| membership_type | VARCHAR(20) | NOT NULL | 会員種別 |
| entry_fee | DECIMAL(10,2) | NOT NULL | 入会金 |
| annual_fee | DECIMAL(10,2) | NOT NULL | 年会費 |
| payment_status | VARCHAR(20) | NOT NULL, DEFAULT '未払い' | 支払い状況 |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 作成日時 |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 更新日時 |

**制約**:
- `membership_type` CHECK制約: `membership_type IN ('メール会員')`
- `payment_status` CHECK制約: `payment_status IN ('未払い', '支払い済み', '支払い期限切れ')`

**外部キー制約**:
- `member_id` → `members.member_id`

### 6. application_history（申請履歴）

| カラム名 | データ型 | 制約 | 説明 |
|---------|---------|------|------|
| history_id | SERIAL | PRIMARY KEY | 履歴ID（自動採番） |
| member_id | INTEGER | NOT NULL, FOREIGN KEY | 会員ID |
| status_from | VARCHAR(20) | NOT NULL | 変更前ステータス |
| status_to | VARCHAR(20) | NOT NULL | 変更後ステータス |
| notes | TEXT | NULL | 備考 |
| processed_by | VARCHAR(100) | NULL | 処理担当者 |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | 作成日時 |

**外部キー制約**:
- `member_id` → `members.member_id`

## インデックス設計

### 主要インデックス
1. `members`テーブル
   - `idx_members_email` (email) - メールアドレス検索用
   - `idx_members_application_status` (application_status) - ステータス検索用
   - `idx_members_created_at` (created_at) - 作成日時検索用

2. `ferrari_vehicles`テーブル
   - `idx_ferrari_vehicles_member_id` (member_id) - 会員ID検索用

3. `referrers`テーブル
   - `idx_referrers_member_id` (member_id) - 会員ID検索用

4. `attachments`テーブル
   - `idx_attachments_member_id` (member_id) - 会員ID検索用
   - `idx_attachments_document_type` (document_type) - 書類種別検索用

5. `membership_fees`テーブル
   - `idx_membership_fees_member_id` (member_id) - 会員ID検索用
   - `idx_membership_fees_payment_status` (payment_status) - 支払い状況検索用

## データ整合性制約

### ビジネスルール
1. **会員情報**
   - メールアドレスは一意である必要がある
   - 生年月日は過去の日付である必要がある
   - 郵便番号は7桁の数字である必要がある

2. **車両情報**
   - 1人の会員は複数のフェラーリ車両を登録可能
   - 年式は現在年以前である必要がある

3. **紹介者情報**
   - 1人の会員に対して最大2名の紹介者を登録可能
   - 紹介者-1は必須、紹介者-2（理事）は任意

4. **添付書類**
   - 運転免許証と車検証は必須
   - 名刺は任意
   - ファイルサイズは100MB以下

5. **会費情報**
   - 入会金：50,000円（固定）
   - 年会費：50,000円（メール会員）

## セキュリティ考慮事項

1. **個人情報保護**
   - 個人情報は暗号化して保存
   - アクセスログの記録
   - データベース接続の暗号化

2. **ファイル管理**
   - アップロードされたファイルは安全な場所に保存
   - ファイル名のランダム化
   - ウイルススキャンの実施

3. **アクセス制御**
   - データベースユーザーの権限分離
   - 読み取り専用ユーザーの作成
   - 定期的なパスワード変更

## バックアップ戦略

1. **定期バックアップ**
   - 日次フルバックアップ
   - 1時間ごとの差分バックアップ
   - トランザクションログのバックアップ

2. **復旧テスト**
   - 月次復旧テストの実施
   - バックアップデータの整合性確認

## パフォーマンス考慮事項

1. **クエリ最適化**
   - 適切なインデックスの設定
   - 不要なJOINの回避
   - クエリ結果のキャッシュ

2. **データベース設定**
   - 適切なメモリ設定
   - コネクションプールの設定
   - クエリタイムアウトの設定

## 移行計画

1. **段階的移行**
   - 既存データの移行
   - データ整合性の確認
   - アプリケーションの段階的切り替え

2. **テスト環境**
   - 本番環境と同じ構成のテスト環境
   - 負荷テストの実施
   - セキュリティテストの実施

---

**作成日**: 2024年12月
**作成者**: AI Assistant
**バージョン**: 1.0 