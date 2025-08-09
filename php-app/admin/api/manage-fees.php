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
            $sql = "SELECT * FROM fee_master WHERE membership_type = 'regular' LIMIT 1";
            $stmt = $db->query($sql);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // デフォルトの年会費配列
            $currentYear = date('Y');
            $defaultAnnualFees = [
                ['year' => $currentYear, 'amount' => 50000, 'description' => $currentYear . '年度年会費'],
                ['year' => $currentYear + 1, 'amount' => 50000, 'description' => ($currentYear + 1) . '年度年会費']
            ];
            
            if (!$data) {
                // データがない場合はデフォルト値を返す
                $data = [
                    'entry_fee' => 300000,
                    'annual_fees' => $defaultAnnualFees,
                    'entry_fee_description' => '初回登録時の入会金',
                    'annual_fee_description' => '年度ごとの年会費'
                ];
            } else {
                // データベースのannual_feeを配列形式に変換
                $data['annual_fees'] = $defaultAnnualFees;
                if (isset($data['annual_fee'])) {
                    $data['annual_fees'][0]['amount'] = floatval($data['annual_fee']);
                    $data['annual_fees'][1]['amount'] = floatval($data['annual_fee']);
                }
                
                // descriptionから説明を分離
                if (isset($data['description'])) {
                    $lines = explode("\n", $data['description']);
                    $data['entry_fee_description'] = str_replace('入会金: ', '', $lines[0] ?? '');
                    $data['annual_fee_description'] = str_replace('年会費: ', '', $lines[1] ?? '');
                }
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
                    CONCAT(r.family_name, ' ', r.first_name) as member_name
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
                        VALUES (:member_id, 'regular', 300000, '未払い', '[]')";
                $stmt = $db->prepare($sql);
                $stmt->execute([':member_id' => $memberId]);
                
                // Get the inserted record
                $sql = "SELECT * FROM membership_fees WHERE member_id = :member_id";
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
            
            // デバッグ用
            error_log("Member ID: " . $memberId);
            error_log("Annual fee data: " . json_encode($feeData['annual_fee']));
            
            echo json_encode($feeData);
        }
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($action === 'updateMaster') {
            error_log("updateMaster action started");
            error_log("Received data: " . json_encode($data));
            
            // マスター設定の更新
            $updatedBy = $_SESSION['admin_username'] ?? 'admin';
            error_log("Updated by: " . $updatedBy);
            
            // 通常会員のレコードが存在するか確認
            $sql = "SELECT COUNT(*) FROM fee_master WHERE membership_type = 'regular'";
            error_log("Executing SQL: " . $sql);
            
            try {
                $count = $db->query($sql)->fetchColumn();
                error_log("Record count: " . $count);
            } catch (PDOException $e) {
                error_log("Failed to count records: " . $e->getMessage());
                throw $e;
            }
            
            // 年会費配列から現在年度の年会費を取得
            $currentYear = date('Y');
            $annualFee = 0;
            foreach ($data['annual_fees'] as $fee) {
                if ($fee['year'] == $currentYear) {
                    $annualFee = $fee['amount'];
                    break;
                }
            }
            
            // 説明文を結合
            $description = "入会金: " . ($data['entry_fee_description'] ?? '') . "\n";
            $description .= "年会費: " . ($data['annual_fee_description'] ?? '');
            
            if ($count > 0) {
                // 更新
                $sql = "UPDATE fee_master SET 
                        entry_fee = :entry_fee,
                        annual_fee = :annual_fee,
                        description = :description,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE membership_type = 'regular'";
                    
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':entry_fee' => $data['entry_fee'],
                    ':annual_fee' => $annualFee,
                    ':description' => trim($description)
                ]);
            } else {
                // 新規作成
                $sql = "INSERT INTO fee_master (
                        membership_type,
                        entry_fee,
                        annual_fee,
                        description,
                        is_active
                    ) VALUES (
                        'regular',
                        :entry_fee,
                        :annual_fee,
                        :description,
                        1
                    )";
                    
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':entry_fee' => $data['entry_fee'],
                    ':annual_fee' => $annualFee,
                    ':description' => trim($description)
                ]);
            }
            
            echo json_encode(['success' => $result]);
            exit;
        }
        elseif ($action === 'update_entry_fee') {
            // 入会金の更新
            $sql = "UPDATE membership_fees SET 
                    entry_fee = :amount,
                    payment_status = :status,
                    entry_fee_payment_date = :payment_date,
                    entry_fee_payment_method = :payment_method,
                    entry_fee_payment_deadline = :payment_deadline,
                    entry_fee_notes = :notes,
                    updated_at = CURRENT_TIMESTAMP
                WHERE member_id = :member_id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':member_id' => $memberId,
                ':amount' => $data['amount'] ?: 300000,
                ':status' => $data['status'],
                ':payment_date' => $data['payment_date'] ?: null,
                ':payment_method' => $data['payment_method'] ?: null,
                ':payment_deadline' => $data['payment_deadline'] ?: null,
                ':notes' => $data['notes'] ?: null
            ]);
            
            echo json_encode(['success' => $result]);
            
        } elseif ($action === 'update_annual_fee') {
            // 年会費の更新
            $year = $data['year'];
            
            // デバッグログ
            error_log("Update annual fee - Year: " . $year . ", MemberID: " . $memberId);
            error_log("Update annual fee - Data: " . json_encode($data));
            
            // 現在のannual_feeを取得
            $sql = "SELECT annual_fee FROM membership_fees WHERE member_id = :member_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':member_id' => $memberId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $annualFees = json_decode($result['annual_fee'], true) ?? [];
            error_log("Current annual fees: " . json_encode($annualFees));
            
            // 該当年度のデータを探す
            $found = false;
            foreach ($annualFees as &$fee) {
                // 年度を整数として比較
                error_log("Comparing year: " . $fee['year'] . " (type: " . gettype($fee['year']) . ") with " . $year . " (type: " . gettype($year) . ")");
                if (intval($fee['year']) == intval($year)) {
                    error_log("Found matching year! Updating...");
                    $fee['amount'] = $data['amount'];
                    $fee['status'] = $data['status'];
                    $fee['payment_date'] = $data['payment_date'] ?: null;
                    $fee['payment_method'] = $data['payment_method'] ?: null;
                    $fee['payment_deadline'] = $data['payment_deadline'] ?: null;
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
                    'payment_deadline' => $data['payment_deadline'] ?: null,
                    'notes' => $data['notes'] ?: null
                ];
            }
            
            // ソート（年度降順）
            usort($annualFees, function($a, $b) {
                return $b['year'] - $a['year'];
            });
            
            // 更新後のデータをログ出力
            error_log("Updated annual fees after sort: " . json_encode($annualFees));
            
            // データベースを更新
            $sql = "UPDATE membership_fees SET 
                    annual_fee = :annual_fee,
                    updated_at = CURRENT_TIMESTAMP
                WHERE member_id = :member_id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':member_id' => $memberId,
                ':annual_fee' => json_encode($annualFees)
            ]);
            
            error_log("Update result: " . ($result ? "SUCCESS" : "FAILED"));
            
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
                'payment_deadline' => null,
                'notes' => null
            ];
            
            // ソート（年度降順）
            usort($annualFees, function($a, $b) {
                return $b['year'] - $a['year'];
            });
            
            // データベースを更新
            $sql = "UPDATE membership_fees SET 
                    annual_fee = :annual_fee,
                    updated_at = CURRENT_TIMESTAMP
                WHERE member_id = :member_id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':member_id' => $memberId,
                ':annual_fee' => json_encode($annualFees)
            ]);
            
            echo json_encode(['success' => $result]);
        }
    }
    
} catch (PDOException $e) {
    error_log("PDO Error in manage-fees.php: " . $e->getMessage());
    error_log("SQLSTATE: " . $e->getCode());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'sqlstate' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    error_log("Error in manage-fees.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>