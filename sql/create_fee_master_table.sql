-- 入会金・年会費マスタテーブルの作成
-- システム全体の入会金と年会費の設定を管理

-- テーブルの削除（既存の場合）
DROP TABLE IF EXISTS fee_master CASCADE;

-- 入会金・年会費マスタテーブル
CREATE TABLE fee_master (
    id INTEGER PRIMARY KEY DEFAULT 1 CHECK (id = 1), -- 単一レコード制約
    entry_fee NUMERIC(10, 2) NOT NULL DEFAULT 300000, -- 入会金（デフォルト30万円）
    annual_fees JSONB NOT NULL DEFAULT '[]'::jsonb,   -- 年度別年会費の配列
    entry_fee_description TEXT,                       -- 入会金の説明
    annual_fee_description TEXT,                       -- 年会費の説明
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_by VARCHAR(100)                           -- 最終更新者
);

-- コメント
COMMENT ON TABLE fee_master IS '入会金・年会費マスタテーブル（システム全体の設定）';
COMMENT ON COLUMN fee_master.id IS 'ID（常に1、単一レコード）';
COMMENT ON COLUMN fee_master.entry_fee IS '入会金';
COMMENT ON COLUMN fee_master.annual_fees IS '年度別年会費 [{year: 2025, amount: 50000, description: "2025年度年会費"}]';
COMMENT ON COLUMN fee_master.entry_fee_description IS '入会金の説明';
COMMENT ON COLUMN fee_master.annual_fee_description IS '年会費の説明';
COMMENT ON COLUMN fee_master.updated_by IS '最終更新者';

-- 初期データの挿入
INSERT INTO fee_master (
    entry_fee,
    annual_fees,
    entry_fee_description,
    annual_fee_description
) VALUES (
    300000,
    '[
        {"year": 2025, "amount": 50000, "description": "2025年度年会費"},
        {"year": 2026, "amount": 50000, "description": "2026年度年会費"}
    ]'::jsonb,
    'Ferrari Owners Club Japan 入会金',
    'Ferrari Owners Club Japan 年会費'
) ON CONFLICT (id) DO NOTHING;

-- 更新トリガー
CREATE OR REPLACE FUNCTION update_fee_master_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_fee_master_timestamp
BEFORE UPDATE ON fee_master
FOR EACH ROW
EXECUTE FUNCTION update_fee_master_timestamp();

-- 年度別年会費を取得する関数
CREATE OR REPLACE FUNCTION get_master_annual_fee(target_year INTEGER)
RETURNS JSONB AS $$
DECLARE
    fee_data JSONB;
    fee_record JSONB;
BEGIN
    SELECT annual_fees INTO fee_data FROM fee_master LIMIT 1;
    
    IF fee_data IS NULL THEN
        RETURN NULL;
    END IF;
    
    FOR fee_record IN SELECT * FROM jsonb_array_elements(fee_data)
    LOOP
        IF (fee_record->>'year')::int = target_year THEN
            RETURN fee_record;
        END IF;
    END LOOP;
    
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- 年度別年会費を追加・更新する関数
CREATE OR REPLACE FUNCTION update_master_annual_fee(
    p_year INTEGER,
    p_amount NUMERIC,
    p_description TEXT DEFAULT NULL
)
RETURNS VOID AS $$
DECLARE
    current_fees JSONB;
    new_fee JSONB;
    updated_fees JSONB := '[]'::jsonb;
    fee_found BOOLEAN := FALSE;
    fee_record JSONB;
BEGIN
    -- 現在の年会費データを取得
    SELECT annual_fees INTO current_fees FROM fee_master LIMIT 1;
    
    -- 新しい年会費データを作成
    new_fee := jsonb_build_object(
        'year', p_year,
        'amount', p_amount,
        'description', COALESCE(p_description, p_year::text || '年度年会費')
    );
    
    -- 既存データの更新または追加
    IF current_fees IS NOT NULL AND jsonb_array_length(current_fees) > 0 THEN
        FOR fee_record IN SELECT * FROM jsonb_array_elements(current_fees)
        LOOP
            IF (fee_record->>'year')::int = p_year THEN
                updated_fees := updated_fees || new_fee;
                fee_found := TRUE;
            ELSE
                updated_fees := updated_fees || fee_record;
            END IF;
        END LOOP;
        
        IF NOT fee_found THEN
            updated_fees := updated_fees || new_fee;
        END IF;
    ELSE
        updated_fees := jsonb_build_array(new_fee);
    END IF;
    
    -- ソート（年度順）
    updated_fees := (
        SELECT jsonb_agg(elem ORDER BY (elem->>'year')::int)
        FROM jsonb_array_elements(updated_fees) elem
    );
    
    -- データベースを更新
    UPDATE fee_master SET annual_fees = updated_fees WHERE id = 1;
END;
$$ LANGUAGE plpgsql;

-- 使用例
/*
-- 入会金を更新
UPDATE fee_master SET entry_fee = 350000 WHERE id = 1;

-- 2027年度の年会費を追加
SELECT update_master_annual_fee(2027, 55000, '2027年度年会費（値上げ）');

-- 特定年度の年会費を取得
SELECT get_master_annual_fee(2025);
*/