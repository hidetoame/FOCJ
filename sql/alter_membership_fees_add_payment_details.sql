-- membership_feesテーブルに入会金の支払い詳細カラムを追加
-- payment_statusは既に入会金の支払いステータスとして使用中

-- 入会金の支払い日を追加
ALTER TABLE membership_fees 
ADD COLUMN IF NOT EXISTS entry_fee_payment_date TIMESTAMP;

-- 入会金の支払い方法を追加
ALTER TABLE membership_fees 
ADD COLUMN IF NOT EXISTS entry_fee_payment_method VARCHAR(50);

-- 入会金の領収書番号を追加
ALTER TABLE membership_fees 
ADD COLUMN IF NOT EXISTS entry_fee_receipt_number VARCHAR(50);

-- 入会金の備考を追加
ALTER TABLE membership_fees 
ADD COLUMN IF NOT EXISTS entry_fee_notes TEXT;

-- 支払い方法のチェック制約を追加
ALTER TABLE membership_fees 
ADD CONSTRAINT chk_entry_fee_payment_method 
CHECK (entry_fee_payment_method IN ('銀行振込', 'クレジットカード', '現金', 'その他', NULL));

-- コメントを追加
COMMENT ON COLUMN membership_fees.payment_status IS '入会金の支払いステータス（未払い/支払い済み/支払い期限切れ）';
COMMENT ON COLUMN membership_fees.entry_fee_payment_date IS '入会金の支払い日';
COMMENT ON COLUMN membership_fees.entry_fee_payment_method IS '入会金の支払い方法（銀行振込/クレジットカード/現金/その他）';
COMMENT ON COLUMN membership_fees.entry_fee_receipt_number IS '入会金の領収書番号';
COMMENT ON COLUMN membership_fees.entry_fee_notes IS '入会金に関する備考';

-- 既存のビューを更新して新しいカラムを含める
CREATE OR REPLACE VIEW v_membership_fees_with_annual AS
SELECT 
    mf.fee_id,
    mf.member_id,
    m.member_number,
    m.family_name || ' ' || m.first_name as member_name,
    mf.membership_type,
    mf.entry_fee,
    mf.payment_status as entry_fee_status,
    mf.entry_fee_payment_date,
    mf.entry_fee_payment_method,
    mf.entry_fee_receipt_number,
    mf.entry_fee_notes,
    mf.annual_fee,
    -- 2025年の年会費情報を抽出
    get_annual_fee_by_year(mf.annual_fee, 2025) as fee_2025,
    -- 2026年の年会費情報を抽出
    get_annual_fee_by_year(mf.annual_fee, 2026) as fee_2026,
    mf.created_at,
    mf.updated_at
FROM membership_fees mf
LEFT JOIN members m ON mf.member_id = m.member_id;

-- 入会金支払い処理用の便利な関数を作成
CREATE OR REPLACE FUNCTION update_entry_fee_payment(
    p_member_id INTEGER,
    p_payment_date TIMESTAMP DEFAULT NOW(),
    p_payment_method VARCHAR DEFAULT '銀行振込',
    p_receipt_number VARCHAR DEFAULT NULL,
    p_notes TEXT DEFAULT NULL
)
RETURNS VOID AS $$
BEGIN
    UPDATE membership_fees
    SET payment_status = '支払い済み',
        entry_fee_payment_date = p_payment_date,
        entry_fee_payment_method = p_payment_method,
        entry_fee_receipt_number = p_receipt_number,
        entry_fee_notes = p_notes,
        updated_at = NOW()
    WHERE member_id = p_member_id;
    
    -- 更新された行がない場合はエラー
    IF NOT FOUND THEN
        RAISE EXCEPTION '会員ID % の会費情報が見つかりません', p_member_id;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- 使用例
/*
-- 会員ID 1の入会金を支払い済みに更新
SELECT update_entry_fee_payment(
    1,                          -- member_id
    '2025-01-15 10:30:00',     -- payment_date
    '銀行振込',                 -- payment_method
    'E2025001',                -- receipt_number
    '振込確認済み'              -- notes
);

-- 簡易版（現在日時、デフォルトの支払い方法で更新）
SELECT update_entry_fee_payment(1);
*/

-- 入会金・年会費の支払い状況サマリービュー
CREATE OR REPLACE VIEW v_payment_summary AS
SELECT 
    mf.member_id,
    m.member_number,
    m.family_name || ' ' || m.first_name as member_name,
    mf.entry_fee,
    mf.payment_status as entry_fee_status,
    mf.entry_fee_payment_date,
    mf.entry_fee_receipt_number,
    jsonb_array_length(mf.annual_fee) as annual_fee_years,
    (SELECT COUNT(*) 
     FROM jsonb_array_elements(mf.annual_fee) fee 
     WHERE fee->>'status' = '支払い済み') as paid_annual_fees,
    (SELECT COUNT(*) 
     FROM jsonb_array_elements(mf.annual_fee) fee 
     WHERE fee->>'status' = '未払い') as unpaid_annual_fees
FROM membership_fees mf
LEFT JOIN members m ON mf.member_id = m.member_id
ORDER BY m.member_number;