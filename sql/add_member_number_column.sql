-- registrationsテーブルにmember_numberカラムを追加
ALTER TABLE registrations 
ADD COLUMN member_number INT UNIQUE DEFAULT NULL AFTER id;

-- インデックスを追加
CREATE INDEX idx_member_number ON registrations(member_number);

-- 既存の承認済みレコードに会員番号を設定（IDと同じ値を設定）
UPDATE registrations 
SET member_number = id 
WHERE status = 'approved' AND member_number IS NULL;