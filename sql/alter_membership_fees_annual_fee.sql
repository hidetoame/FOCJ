-- membership_feesテーブルのannual_feeカラムを配列型に変更
-- 年度と金額のペアを格納するJSONB型を使用

-- 既存のannual_feeカラムを一時的にリネーム
ALTER TABLE membership_fees 
RENAME COLUMN annual_fee TO annual_fee_old;

-- 新しいannual_fee列をJSONB型で追加（年度別の年会費を配列で管理）
ALTER TABLE membership_fees 
ADD COLUMN annual_fee JSONB DEFAULT '[]'::jsonb;

-- 既存データを新形式に移行（例：現在の金額を2025年度として設定）
UPDATE membership_fees 
SET annual_fee = jsonb_build_array(
    jsonb_build_object(
        'year', 2025,
        'amount', annual_fee_old,
        'status', '未払い',
        'payment_date', NULL,
        'receipt_number', NULL
    )
)
WHERE annual_fee_old IS NOT NULL;

-- 古いカラムを削除
ALTER TABLE membership_fees 
DROP COLUMN annual_fee_old;

-- コメントを追加
COMMENT ON COLUMN membership_fees.annual_fee IS '年会費配列 - 年度別の年会費情報をJSON配列で管理 [{year: 2025, amount: 50000, status: "支払い済み", payment_date: "2025-01-01", receipt_number: "R2025001"}]';

-- サンプルデータの確認用クエリ
-- 年会費を追加する例
/*
-- 2025年の年会費を追加
UPDATE membership_fees 
SET annual_fee = annual_fee || '[{"year": 2025, "amount": 50000, "status": "未払い"}]'::jsonb
WHERE member_id = 1;

-- 2026年の年会費を追加
UPDATE membership_fees 
SET annual_fee = annual_fee || '[{"year": 2026, "amount": 50000, "status": "未払い"}]'::jsonb
WHERE member_id = 1;

-- 特定年度の年会費を更新（2025年を支払い済みに）
UPDATE membership_fees 
SET annual_fee = jsonb_set(
    annual_fee,
    '{0}',  -- 配列の最初の要素
    '{"year": 2025, "amount": 50000, "status": "支払い済み", "payment_date": "2025-03-15", "receipt_number": "R2025001"}'::jsonb
)
WHERE member_id = 1 
  AND annual_fee @> '[{"year": 2025}]'::jsonb;
*/

-- 年会費情報を取得する便利な関数を作成
CREATE OR REPLACE FUNCTION get_annual_fee_by_year(fee_data JSONB, target_year INTEGER)
RETURNS JSONB AS $$
DECLARE
    fee_record JSONB;
BEGIN
    FOR fee_record IN SELECT * FROM jsonb_array_elements(fee_data)
    LOOP
        IF (fee_record->>'year')::int = target_year THEN
            RETURN fee_record;
        END IF;
    END LOOP;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- 年会費の支払い状況を確認するビュー
CREATE OR REPLACE VIEW v_membership_fees_with_annual AS
SELECT 
    mf.fee_id,
    mf.member_id,
    m.member_number,
    m.family_name || ' ' || m.first_name as member_name,
    mf.membership_type,
    mf.entry_fee,
    mf.payment_status as entry_fee_status,
    mf.annual_fee,
    -- 2025年の年会費情報を抽出
    get_annual_fee_by_year(mf.annual_fee, 2025) as fee_2025,
    -- 2026年の年会費情報を抽出
    get_annual_fee_by_year(mf.annual_fee, 2026) as fee_2026,
    mf.created_at,
    mf.updated_at
FROM membership_fees mf
LEFT JOIN members m ON mf.member_id = m.member_id;

-- 年会費の追加・更新用の便利な関数
CREATE OR REPLACE FUNCTION add_or_update_annual_fee(
    p_member_id INTEGER,
    p_year INTEGER,
    p_amount NUMERIC,
    p_status VARCHAR DEFAULT '未払い',
    p_payment_date DATE DEFAULT NULL,
    p_receipt_number VARCHAR DEFAULT NULL
)
RETURNS VOID AS $$
DECLARE
    current_fees JSONB;
    new_fee JSONB;
    fee_index INTEGER := -1;
    i INTEGER := 0;
    fee_record JSONB;
BEGIN
    -- 現在の年会費データを取得
    SELECT annual_fee INTO current_fees
    FROM membership_fees
    WHERE member_id = p_member_id;
    
    -- 新しい年会費データを作成
    new_fee := jsonb_build_object(
        'year', p_year,
        'amount', p_amount,
        'status', p_status,
        'payment_date', p_payment_date,
        'receipt_number', p_receipt_number
    );
    
    -- 既存の年度データがあるか確認
    IF current_fees IS NOT NULL THEN
        FOR fee_record IN SELECT * FROM jsonb_array_elements(current_fees)
        LOOP
            IF (fee_record->>'year')::int = p_year THEN
                fee_index := i;
                EXIT;
            END IF;
            i := i + 1;
        END LOOP;
    ELSE
        current_fees := '[]'::jsonb;
    END IF;
    
    -- 更新または追加
    IF fee_index >= 0 THEN
        -- 既存の年度データを更新
        current_fees := jsonb_set(current_fees, array[fee_index::text], new_fee);
    ELSE
        -- 新しい年度データを追加
        current_fees := current_fees || jsonb_build_array(new_fee);
    END IF;
    
    -- データベースを更新
    UPDATE membership_fees
    SET annual_fee = current_fees,
        updated_at = NOW()
    WHERE member_id = p_member_id;
END;
$$ LANGUAGE plpgsql;

-- 使用例
/*
-- 会員ID 1に2025年の年会費を追加
SELECT add_or_update_annual_fee(1, 2025, 50000, '未払い');

-- 会員ID 1の2025年の年会費を支払い済みに更新
SELECT add_or_update_annual_fee(1, 2025, 50000, '支払い済み', '2025-03-15', 'R2025001');

-- 会員ID 1に2026年の年会費を追加
SELECT add_or_update_annual_fee(1, 2026, 55000, '未払い');
*/