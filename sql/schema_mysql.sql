-- FOCJ会員登録システム MySQL版テーブル作成スクリプト

-- 1. members（会員基本情報）
CREATE TABLE members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    member_number VARCHAR(10) UNIQUE,
    family_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    family_name_kana VARCHAR(50) NOT NULL,
    first_name_kana VARCHAR(50) NOT NULL,
    name_alphabet VARCHAR(100) NOT NULL,
    postal_code VARCHAR(8) NOT NULL,
    prefecture VARCHAR(10) NOT NULL,
    city_address TEXT NOT NULL,
    building_name TEXT,
    address_type VARCHAR(10) NOT NULL,
    mobile_number VARCHAR(20) NOT NULL,
    phone_number VARCHAR(20),
    birth_date DATE NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    occupation TEXT NOT NULL,
    self_introduction TEXT NOT NULL,
    relationship_dealer VARCHAR(50),
    sales_person VARCHAR(100),
    privacy_agreement BOOLEAN NOT NULL DEFAULT FALSE,
    application_status VARCHAR(20) NOT NULL DEFAULT '申請中',
    approved_at TIMESTAMP NULL,
    approved_by VARCHAR(100),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_address_type CHECK (address_type IN ('自宅', '勤務先')),
    CONSTRAINT chk_application_status CHECK (application_status IN ('申請中', '審査中', '承認済み', '却下'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ferrari_vehicles（フェラーリ車両情報）
CREATE TABLE ferrari_vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    color VARCHAR(50) NOT NULL,
    registration_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. referrers（紹介者情報）
CREATE TABLE referrers (
    referrer_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    referrer_name VARCHAR(100) NOT NULL,
    referrer_dealer VARCHAR(50),
    is_director BOOLEAN NOT NULL DEFAULT FALSE,
    referrer_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. attachments（添付書類）
CREATE TABLE attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    document_type VARCHAR(20) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    CONSTRAINT chk_document_type CHECK (document_type IN ('運転免許証', '車検証', '名刺'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. membership_fees（会費情報）
CREATE TABLE membership_fees (
    fee_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    membership_type VARCHAR(20) NOT NULL,
    entry_fee DECIMAL(10,2) NOT NULL,
    annual_fee DECIMAL(10,2) NOT NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT '未払い',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    CONSTRAINT chk_membership_type CHECK (membership_type IN ('メール会員')),
    CONSTRAINT chk_payment_status CHECK (payment_status IN ('未払い', '支払い済み', '支払い期限切れ'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. application_history（申請履歴）
CREATE TABLE application_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    status_from VARCHAR(20) NOT NULL,
    status_to VARCHAR(20) NOT NULL,
    notes TEXT,
    processed_by VARCHAR(100),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. admin_users（管理者ユーザー）
CREATE TABLE admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    hashed_password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_role CHECK (role IN ('admin', 'super_admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. admin_sessions（管理者セッション）
CREATE TABLE admin_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. mail_templates（メールテンプレート）
CREATE TABLE mail_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    template_type VARCHAR(20) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_template_type CHECK (template_type IN ('承認通知', '却下通知', '案内'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. mail_history（メール送信履歴）
CREATE TABLE mail_history (
    mail_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    template_id INT,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT '送信済み',
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES mail_templates(template_id) ON DELETE SET NULL,
    CONSTRAINT chk_mail_status CHECK (status IN ('送信済み', '送信失敗'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. fee_master（会費マスタ）
CREATE TABLE fee_master (
    fee_master_id INT AUTO_INCREMENT PRIMARY KEY,
    membership_type VARCHAR(20) NOT NULL UNIQUE,
    entry_fee DECIMAL(10,2) NOT NULL,
    annual_fee DECIMAL(10,2) NOT NULL,
    description TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_fee_membership_type CHECK (membership_type IN ('メール会員'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- インデックス作成
CREATE INDEX idx_members_email ON members(email);
CREATE INDEX idx_members_application_status ON members(application_status);
CREATE INDEX idx_members_member_number ON members(member_number);
CREATE INDEX idx_members_created_at ON members(created_at);

CREATE INDEX idx_ferrari_vehicles_member_id ON ferrari_vehicles(member_id);

CREATE INDEX idx_referrers_member_id ON referrers(member_id);

CREATE INDEX idx_attachments_member_id ON attachments(member_id);
CREATE INDEX idx_attachments_document_type ON attachments(document_type);

CREATE INDEX idx_membership_fees_member_id ON membership_fees(member_id);
CREATE INDEX idx_membership_fees_payment_status ON membership_fees(payment_status);

CREATE INDEX idx_application_history_member_id ON application_history(member_id);
CREATE INDEX idx_application_history_created_at ON application_history(created_at);

CREATE INDEX idx_admin_users_username ON admin_users(username);
CREATE INDEX idx_admin_users_email ON admin_users(email);
CREATE INDEX idx_admin_users_role ON admin_users(role);

CREATE INDEX idx_admin_sessions_token ON admin_sessions(session_token);
CREATE INDEX idx_admin_sessions_admin_id ON admin_sessions(admin_id);
CREATE INDEX idx_admin_sessions_expires_at ON admin_sessions(expires_at);

CREATE INDEX idx_mail_templates_type ON mail_templates(template_type);
CREATE INDEX idx_mail_templates_active ON mail_templates(is_active);

CREATE INDEX idx_mail_history_member_id ON mail_history(member_id);
CREATE INDEX idx_mail_history_sent_at ON mail_history(sent_at);

-- 会員番号用のシーケンステーブル（MySQLにはSEQUENCEがないため代替）
CREATE TABLE member_number_sequence (
    id INT NOT NULL,
    next_val INT NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初期値2000から開始
INSERT INTO member_number_sequence (id, next_val) VALUES (1, 2000);