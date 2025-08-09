-- MySQLへのサンプルデータ挿入

-- 管理者ユーザー（パスワード: admin123）
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE password = VALUES(password);

-- 会費マスタが既に存在しない場合のみ挿入
INSERT IGNORE INTO fee_masters (fee_type, fee_name, amount, display_order) VALUES
('admission', '入会金（通常）', 100000, 1),
('admission', '入会金（法人）', 150000, 2),
('annual', '年会費（初年度）', 50000, 3),
('annual', '年会費（2年目以降）', 40000, 4),
('annual', '年会費（法人）', 80000, 5);

-- メールテンプレートが既に存在しない場合のみ挿入
INSERT IGNORE INTO mail_templates (template_name, template_type, subject, body, is_active) VALUES
(
    '入会承認通知（詳細版）', 
    '承認通知', 
    'Ferrari Owners'' Club Japan 入会承認のお知らせ',
    '{{name}} 様

この度は、Ferrari Owners'' Club Japan (FOCJ) へのご入会申請をいただき、誠にありがとうございます。

厳正な審査の結果、あなた様の入会を承認させていただきましたことをお知らせいたします。

【会員番号】{{member_number}}
【入会日】{{approval_date}}

■今後のお手続きについて
1. 入会金・年会費のお支払い
   入会金: 100,000円
   年会費: 50,000円（初年度）
   
   お振込先：
   銀行名：○○銀行
   支店名：○○支店
   口座種別：普通
   口座番号：1234567
   口座名義：Ferrari Owners'' Club Japan

2. 会員証の発行
   ご入金確認後、約2週間で会員証を郵送いたします。

■会員特典
- 年次総会への参加権
- 各種イベントへの優先申込
- 会員限定ドライビングイベント
- フェラーリ関連情報の定期配信
- 提携施設での優待利用

ご不明な点がございましたら、事務局までお気軽にお問い合わせください。

今後とも、Ferrari Owners'' Club Japanをよろしくお願い申し上げます。

Ferrari Owners'' Club Japan
事務局
〒100-0000 東京都千代田区○○1-2-3
TEL: 03-0000-0000
Email: info@focj.jp
URL: https://www.focj.jp',
    FALSE
),
(
    '入会拒否通知（理由付き）', 
    '拒否通知', 
    'Ferrari Owners'' Club Japan 入会審査結果のお知らせ',
    '{{name}} 様

この度は、Ferrari Owners'' Club Japan (FOCJ) へのご入会申請をいただき、誠にありがとうございました。

慎重に審査させていただきました結果、誠に残念ながら今回のご入会は見送らせていただくこととなりました。

{{rejection_reason}}

今後、状況が変わりましたら、改めてご申請いただければ幸いです。

何卒ご理解賜りますようお願い申し上げます。

Ferrari Owners'' Club Japan
事務局',
    FALSE
),
(
    '年会費更新のご案内', 
    'リマインダー', 
    'Ferrari Owners'' Club Japan 年会費更新のお願い',
    '{{name}} 様

いつもFerrari Owners'' Club Japanの活動にご協力いただき、誠にありがとうございます。

年会費の更新時期が近づいてまいりましたので、ご案内申し上げます。

【更新期限】{{renewal_date}}
【年会費】40,000円（2年目以降）

お振込先：
銀行名：○○銀行
支店名：○○支店
口座種別：普通
口座番号：1234567
口座名義：Ferrari Owners'' Club Japan

更新期限までにお支払いいただきますようお願いいたします。

ご不明な点がございましたら、事務局までお問い合わせください。

Ferrari Owners'' Club Japan
事務局',
    FALSE
),
(
    'イベント開催のご案内', 
    'その他', 
    'Ferrari Owners'' Club Japan イベントのお知らせ',
    '{{name}} 様

Ferrari Owners'' Club Japanよりイベント開催のお知らせです。

{{event_details}}

皆様のご参加を心よりお待ちしております。

Ferrari Owners'' Club Japan
事務局',
    FALSE
);

-- テスト用の登録データ（承認待ち）
INSERT INTO registrations (
    family_name, first_name, family_name_kana, first_name_kana, name_alphabet,
    postal_code, prefecture, city_address, building_name, address_type,
    phone_number, mobile_number, email,
    birth_date, gender,
    occupation, company_name,
    car_model, model_year, car_color, car_number,
    relationship_dealer, sales_person,
    self_introduction,
    referrer1, referrer_dealer,
    status
) VALUES 
(
    '山田', '太郎', 'ヤマダ', 'タロウ', 'Taro Yamada',
    '100-0001', '東京都', '千代田区千代田1-1-1', 'フェラーリビル101', 'home',
    '03-1234-5678', '090-1234-5678', 'yamada@example.com',
    '1980-01-15', 'male',
    '会社役員', '株式会社山田商事',
    'Ferrari 488 GTB', '2020', 'Rosso Corsa', '品川 330 あ 12-34',
    'フェラーリ東京', '田中一郎',
    'フェラーリを愛する仲間との交流を楽しみにしております。',
    '鈴木次郎', 'フェラーリ横浜',
    'pending'
),
(
    '佐藤', '花子', 'サトウ', 'ハナコ', 'Hanako Sato',
    '106-0032', '東京都', '港区六本木6-10-1', '六本木ヒルズ2001', 'work',
    '03-9876-5432', '080-9876-5432', 'sato@example.com',
    '1975-06-20', 'female',
    '医師', '佐藤クリニック',
    'Ferrari F8 Tributo', '2021', 'Blu Corsa', '港 330 さ 98-76',
    'フェラーリ大阪', '山本三郎',
    '週末のドライブを楽しんでいます。クラブイベントに積極的に参加したいです。',
    '高橋四郎', '',
    'pending'
);