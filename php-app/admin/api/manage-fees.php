<?php
/**
 * 入会金・年会費管理API
 */
require_once '../../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

// リクエストメソッドとアクションを取得
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$memberId = $_GET['member_id'] ?? 0;

header('Content-Type: application/json');

try {
    if ($method === 'GET') {
        // マスター設定を取得
        if ($action === 'getMaster') {
            $sql = "SELECT * FROM fee_master LIMIT 1";
            $stmt = $db->query($sql);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                // データがない場合はデフォルト値を返す
                $data = [
                    'entry_fee' => 300000,
                    'annual_fees' => json_encode([
                        ['year' => 2025, 'amount' => 50000, 'description' => '2025年度年会費'],
                        ['year' => 2026, 'amount' => 50000, 'description' => '2026年度年会費']
                    ])
                ];
            }
            
            // JSONデータをデコード
            if (isset($data['annual_fees']) && is_string($data['annual_fees'])) {
                $data['annual_fees'] = json_decode($data['annual_fees'], true);
            }
            
            echo json_encode($data);
            exit;
        }
        // 会費情報を取得
        elseif ($action === 'get_fees') {
            // まず会員情報を取得
            $sql = "SELECT 
                    m.member_id,
                    m.member_number,
                    r.family_name || ' ' || r.first_name as member_name
                FROM members m
                LEFT JOIN registrations r ON r.email = m.email
                WHERE m.member_id = :member_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':member_id' => $memberId]);
            $memberInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // membership_feesテーブルから情報を取得
            $sql = "SELECT * FROM membership_fees WHERE member_id = :member_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':member_id' => $memberId]);
            $feeData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$feeData) {
                // membership_feesレコードがない場合は作成
                $sql = "INSERT INTO membership_fees (member_id, membership_type, entry_fee, payment_status, annual_fee) 
                        VALUES (:member_id, 'メール会員', 300000, '未払い', '[]'::jsonb)
                        RETURNING *";
                $stmt = $db->prepare($sql);
                $stmt->execute([':member_id' => $memberId]);
                $feeData = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // 会員情報をマージ
            if ($memberInfo) {
                $feeData['member_number'] = $memberInfo['member_number'];
                $feeData['member_name'] = $memberInfo['member_name'];
            }
            
            // annual_feeをデコード
            $feeData['annual_fee'] = json_decode($feeData['annual_fee'], true) ?? [];
            
            echo json_encode($feeData);
        }
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($action === 'updateMaster') {
            // マスター設定の更新
            $updatedBy = $_SESSION['admin_username'] ?? 'admin';
            
            // 既存レコードの確認
            $sql = "SELECT COUNT(*) FROM fee_master";
            $count = $db->query($sql)->fetchColumn();
            
            if ($count > 0) {
                // 更新
                $sql = "UPDATE fee_master SET 
                        entry_fee = :entry_fee,
                        annual_fees = :annual_fees,
                        entry_fee_description = :entry_fee_description,
                        annual_fee_description = :annual_fee_description,
                        updated_at = NOW(),
                        updated_by = :updated_by
                    WHERE id = 1";
            } else {
                // 新規作成
                $sql = "INSERT INTO fee_master (
                        entry_fee,
                        annual_fees,
                        entry_fee_description,
                        annual_fee_description,
                        updated_by
                    ) VALUES (
                        :entry_fee,
                        :annual_fees,
                        :entry_fee_description,
                        :annual_fee_description,
                        :updated_by
                    )";
            }
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':entry_fee' => $data['entry_fee'],
                ':annual_fees' => json_encode($data['annual_fees']),
                ':entry_fee_description' => $data['entry_fee_description'] ?? '',
                ':annual_fee_description' => $data['annual_fee_description'] ?? '',
                ':updated_by' => $updatedBy
            ]);
            
            echo json_encode(['success' => $result]);
            exit;
        }
        elseif ($action === 'update_entry_fee') {
            // 入会金の更新
            $sql = "UPDATE membership_fees SET 
                    payment_status = :status,
                    entry_fee_payment_date = :payment_date,
                    entry_fee_payment_method = :payment_method,
                    entry_fee_receipt_number = :receipt_number,
                    entry_fee_notes = :notes,
                    updated_at = NOW()
                WHERE member_id = :member_id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':member_id' => $memberId,
                ':status' => $data['status'],
                ':payment_date' => $data['payment_date'] ?: null,
                ':payment_method' => $data['payment_method'] ?: null,
                ':receipt_number' => $data['receipt_number'] ?: null,
                ':notes' => $data['notes'] ?: null
            ]);
            
            echo json_encode(['success' => $result]);
            
        } elseif ($action === 'update_annual_fee') {
            // 年会費の更新
            $year = $data['year'];
            
            // 現在のannual_feeを取得
            $sql = "SELECT annual_fee FROM membership_fees WHERE member_id = :member_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':member_id' => $memberId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $annualFees = json_decode($result['annual_fee'], true) ?? [];
            
            // 該当年度のデータを探す
            $found = false;
            foreach ($annualFees as &$fee) {
                if ($fee['year'] == $year) {
                    $fee['amount'] = $data['amount'];
                    $fee['status'] = $data['status'];
                    $fee['payment_date'] = $data['payment_date'] ?: null;
                    $fee['payment_method'] = $data['payment_method'] ?: null;
                    $fee['receipt_number'] = $data['receipt_number'] ?: null;
                    $fee['notes'] = $data['notes'] ?: null;
                    $found = true;
                    break;
                }
            }
            
            // 見つからなかった場合は新規追加
            if (!$found) {
                $annualFees[] = [
                    'year' => $year,
                    'amount' => $data['amount'],
                    'status' => $data['status'],
                    'payment_date' => $data['payment_date'] ?: null,
                    'payment_method' => $data['payment_method'] ?: null,
                    'receipt_number' => $data['receipt_number'] ?: null,
                    'notes' => $data['notes'] ?: null
                ];
            }
            
            // ソート（年度降順）
            usort($annualFees, function($a, $b) {
                return $b['year'] - $a['year'];
            });
            
            // データベースを更新
            $sql = "UPDATE membership_fees SET 
                    annual_fee = :annual_fee,
                    updated_at = NOW()
                WHERE member_id = :member_id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':member_id' => $memberId,
                ':annual_fee' => json_encode($annualFees)
            ]);
            
            echo json_encode(['success' => $result]);
            
        } elseif ($action === 'add_annual_fee') {
            // 新規年度の年会費を追加
            $year = $data['year'];
            $amount = $data['amount'] ?? 50000;
            
            // 現在のannual_feeを取得
            $sql = "SELECT annual_fee FROM membership_fees WHERE member_id = :member_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':member_id' => $memberId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $annualFees = json_decode($result['annual_fee'], true) ?? [];
            
            // 重複チェック
            foreach ($annualFees as $fee) {
                if ($fee['year'] == $year) {
                    echo json_encode(['error' => 'この年度の年会費は既に登録されています']);
                    exit;
                }
            }
            
            // 新規追加
            $annualFees[] = [
                'year' => $year,
                'amount' => $amount,
                'status' => '未払い',
                'payment_date' => null,
                'payment_method' => null,
                'receipt_number' => null,
                'notes' => null
            ];
            
            // ソート（年度降順）
            usort($annualFees, function($a, $b) {
                return $b['year'] - $a['year'];
            });
            
            // データベースを更新
            $sql = "UPDATE membership_fees SET 
                    annual_fee = :annual_fee,
                    updated_at = NOW()
                WHERE member_id = :member_id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':member_id' => $memberId,
                ':annual_fee' => json_encode($annualFees)
            ]);
            
            echo json_encode(['success' => $result]);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>