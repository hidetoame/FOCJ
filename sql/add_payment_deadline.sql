-- membership_feesテーブルに支払期限カラムを追加
ALTER TABLE membership_fees 
ADD COLUMN IF NOT EXISTS entry_fee_payment_deadline DATE,
DROP COLUMN IF EXISTS entry_fee_receipt_number;

-- 既存データに対してデフォルトの支払期限を設定（作成日から30日後）
UPDATE membership_fees 
SET entry_fee_payment_deadline = DATE(created_at) + INTERVAL '30 days'
WHERE entry_fee_payment_deadline IS NULL;