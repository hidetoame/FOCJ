-- membership_feesテーブルに不足しているカラムを追加
ALTER TABLE membership_fees 
ADD COLUMN IF NOT EXISTS entry_fee_payment_date DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS entry_fee_payment_method VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS entry_fee_payment_deadline DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS entry_fee_notes TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS annual_fee_payment_date DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS annual_fee_payment_method VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS annual_fee_notes TEXT DEFAULT NULL;

-- annual_feeカラムをTEXT型に変更（JSON配列を格納するため）
ALTER TABLE membership_fees MODIFY COLUMN annual_fee TEXT;

-- 既存レコードのannual_feeを初期化
UPDATE membership_fees SET annual_fee = '[]' WHERE annual_fee IS NULL OR annual_fee = '';