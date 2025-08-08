<?php
/**
 * メール送信関連の関数
 */

/**
 * 承認メールを送信
 */
function sendApprovalEmail($db, $registrationId) {
    // 申込者情報を取得
    $sql = "SELECT * FROM registrations WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        return false;
    }
    
    // アクティブな承認用テンプレートを取得
    $sql = "SELECT * FROM mail_templates 
            WHERE template_type = '承認通知' AND is_active = true 
            LIMIT 1";
    $stmt = $db->query($sql);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        error_log("No active approval template found");
        return false;
    }
    
    // テンプレート変数を置換
    $body = $template['body'];
    $body = str_replace('{{name}}', $registration['family_name'] . ' ' . $registration['first_name'], $body);
    $body = str_replace('{{email}}', $registration['email'], $body);
    $body = str_replace('{{member_number}}', 'FOCJ-' . str_pad($registrationId, 5, '0', STR_PAD_LEFT), $body);
    
    // メール送信（実際の実装では mail() または PHPMailer等を使用）
    $to = $registration['email'];
    $subject = $template['subject'];
    $headers = "From: noreply@focj.jp\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // デバッグ用：実際のメール送信はコメントアウト
    // $result = mail($to, $subject, $body, $headers);
    
    // メール履歴を記録
    $sql = "INSERT INTO mail_history (template_id, recipient_email, subject, body, sent_at, sent_by) 
            VALUES (:template_id, :email, :subject, :body, NOW(), 'system')";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':template_id' => $template['template_id'],
        ':email' => $to,
        ':subject' => $subject,
        ':body' => $body
    ]);
    
    error_log("Approval email would be sent to: " . $to);
    return true;
}

/**
 * カスタム承認メールを送信（編集されたメール内容を使用）
 */
function sendCustomApprovalEmail($db, $registrationId, $customContent) {
    // 申込者情報を取得
    $sql = "SELECT * FROM registrations WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        return false;
    }
    
    // メール送信（実際の実装では mail() または PHPMailer等を使用）
    $to = $registration['email'];
    $subject = "Ferrari Owners' Club Japan - ご入会承認のお知らせ";
    $headers = "From: noreply@focj.jp\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // デバッグ用：実際のメール送信はコメントアウト
    // $result = mail($to, $subject, $customContent, $headers);
    
    // メール履歴を記録（テンプレートIDはNULLとして記録）
    $sql = "INSERT INTO mail_history (template_id, recipient_email, subject, body, sent_at, sent_by) 
            VALUES (NULL, :email, :subject, :body, NOW(), 'system')";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':email' => $to,
        ':subject' => $subject,
        ':body' => $customContent
    ]);
    
    error_log("Custom approval email would be sent to: " . $to);
    return true;
}

/**
 * 否認メールを送信
 */
function sendRejectionEmail($db, $registrationId, $reason = '') {
    // 申込者情報を取得
    $sql = "SELECT * FROM registrations WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        return false;
    }
    
    // アクティブな否認用テンプレートを取得
    $sql = "SELECT * FROM mail_templates 
            WHERE template_type = '却下通知' AND is_active = true 
            LIMIT 1";
    $stmt = $db->query($sql);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        error_log("No active rejection template found");
        return false;
    }
    
    // テンプレート変数を置換
    $body = $template['body'];
    $body = str_replace('{{name}}', $registration['family_name'] . ' ' . $registration['first_name'], $body);
    $body = str_replace('{{email}}', $registration['email'], $body);
    $body = str_replace('{{rejection_reason}}', $reason, $body);
    
    // メール送信（実際の実装では mail() または PHPMailer等を使用）
    $to = $registration['email'];
    $subject = $template['subject'];
    $headers = "From: noreply@focj.jp\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // デバッグ用：実際のメール送信はコメントアウト
    // $result = mail($to, $subject, $body, $headers);
    
    // メール履歴を記録
    $sql = "INSERT INTO mail_history (template_id, recipient_email, subject, body, sent_at, sent_by) 
            VALUES (:template_id, :email, :subject, :body, NOW(), 'system')";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':template_id' => $template['template_id'],
        ':email' => $to,
        ':subject' => $subject,
        ':body' => $body
    ]);
    
    error_log("Rejection email would be sent to: " . $to);
    return true;
}