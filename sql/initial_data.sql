-- FOCJ会員登録システム 初期データ

-- メールテンプレート初期データ
INSERT INTO mail_templates (template_name, template_type, subject, body) VALUES
('承認通知メール', '承認通知', 'FOCJ入会申請の承認について', 'この度、FOCJ入会申請を承認いたしました。\n\n会員番号: {member_number}\n\n今後ともよろしくお願いいたします。'),
('却下通知メール', '却下通知', 'FOCJ入会申請について', 'この度、FOCJ入会申請について審査の結果、承認を見送らせていただきました。\n\nご理解いただけますと幸いです。'),
('案内メール', '案内', 'FOCJからのお知らせ', 'FOCJからのお知らせです。\n\n{message}\n\n今後ともよろしくお願いいたします。');

-- 管理者ユーザーの初期データ（パスワード: admin123）
INSERT INTO admin_users (username, email, hashed_password, full_name, role) VALUES
('admin', 'admin@focj.jp', '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/HS.iK2O', 'システム管理者', 'super_admin');

-- 会費マスタ初期データ
INSERT INTO fee_master (membership_type, entry_fee, annual_fee, description) VALUES
('メール会員', 10000.00, 30000.00, 'メール会員の入会金と年会費');