-- 登録申込テーブル
CREATE TABLE IF NOT EXISTS registrations (
    id SERIAL PRIMARY KEY,
    -- 申込者情報
    family_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    family_name_kana VARCHAR(50) NOT NULL,
    first_name_kana VARCHAR(50) NOT NULL,
    name_alphabet VARCHAR(100) NOT NULL,
    
    -- 連絡先
    postal_code VARCHAR(10) NOT NULL,
    prefecture VARCHAR(20) NOT NULL,
    city_address VARCHAR(255) NOT NULL,
    building_name VARCHAR(255),
    phone_number VARCHAR(20) NOT NULL,
    mobile_number VARCHAR(20),
    email VARCHAR(255) NOT NULL,
    
    -- 生年月日・性別
    birth_date DATE NOT NULL,
    gender VARCHAR(10) NOT NULL,
    
    -- 職業
    occupation VARCHAR(100),
    company_name VARCHAR(255),
    
    -- 車両情報
    car_model VARCHAR(100) NOT NULL,
    model_year INTEGER,
    car_color VARCHAR(50),
    
    -- ファイルアップロード
    drivers_license_file VARCHAR(255) NOT NULL,
    vehicle_inspection_file VARCHAR(255) NOT NULL,
    business_card_file VARCHAR(255),
    
    -- 追加情報
    how_found VARCHAR(50),
    how_found_other TEXT,
    comments TEXT,
    
    -- システム情報
    status VARCHAR(20) DEFAULT 'pending', -- pending, approved, rejected
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP,
    approved_by INTEGER,
    rejection_reason TEXT
);

-- インデックス
CREATE INDEX idx_registrations_status ON registrations(status);
CREATE INDEX idx_registrations_email ON registrations(email);
CREATE INDEX idx_registrations_created_at ON registrations(created_at);