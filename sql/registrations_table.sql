-- registrationsテーブル（既存システム互換用）
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- 基本情報
    family_name VARCHAR(50),
    first_name VARCHAR(50),
    family_name_kana VARCHAR(50),
    first_name_kana VARCHAR(50),
    name_alphabet VARCHAR(100),
    
    -- 住所
    postal_code VARCHAR(10),
    prefecture VARCHAR(20),
    city_address VARCHAR(255),
    building_name VARCHAR(100),
    address_type ENUM('home', 'work') DEFAULT 'home',
    
    -- 連絡先
    mobile_number VARCHAR(20),
    phone_number VARCHAR(20),
    email VARCHAR(100),
    
    -- 個人情報
    birth_date DATE,
    gender ENUM('male', 'female'),
    occupation TEXT,
    company_name VARCHAR(255),
    self_introduction TEXT,
    schedule_url VARCHAR(255),
    
    -- 車両情報
    car_model VARCHAR(100),
    model_year VARCHAR(10),
    car_color VARCHAR(50),
    car_number VARCHAR(50),
    other_cars TEXT,
    
    -- ディーラー情報
    relationship_dealer VARCHAR(100),
    sales_person VARCHAR(100),
    
    -- 紹介者情報
    referrer1 VARCHAR(100),
    referrer_dealer VARCHAR(100),
    referrer2 VARCHAR(100),
    
    -- その他の情報
    how_found VARCHAR(50),
    how_found_other TEXT,
    comments TEXT,
    
    -- 添付ファイル
    drivers_license_file VARCHAR(255),
    vehicle_inspection_file VARCHAR(255),
    business_card_file VARCHAR(255),
    license_image VARCHAR(255),
    vehicle_inspection_image VARCHAR(255),
    business_card_image VARCHAR(255),
    
    -- ステータス
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_at TIMESTAMP NULL DEFAULT NULL,
    approved_by VARCHAR(50) DEFAULT NULL,
    rejected_at TIMESTAMP NULL DEFAULT NULL,
    rejected_by VARCHAR(50) DEFAULT NULL,
    rejection_reason TEXT,
    
    -- 退会関連
    is_withdrawn BOOLEAN DEFAULT FALSE,
    withdrawn_at TIMESTAMP NULL,
    withdrawn_by VARCHAR(50),
    withdrawal_reason TEXT,
    
    -- タイムスタンプ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- インデックス
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- adminsテーブル（管理者用）
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 管理者初期データ（パスワード: admin）
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$8pF2KmrFJkwJ8Yjyyx8hXeQqQr2AxNV9cFMXHMgXpBYC1TcBv7y16')
ON DUPLICATE KEY UPDATE password = password;