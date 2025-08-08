-- FOCJ会員登録システム テーブル作成スクリプト
-- データベース設計書v2.0に基づく

-- 1. members（会員基本情報）
CREATE TABLE members (
    member_id SERIAL PRIMARY KEY,
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
    address_type VARCHAR(10) NOT NULL CHECK (address_type IN ('自宅', '勤務先')),
    mobile_number VARCHAR(20) NOT NULL,
    phone_number VARCHAR(20),
    birth_date DATE NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    occupation TEXT NOT NULL,
    self_introduction TEXT NOT NULL,
    relationship_dealer VARCHAR(50),
    sales_person VARCHAR(100),
    privacy_agreement BOOLEAN NOT NULL DEFAULT FALSE,
    application_status VARCHAR(20) NOT NULL DEFAULT '申請中' CHECK (application_status IN ('申請中', '審査中', '承認済み', '却下')),
    approved_at TIMESTAMP,
    approved_by VARCHAR(100),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 2. ferrari_vehicles（フェラーリ車両情報）
CREATE TABLE ferrari_vehicles (
    vehicle_id SERIAL PRIMARY KEY,
    member_id INTEGER NOT NULL REFERENCES members(member_id),
    model_name VARCHAR(100) NOT NULL,
    year INTEGER NOT NULL,
    color VARCHAR(50) NOT NULL,
    registration_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 3. referrers（紹介者情報）
CREATE TABLE referrers (
    referrer_id SERIAL PRIMARY KEY,
    member_id INTEGER NOT NULL REFERENCES members(member_id),
    referrer_name VARCHAR(100) NOT NULL,
    referrer_dealer VARCHAR(50),
    is_director BOOLEAN NOT NULL DEFAULT FALSE,
    referrer_order INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 4. attachments（添付書類）
CREATE TABLE attachments (
    attachment_id SERIAL PRIMARY KEY,
    member_id INTEGER NOT NULL REFERENCES members(member_id),
    document_type VARCHAR(20) NOT NULL CHECK (document_type IN ('運転免許証', '車検証', '名刺')),
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 5. membership_fees（会費情報）
CREATE TABLE membership_fees (
    fee_id SERIAL PRIMARY KEY,
    member_id INTEGER NOT NULL REFERENCES members(member_id),
    membership_type VARCHAR(20) NOT NULL CHECK (membership_type IN ('メール会員')),
    entry_fee DECIMAL(10,2) NOT NULL,
    annual_fee DECIMAL(10,2) NOT NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT '未払い' CHECK (payment_status IN ('未払い', '支払い済み', '支払い期限切れ')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 6. application_history（申請履歴）
CREATE TABLE application_history (
    history_id SERIAL PRIMARY KEY,
    member_id INTEGER NOT NULL REFERENCES members(member_id),
    status_from VARCHAR(20) NOT NULL,
    status_to VARCHAR(20) NOT NULL,
    notes TEXT,
    processed_by VARCHAR(100),
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 7. admin_users（管理者ユーザー）
CREATE TABLE admin_users (
    admin_id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    hashed_password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin' CHECK (role IN ('admin', 'super_admin')),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 8. admin_sessions（管理者セッション）
CREATE TABLE admin_sessions (
    session_id SERIAL PRIMARY KEY,
    admin_id INTEGER NOT NULL REFERENCES admin_users(admin_id),
    session_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 9. mail_templates（メールテンプレート）
CREATE TABLE mail_templates (
    template_id SERIAL PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    template_type VARCHAR(20) NOT NULL CHECK (template_type IN ('承認通知', '却下通知', '案内')),
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 10. mail_history（メール送信履歴）
CREATE TABLE mail_history (
    mail_id SERIAL PRIMARY KEY,
    member_id INTEGER REFERENCES members(member_id),
    template_id INTEGER REFERENCES mail_templates(template_id),
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    sent_at TIMESTAMP NOT NULL DEFAULT NOW(),
    status VARCHAR(20) NOT NULL DEFAULT '送信済み' CHECK (status IN ('送信済み', '送信失敗'))
);

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

-- 初期データ挿入
INSERT INTO mail_templates (template_name, template_type, subject, body) VALUES
('承認通知メール', '承認通知', 'FOCJ入会申請の承認について', 'この度、FOCJ入会申請を承認いたしました。\n\n会員番号: {member_number}\n\n今後ともよろしくお願いいたします。'),
('却下通知メール', '却下通知', 'FOCJ入会申請について', 'この度、FOCJ入会申請について審査の結果、承認を見送らせていただきました。\n\nご理解いただけますと幸いです。'),
('案内メール', '案内', 'FOCJからのお知らせ', 'FOCJからのお知らせです。\n\n{message}\n\n今後ともよろしくお願いいたします。');

-- 管理者ユーザーの初期データ（パスワード: admin123）
INSERT INTO admin_users (username, email, hashed_password, full_name, role) VALUES
('admin', 'admin@focj.jp', '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/HS.iK2O', 'システム管理者', 'super_admin');

-- 会員番号シーケンス作成（2000番から開始）
CREATE SEQUENCE member_number_seq START 2000; 