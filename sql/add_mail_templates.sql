-- メールテンプレートテーブルの作成
CREATE TABLE IF NOT EXISTS mail_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    template_type ENUM('承認通知', '拒否通知', 'リマインダー', 'その他') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active)
);

-- サンプルテンプレートの挿入
INSERT INTO mail_templates (template_name, template_type, subject, body, is_active) VALUES
(
    '入会承認通知（標準）', 
    '承認通知', 
    'Ferrari Owners'' Club Japan 入会承認のお知らせ',
    '{{name}} 様

この度は、Ferrari Owners'' Club Japan (FOCJ) へのご入会申請をいただき、誠にありがとうございます。

厳正な審査の結果、あなた様の入会を承認させていただきましたことをお知らせいたします。

【会員番号】{{member_number}}

今後、クラブの活動やイベントに関する情報を随時お送りさせていただきます。
FOCJの一員として、フェラーリオーナーの皆様との交流をお楽しみください。

ご不明な点がございましたら、お気軽にお問い合わせください。

Ferrari Owners'' Club Japan
事務局',
    TRUE
),
(
    '入会拒否通知（標準）', 
    '拒否通知', 
    'Ferrari Owners'' Club Japan 入会審査結果のお知らせ',
    '{{name}} 様

この度は、Ferrari Owners'' Club Japan (FOCJ) へのご入会申請をいただき、誠にありがとうございました。

慎重に審査させていただきました結果、誠に残念ながら今回のご入会は見送らせていただくこととなりました。

審査内容の詳細につきましては、お答えいたしかねますことをご了承ください。

今後とも、何卒よろしくお願い申し上げます。

Ferrari Owners'' Club Japan
事務局',
    TRUE
);