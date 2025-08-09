#!/bin/bash

echo "データベースをセットアップ中..."

# スキーマを作成
echo "1. スキーマを作成..."
docker exec -i mysql-focj mysql -uroot -pKumagai@0502 < sql/schema_mysql.sql

# member_numberカラムを追加
echo "2. member_numberカラムを追加..."
docker exec mysql-focj mysql -uroot -pKumagai@0502 focj_admin -e "
ALTER TABLE registrations 
ADD COLUMN member_number INT UNIQUE DEFAULT NULL AFTER id;
"

# member_number_settingsテーブルを作成
echo "3. member_number_settingsテーブルを作成..."
docker exec mysql-focj mysql -uroot -pKumagai@0502 focj_admin -e "
CREATE TABLE IF NOT EXISTS member_number_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_number INT DEFAULT 1,
    exclude_numbers JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(50)
);
"

# 初期データを挿入
echo "4. 初期データを挿入..."
docker exec mysql-focj mysql -uroot -pKumagai@0502 focj_admin -e "
INSERT INTO member_number_settings (start_number, exclude_numbers, updated_by) 
VALUES (1, '[]', 'system')
ON DUPLICATE KEY UPDATE id=id;
"

# fee_masterテーブルを作成
echo "5. fee_masterテーブルを作成..."
docker exec mysql-focj mysql -uroot -pKumagai@0502 focj_admin -e "
CREATE TABLE IF NOT EXISTS fee_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_fee INT DEFAULT 300000,
    entry_fee_description TEXT,
    annual_fee INT DEFAULT 50000,
    annual_fee_description TEXT,
    annual_fees JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
"

# membership_feesテーブルを作成
echo "6. membership_feesテーブルを作成..."
docker exec mysql-focj mysql -uroot -pKumagai@0502 focj_admin -e "
CREATE TABLE IF NOT EXISTS membership_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    payment_status VARCHAR(20) DEFAULT '未払い',
    entry_fee_amount INT,
    entry_fee_payment_date DATETIME,
    entry_fee_payment_method VARCHAR(50),
    entry_fee_payment_deadline DATETIME,
    entry_fee_notes TEXT,
    annual_fee JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_member_id (member_id)
);
"

echo "データベースセットアップ完了！"