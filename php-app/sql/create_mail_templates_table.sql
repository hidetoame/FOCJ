-- メールテンプレートテーブル作成
CREATE TABLE IF NOT EXISTS mail_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('approve', 'reject')),
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_active BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 更新時刻を自動更新するトリガー
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_mail_templates_updated_at BEFORE UPDATE
    ON mail_templates FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- サンプルデータ挿入
INSERT INTO mail_templates (name, type, subject, content, is_active) VALUES
('標準テンプレート', 'approve', 'Ferrari Owners'' Club Japan 入会承認のお知らせ', 'お申込みありがとうございます。

この度は、Ferrari Owners'' Club Japanへのご入会申請をいただき、
誠にありがとうございます。

審査の結果、正式会員として承認されましたことをお知らせいたします。

【会員番号】
{{member_number}}

【ご登録情報】
お名前: {{name}}
メールアドレス: {{email}}

今後のイベント情報等は別途ご案内させていただきます。
何かご不明な点がございましたら、お気軽にお問い合わせください。

Ferrari Owners'' Club Japan
事務局', true),

('イベント案内用', 'approve', 'FOCJ 入会承認とイベントのご案内', 'この度は、Ferrari Owners'' Club Japanへのご入会申請をいただき、
誠にありがとうございます。

審査の結果、正式会員として承認されましたことをお知らせいたします。

次回イベントのご案内：
日時: 2025年3月15日（土）10:00〜
場所: 富士スピードウェイ
詳細は追ってご連絡いたします。

Ferrari Owners'' Club Japan', false),

('標準否認テンプレート', 'reject', 'Ferrari Owners'' Club Japan 入会審査結果のお知らせ', 'お申込みありがとうございます。

この度は、Ferrari Owners'' Club Japanへのご入会申請をいただき、
誠にありがとうございました。

誠に申し訳ございませんが、厳正なる審査の結果、
今回はご入会を見送らせていただくこととなりました。

またの機会がございましたら、改めてご申請いただければ幸いです。

Ferrari Owners'' Club Japan
事務局', true),

('詳細理由付き否認', 'reject', 'FOCJ 入会審査結果のお知らせ', 'お申込みありがとうございます。

この度は、Ferrari Owners'' Club Japanへのご入会申請をいただき、
誠にありがとうございました。

審査の結果、以下の理由により今回はご入会を見送らせていただきます。

{{rejection_reason}}

ご理解のほどよろしくお願いいたします。

Ferrari Owners'' Club Japan', false);