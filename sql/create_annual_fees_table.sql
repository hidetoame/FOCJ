-- 年会費管理テーブルの作成
-- membership_fees.payment_status は入会金の支払いステータスとして使用
-- 年会費は年度別に管理するため別テーブルで管理

-- 年会費管理テーブル
CREATE TABLE IF NOT EXISTS annual_fees (
    annual_fee_id SERIAL PRIMARY KEY,
    member_id INTEGER NOT NULL,
    fiscal_year INTEGER NOT NULL, -- 年度（例：2025）
    amount NUMERIC(10, 2) NOT NULL DEFAULT 50000.00, -- 年会費金額
    payment_status VARCHAR(20) NOT NULL DEFAULT '未払い',
    payment_date TIMESTAMP, -- 支払い日
    payment_method VARCHAR(50), -- 支払い方法（振込、クレジットカード、現金等）
    receipt_number VARCHAR(50), -- 領収書番号
    notes TEXT, -- 備考
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    created_by VARCHAR(50), -- 作成者（管理者名）
    updated_by VARCHAR(50), -- 更新者（管理者名）
    
    -- 外部キー制約
    CONSTRAINT fk_annual_fees_member 
        FOREIGN KEY (member_id) 
        REFERENCES members(member_id) 
        ON DELETE CASCADE,
    
    -- チェック制約
    CONSTRAINT chk_annual_fees_payment_status 
        CHECK (payment_status IN ('未払い', '支払い済み', '免除', '期限切れ')),
    
    CONSTRAINT chk_annual_fees_payment_method 
        CHECK (payment_method IN ('銀行振込', 'クレジットカード', '現金', 'その他', NULL)),
    
    -- 同一会員・同一年度の重複を防ぐ
    CONSTRAINT uk_annual_fees_member_year 
        UNIQUE (member_id, fiscal_year)
);

-- インデックスの作成
CREATE INDEX idx_annual_fees_member_id ON annual_fees(member_id);
CREATE INDEX idx_annual_fees_fiscal_year ON annual_fees(fiscal_year);
CREATE INDEX idx_annual_fees_payment_status ON annual_fees(payment_status);
CREATE INDEX idx_annual_fees_payment_date ON annual_fees(payment_date);

-- コメントの追加
COMMENT ON TABLE annual_fees IS '年会費管理テーブル';
COMMENT ON COLUMN annual_fees.annual_fee_id IS '年会費ID（主キー）';
COMMENT ON COLUMN annual_fees.member_id IS '会員ID（members.member_idへの外部キー）';
COMMENT ON COLUMN annual_fees.fiscal_year IS '会計年度';
COMMENT ON COLUMN annual_fees.amount IS '年会費金額';
COMMENT ON COLUMN annual_fees.payment_status IS '支払いステータス（未払い/支払い済み/免除/期限切れ）';
COMMENT ON COLUMN annual_fees.payment_date IS '支払い日';
COMMENT ON COLUMN annual_fees.payment_method IS '支払い方法';
COMMENT ON COLUMN annual_fees.receipt_number IS '領収書番号';
COMMENT ON COLUMN annual_fees.notes IS '備考';
COMMENT ON COLUMN annual_fees.created_at IS '作成日時';
COMMENT ON COLUMN annual_fees.updated_at IS '更新日時';
COMMENT ON COLUMN annual_fees.created_by IS '作成者（管理者名）';
COMMENT ON COLUMN annual_fees.updated_by IS '更新者（管理者名）';

-- membership_feesテーブルにコメントを追加（入会金用であることを明記）
COMMENT ON COLUMN membership_fees.payment_status IS '入会金の支払いステータス（未払い/支払い済み/支払い期限切れ）';

-- 年会費集計ビューの作成（年度別の支払い状況を簡単に確認できる）
CREATE OR REPLACE VIEW v_annual_fees_summary AS
SELECT 
    af.fiscal_year,
    COUNT(*) as total_members,
    COUNT(CASE WHEN af.payment_status = '支払い済み' THEN 1 END) as paid_count,
    COUNT(CASE WHEN af.payment_status = '未払い' THEN 1 END) as unpaid_count,
    COUNT(CASE WHEN af.payment_status = '免除' THEN 1 END) as exempt_count,
    COUNT(CASE WHEN af.payment_status = '期限切れ' THEN 1 END) as expired_count,
    SUM(CASE WHEN af.payment_status = '支払い済み' THEN af.amount ELSE 0 END) as total_collected,
    SUM(CASE WHEN af.payment_status = '未払い' THEN af.amount ELSE 0 END) as total_pending
FROM annual_fees af
GROUP BY af.fiscal_year
ORDER BY af.fiscal_year DESC;

-- 会員別の年会費支払い履歴ビュー
CREATE OR REPLACE VIEW v_member_annual_fees_history AS
SELECT 
    m.member_id,
    m.member_number,
    r.family_name || ' ' || r.first_name as member_name,
    af.fiscal_year,
    af.amount,
    af.payment_status,
    af.payment_date,
    af.payment_method,
    af.receipt_number
FROM members m
INNER JOIN registrations r ON m.registration_id = r.id
LEFT JOIN annual_fees af ON m.member_id = af.member_id
ORDER BY m.member_id, af.fiscal_year DESC;