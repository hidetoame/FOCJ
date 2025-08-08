<?php
/**
 * 管理画面 - 承認済み会員一覧
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// ページネーション設定
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 承認済みかつ退会していない会員の総数を取得
$countSql = "SELECT COUNT(*) FROM registrations WHERE status = 'approved' AND is_withdrawn = FALSE";
$countStmt = $db->query($countSql);
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// 承認済みかつ退会していない会員のみを取得（ページネーション付き）
$sql = "SELECT 
        id, 
        family_name, 
        first_name, 
        family_name_kana, 
        first_name_kana,
        postal_code,
        prefecture,
        city_address,
        building_name,
        mobile_number,
        email,
        approved_at,
        'FOCJ-' || LPAD(id::text, 5, '0') as member_number,
        FALSE as admission_fee_paid,  -- 入会金支払い（今後実装）
        FALSE as annual_fee_paid       -- 年会費支払い（今後実装）
    FROM registrations 
    WHERE status = 'approved' AND is_withdrawn = FALSE
    ORDER BY approved_at DESC, id DESC
    LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/C1_members-list.html');

// アセットパスを調整
$html = str_replace('href="assets/', 'href="/templates/member-management/assets/', $html);
$html = str_replace('src="assets/', 'src="/templates/member-management/assets/', $html);

// ユーザー名を表示
$username = $_SESSION['admin_username'] ?? 'admin';
$html = str_replace('username01', h($username), $html);

// ログアウトリンクを調整
$html = str_replace('action="0_login.html"', 'action="logout.php"', $html);

// メニューリンクを調整
$html = str_replace('href="A2_registration-list.html"', 'href="registration-list.php"', $html);
$html = str_replace('href="B1_edit-mail-index.html"', 'href="edit-mail.php"', $html);
$html = str_replace('href="C1_members-list.html"', 'href="members-list.php"', $html);

// membership_feesテーブルから支払い状況を取得
$memberIds = array_column($members, 'id');
$feeStatus = [];
if (!empty($memberIds)) {
    $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
    $sql = "SELECT 
            r.id as registration_id,
            mf.payment_status as entry_fee_status,
            mf.annual_fee
        FROM registrations r
        LEFT JOIN members m ON m.email = r.email
        LEFT JOIN membership_fees mf ON mf.member_id = m.member_id
        WHERE r.id IN ($placeholders)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($memberIds);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $feeStatus[$row['registration_id']] = [
            'entry_fee' => $row['entry_fee_status'] ?? '未払い',
            'annual_fees' => json_decode($row['annual_fee'] ?? '[]', true)
        ];
    }
}

// 会員リストを生成
$memberRows = '';
foreach ($members as $member) {
    // 承認日
    $approvedDate = $member['approved_at'] ? date('Y/n/j', strtotime($member['approved_at'])) : '-';
    
    // 会員番号（IDの下4桁を表示）
    $memberNumber = substr($member['member_number'], -4);
    
    // 氏名
    $fullName = h($member['family_name'] . ' ' . $member['first_name']);
    
    // フリガナ
    $fullNameKana = h($member['family_name_kana'] . ' ' . $member['first_name_kana']);
    
    // 住所
    $address = h($member['prefecture'] . $member['city_address']);
    if ($member['building_name']) {
        $address .= ' ' . h($member['building_name']);
    }
    
    // 入会金の支払い状況
    $entryFeeStatus = $feeStatus[$member['id']]['entry_fee'] ?? '未払い';
    $entryFeeClass = $entryFeeStatus === '支払い済み' ? 'fee-paid' : 'fee-unpaid';
    $entryFeeText = $entryFeeStatus === '支払い済み' ? '済' : '未';
    
    // 現在年度の年会費状況を確認
    $currentYear = date('Y');
    $annualFeeStatus = '未払い';
    $annualFees = $feeStatus[$member['id']]['annual_fees'] ?? [];
    foreach ($annualFees as $fee) {
        if ($fee['year'] == $currentYear) {
            $annualFeeStatus = $fee['status'];
            break;
        }
    }
    $annualFeeClass = $annualFeeStatus === '支払い済み' ? 'fee-paid' : 'fee-unpaid';
    $annualFeeText = $annualFeeStatus === '支払い済み' ? '済' : '未';
    
    // members.member_idを取得
    $sql = "SELECT member_id FROM members WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->execute([':email' => $member['email']]);
    $memberIdResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $realMemberId = $memberIdResult['member_id'] ?? 0;
    
    $memberRows .= '<tr>
                    <td>' . $approvedDate . '</td>
                    <td>' . $memberNumber . '</td>
                    <td>' . $fullName . '</td>
                    <td>' . $fullNameKana . '</td>
                    <td>' . $address . '</td>
                    <td>' . h($member['mobile_number']) . '</td>
                    <td>' . h($member['email']) . '</td>
                    <td><button class="fee-button ' . $entryFeeClass . '" onclick="openFeeModal(' . $realMemberId . ', \'entry\')">' . $entryFeeText . '</button></td>
                    <td><button class="fee-button ' . $annualFeeClass . '" onclick="openFeeModal(' . $realMemberId . ', \'annual\')">' . $annualFeeText . '</button></td>
                    <td><a href="member-detail.php?id=' . $member['id'] . '" class="button button--line button--small">表示</a></td>
                  </tr>';
}

// 会員がいない場合
if (empty($memberRows)) {
    $memberRows = '<tr><td colspan="10" style="text-align: center; padding: 40px;">承認済みの会員はまだいません</td></tr>';
}

// サンプルデータを実際のデータで置換
$html = preg_replace('/<tbody>.*?<\/tbody>/s', '<tbody>' . $memberRows . '</tbody>', $html);

// ページネーションボタンを設定
$prevButton = '';
$nextButton = '';

if ($page > 1) {
    $prevPage = $page - 1;
    $prevButton = '<a href="members-list.php?page=' . $prevPage . '" class="button button--line">前の10件</a>';
} else {
    $prevButton = '<span class="button button--line button--disable">前の10件</span>';
}

if ($page < $totalPages) {
    $nextPage = $page + 1;
    $nextButton = '<a href="members-list.php?page=' . $nextPage . '" class="button button--line">次の10件</a>';
} else {
    $nextButton = '<span class="button button--line button--disable">次の10件</span>';
}

// ページ情報を含むページネーションセクションを作成
$pageInfo = '';
if ($totalCount > 0) {
    $pageInfo = '<span style="margin: 0 20px;">ページ ' . $page . ' / ' . $totalPages . ' (全' . $totalCount . '件)</span>';
}

// ページネーションボタンを置換
$html = preg_replace(
    '/<div class="button-area pagenation">.*?<\/div>/s',
    '<div class="button-area pagenation">' . $prevButton . $pageInfo . $nextButton . '</div>',
    $html
);

// CSV出力ボタンのリンクを調整
$html = str_replace('href="#"', 'href="export-members-csv.php"', $html);

// モーダルとスタイル、JavaScriptを追加
$modalHtml = '
<!-- 会費管理モーダル -->
<div id="feeModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">会費管理</h3>
            <span class="modal-close" onclick="closeFeeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modalContent">
                <!-- 動的にコンテンツが挿入される -->
            </div>
        </div>
    </div>
</div>

<style>
/* モーダルのスタイル */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #1a1a1a;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #444;
    width: 80%;
    max-width: 800px;
    border-radius: 8px;
    color: white;
}

.modal-header {
    padding: 20px;
    background-color: #2a2a2a;
    border-bottom: 1px solid #444;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px 8px 0 0;
}

.modal-header h3 {
    margin: 0;
    color: white;
}

.modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.modal-close:hover,
.modal-close:focus {
    color: black;
}

.modal-body {
    padding: 20px;
}

/* 会費ボタンのスタイル */
.fee-button {
    padding: 4px 12px;
    border: 1px solid;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    background: #1a1a1a;
    color: white;
}

.fee-paid {
    border-color: #28a745;
    background: #1a1a1a;
    color: #28a745;
}

.fee-unpaid {
    border-color: #dc3545;
    background: #1a1a1a;
    color: #dc3545;
}

.fee-button:hover {
    opacity: 0.8;
}

/* フォームスタイル */
.fee-form {
    margin-bottom: 20px;
}

.fee-form h4 {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #444;
    color: white;
}

.form-group {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.form-group label {
    width: 150px;
    font-weight: bold;
    color: white;
}

.form-group span {
    color: white;
}

.form-group input,
.form-group select,
.form-group textarea {
    flex: 1;
    padding: 8px;
    border: 1px solid #444;
    border-radius: 4px;
    background-color: #2a2a2a;
    color: white;
}

.form-group textarea {
    resize: vertical;
    min-height: 60px;
}

.fee-history {
    margin-top: 30px;
}

.fee-history h4 {
    margin-bottom: 15px;
    color: white;
}

.fee-history table {
    width: 100%;
    border-collapse: collapse;
}

.fee-history th,
.fee-history td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #444;
    color: white;
}

.fee-history th {
    background-color: #2a2a2a;
    font-weight: bold;
    color: white;
}

.button-group {
    margin-top: 20px;
    text-align: right;
}

.button-group button {
    margin-left: 10px;
}

.add-year-button {
    margin-top: 10px;
}
</style>

<script>
let currentMemberId = null;
let currentFeeType = null;

function openFeeModal(memberId, feeType) {
    currentMemberId = memberId;
    currentFeeType = feeType;
    
    const modal = document.getElementById("feeModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalContent = document.getElementById("modalContent");
    
    modalTitle.textContent = feeType === "entry" ? "入会金管理" : "年会費管理";
    modalContent.innerHTML = "読み込み中...";
    
    modal.style.display = "block";
    
    // APIから会費情報を取得
    fetch(`api/manage-fees.php?action=get_fees&member_id=${memberId}`)
        .then(response => response.json())
        .then(data => {
            if (feeType === "entry") {
                showEntryFeeForm(data);
            } else {
                showAnnualFeeForm(data);
            }
        })
        .catch(error => {
            modalContent.innerHTML = `<div style="color: red;">エラーが発生しました: ${error}</div>`;
        });
}

function closeFeeModal() {
    document.getElementById("feeModal").style.display = "none";
}

function showEntryFeeForm(data) {
    const modalContent = document.getElementById("modalContent");
    const currentStatus = data.payment_status || "未払い";
    const paymentDate = data.entry_fee_payment_date ? data.entry_fee_payment_date.split(" ")[0] : "";
    
    modalContent.innerHTML = `
        <div class="fee-form">
            <h4>会員情報</h4>
            <div class="form-group">
                <label>会員番号:</label>
                <span>${data.member_number || "-"}</span>
            </div>
            <div class="form-group">
                <label>氏名:</label>
                <span>${data.member_name || "-"}</span>
            </div>
            
            <h4>入会金情報</h4>
            <div class="form-group">
                <label>金額:</label>
                <input type="number" id="entryFeeAmount" value="${data.entry_fee || 300000}" readonly style="background-color: #f0f0f0;">
            </div>
            <div class="form-group">
                <label>ステータス:</label>
                <select id="entryFeeStatus">
                    <option value="未払い" ${currentStatus === "未払い" ? "selected" : ""}>未払い</option>
                    <option value="支払い済み" ${currentStatus === "支払い済み" ? "selected" : ""}>支払い済み</option>
                    <option value="支払い期限切れ" ${currentStatus === "支払い期限切れ" ? "selected" : ""}>期限切れ</option>
                </select>
            </div>
            <div class="form-group">
                <label>支払い日:</label>
                <input type="date" id="entryFeePaymentDate" value="${paymentDate}">
            </div>
            <div class="form-group">
                <label>支払い方法:</label>
                <select id="entryFeePaymentMethod">
                    <option value="">選択してください</option>
                    <option value="銀行振込" ${data.entry_fee_payment_method === "銀行振込" ? "selected" : ""}>銀行振込</option>
                    <option value="クレジットカード" ${data.entry_fee_payment_method === "クレジットカード" ? "selected" : ""}>クレジットカード</option>
                    <option value="現金" ${data.entry_fee_payment_method === "現金" ? "selected" : ""}>現金</option>
                    <option value="その他" ${data.entry_fee_payment_method === "その他" ? "selected" : ""}>その他</option>
                </select>
            </div>
            <div class="form-group">
                <label>領収書番号:</label>
                <input type="text" id="entryFeeReceiptNumber" value="${data.entry_fee_receipt_number || ""}">
            </div>
            <div class="form-group">
                <label>備考:</label>
                <textarea id="entryFeeNotes">${data.entry_fee_notes || ""}</textarea>
            </div>
        </div>
        
        <div class="button-group">
            <button class="button button--line" onclick="closeFeeModal()" style="background: #2a2a2a; color: white; border-color: #666;">キャンセル</button>
            <button class="button button--primary" onclick="saveEntryFee()" style="background: #0066ff; color: white; border-color: #0066ff;">保存</button>
        </div>
    `;
}

function showAnnualFeeForm(data) {
    const modalContent = document.getElementById("modalContent");
    const annualFees = data.annual_fee || [];
    const currentYear = new Date().getFullYear();
    
    // 年会費フォームHTML
    let formHtml = `
        <div class="fee-form">
            <h4>会員情報</h4>
            <div class="form-group">
                <label>会員番号:</label>
                <span>${data.member_number || "-"}</span>
            </div>
            <div class="form-group">
                <label>氏名:</label>
                <span>${data.member_name || "-"}</span>
            </div>
            
            <h4>年会費管理</h4>
            <button class="button button--line add-year-button" onclick="addNewYear()" style="background: #2a2a2a; color: white; border-color: #666;">新規年度追加</button>
    `;
    
    // 年度別の入力フォーム（最新年度のみ表示、他は履歴に）
    if (annualFees.length > 0) {
        const latestFee = annualFees[0];
        const paymentDate = latestFee.payment_date ? latestFee.payment_date.split(" ")[0] : "";
        
        formHtml += `
            <div style="margin-top: 20px; padding: 15px; border: 1px solid #444; border-radius: 4px; background: #2a2a2a;">
                <h5 style="color: white; margin-top: 0;">${latestFee.year}年度</h5>
                <div class="form-group">
                    <label>金額:</label>
                    <input type="number" id="annualFeeAmount" value="${latestFee.amount || 50000}">
                </div>
                <div class="form-group">
                    <label>ステータス:</label>
                    <select id="annualFeeStatus">
                        <option value="未払い" ${latestFee.status === "未払い" ? "selected" : ""}>未払い</option>
                        <option value="支払い済み" ${latestFee.status === "支払い済み" ? "selected" : ""}>支払い済み</option>
                        <option value="免除" ${latestFee.status === "免除" ? "selected" : ""}>免除</option>
                        <option value="期限切れ" ${latestFee.status === "期限切れ" ? "selected" : ""}>期限切れ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>支払い日:</label>
                    <input type="date" id="annualFeePaymentDate" value="${paymentDate}">
                </div>
                <div class="form-group">
                    <label>支払い方法:</label>
                    <select id="annualFeePaymentMethod">
                        <option value="">選択してください</option>
                        <option value="銀行振込" ${latestFee.payment_method === "銀行振込" ? "selected" : ""}>銀行振込</option>
                        <option value="クレジットカード" ${latestFee.payment_method === "クレジットカード" ? "selected" : ""}>クレジットカード</option>
                        <option value="現金" ${latestFee.payment_method === "現金" ? "selected" : ""}>現金</option>
                        <option value="その他" ${latestFee.payment_method === "その他" ? "selected" : ""}>その他</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>領収書番号:</label>
                    <input type="text" id="annualFeeReceiptNumber" value="${latestFee.receipt_number || ""}">
                </div>
                <div class="form-group">
                    <label>備考:</label>
                    <textarea id="annualFeeNotes">${latestFee.notes || ""}</textarea>
                </div>
                <input type="hidden" id="annualFeeYear" value="${latestFee.year}">
            </div>
        `;
    }
    
    formHtml += `</div>`;
    
    // 履歴テーブル
    if (annualFees.length > 0) {
        formHtml += `
            <div class="fee-history">
                <h4>年会費支払い履歴</h4>
                <table>
                    <thead>
                        <tr>
                            <th>年度</th>
                            <th>金額</th>
                            <th>ステータス</th>
                            <th>支払い日</th>
                            <th>支払い方法</th>
                            <th>領収書番号</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        annualFees.forEach(fee => {
            const statusColor = fee.status === "支払い済み" ? "green" : 
                              fee.status === "免除" ? "blue" : "red";
            formHtml += `
                <tr>
                    <td>${fee.year}年</td>
                    <td>¥${(fee.amount || 0).toLocaleString()}</td>
                    <td style="color: ${statusColor};">${fee.status || "未払い"}</td>
                    <td>${fee.payment_date ? fee.payment_date.split(" ")[0] : "-"}</td>
                    <td>${fee.payment_method || "-"}</td>
                    <td>${fee.receipt_number || "-"}</td>
                </tr>
            `;
        });
        
        formHtml += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    formHtml += `
        <div class="button-group">
            <button class="button button--line" onclick="closeFeeModal()" style="background: #2a2a2a; color: white; border-color: #666;">キャンセル</button>
            <button class="button button--primary" onclick="saveAnnualFee()" style="background: #0066ff; color: white; border-color: #0066ff;">保存</button>
        </div>
    `;
    
    modalContent.innerHTML = formHtml;
}

function saveEntryFee() {
    const data = {
        status: document.getElementById("entryFeeStatus").value,
        payment_date: document.getElementById("entryFeePaymentDate").value,
        payment_method: document.getElementById("entryFeePaymentMethod").value,
        receipt_number: document.getElementById("entryFeeReceiptNumber").value,
        notes: document.getElementById("entryFeeNotes").value
    };
    
    fetch(`api/manage-fees.php?action=update_entry_fee&member_id=${currentMemberId}`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert("入会金情報を更新しました");
            location.reload();
        } else {
            alert("更新に失敗しました");
        }
    });
}

function saveAnnualFee() {
    const yearElement = document.getElementById("annualFeeYear");
    if (!yearElement) {
        alert("年度が選択されていません");
        return;
    }
    
    const data = {
        year: yearElement.value,
        amount: document.getElementById("annualFeeAmount").value,
        status: document.getElementById("annualFeeStatus").value,
        payment_date: document.getElementById("annualFeePaymentDate").value,
        payment_method: document.getElementById("annualFeePaymentMethod").value,
        receipt_number: document.getElementById("annualFeeReceiptNumber").value,
        notes: document.getElementById("annualFeeNotes").value
    };
    
    fetch(`api/manage-fees.php?action=update_annual_fee&member_id=${currentMemberId}`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert("年会費情報を更新しました");
            location.reload();
        } else {
            alert("更新に失敗しました");
        }
    });
}

function addNewYear() {
    const year = prompt("追加する年度を入力してください（例：2026）");
    if (!year || isNaN(year)) {
        return;
    }
    
    const amount = prompt("年会費金額を入力してください（デフォルト：50000）", "50000");
    if (!amount || isNaN(amount)) {
        return;
    }
    
    fetch(`api/manage-fees.php?action=add_annual_fee&member_id=${currentMemberId}`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({year: parseInt(year), amount: parseInt(amount)})
    })
    .then(response => response.json())
    .then(result => {
        if (result.error) {
            alert(result.error);
        } else if (result.success) {
            alert("新しい年度を追加しました");
            openFeeModal(currentMemberId, "annual");
        }
    });
}

// モーダル外クリックで閉じる
window.onclick = function(event) {
    const modal = document.getElementById("feeModal");
    if (event.target == modal) {
        closeFeeModal();
    }
}
</script>
';

// </body>タグの前にモーダルHTMLを挿入
$html = str_replace('</body>', $modalHtml . '</body>', $html);

echo $html;