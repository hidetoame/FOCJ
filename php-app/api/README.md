# FOCJ 外部連携API仕様書

## 概要
FOCJ会員管理システムの外部連携用APIです。
決済システムや外部サービスとの連携に使用します。

## エンドポイント一覧

### 1. 入会金決済API
**URL:** `/api/payment-entry-fee.php`  
**メソッド:** POST  
**Content-Type:** application/json

#### リクエスト
```json
{
  "user_id": 17,
  "email": "test@example.com",
  "amount": 10000,
  "payment_method": "credit_card"
}
```

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| user_id | integer | ○ | ユーザーID |
| email | string | ○ | メールアドレス |
| amount | number | ○ | 決済金額 |
| payment_method | string | ○ | 決済方法（credit_card, bank_transfer, cash） |

#### レスポンス（成功時）
```json
{
  "status": "success",
  "user_id": 17,
  "email": "test@example.com",
  "amount": 10000,
  "payment_date": "2025-01-15 10:30:00"
}
```

#### レスポンス（失敗時）
```json
{
  "status": "failure",
  "error": "User not found",
  "user_id": 17,
  "email": "test@example.com"
}
```

---

### 2. 年会費決済API
**URL:** `/api/payment-annual-fee.php`  
**メソッド:** POST  
**Content-Type:** application/json

#### リクエスト
```json
{
  "user_id": 17,
  "email": "test@example.com",
  "amount": 20000,
  "payment_method": "bank_transfer",
  "year": 2025
}
```

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| user_id | integer | ○ | ユーザーID |
| email | string | ○ | メールアドレス |
| amount | number | ○ | 決済金額 |
| payment_method | string | ○ | 決済方法（credit_card, bank_transfer, cash） |
| year | integer | - | 対象年度（未指定時は現在年） |

#### レスポンス（成功時）
```json
{
  "status": "success",
  "user_id": 17,
  "email": "test@example.com",
  "amount": 20000,
  "year": 2025,
  "payment_date": "2025-01-15 10:30:00"
}
```

#### レスポンス（失敗時）
```json
{
  "status": "failure",
  "error": "User not approved",
  "user_id": 17,
  "email": "test@example.com"
}
```

---

### 3. 正規会員確認API
**URL:** `/api/verify-member.php`  
**メソッド:** POST  
**Content-Type:** application/json

#### リクエスト
```json
{
  "email": "test@example.com",
  "phone": "090-1234-5678"
}
```

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| email | string | ○ | メールアドレス |
| phone | string | ○ | 電話番号（携帯電話番号） |

#### レスポンス（会員の場合）
```json
{
  "member_exists": true,
  "member_number": "FOCJ-00017",
  "member_name": "山田 太郎",
  "is_active": true
}
```

#### レスポンス（非会員の場合）
```json
{
  "member_exists": false,
  "message": "Member not found"
}
```

#### レスポンス（会員だが会費未払いの場合）
```json
{
  "member_exists": true,
  "member_number": "FOCJ-00017",
  "member_name": "山田 太郎",
  "is_active": false,
  "warnings": [
    "Entry fee not paid",
    "Current year fee not paid"
  ]
}
```

---

## エラーコード

| HTTPステータス | 説明 |
|---------------|------|
| 200 | 成功 |
| 400 | リクエストパラメータエラー |
| 404 | ユーザーが見つからない |
| 405 | メソッドが許可されていない |
| 500 | サーバーエラー |

## 認証
現在のバージョンでは認証は実装されていません。
本番環境では以下の認証方式の実装を推奨します：
- APIキー認証
- OAuth 2.0
- JWT認証

## CORS設定
すべてのAPIでCORSが有効になっています。
本番環境では適切なOrigin制限を設定してください。

## テスト
`/api/test-api.html` をブラウザで開いてAPIのテストが可能です。

## セキュリティ考慮事項
1. HTTPSでの通信を必須とする
2. APIキーやトークンによる認証を実装する
3. レート制限を設定する
4. IPアドレス制限を検討する
5. ログを適切に記録する
6. SQLインジェクション対策（パラメータバインディング実装済み）