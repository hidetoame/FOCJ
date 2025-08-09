-- member_number_sequenceテーブルに除外番号用のテーブルを作成
CREATE TABLE IF NOT EXISTS excluded_member_numbers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    excluded_number INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 除外番号を挿入
INSERT INTO excluded_member_numbers (excluded_number) VALUES 
    (1001),
    (1005),
    (1020);

-- 現在の番号を999に更新
UPDATE member_number_sequence SET next_val = 999 WHERE id = 1;