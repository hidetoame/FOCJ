<?php
/**
 * 管理画面 - ダッシュボード
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(dirname(__FILE__)) . '/config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// テンプレート読み込み
$html = file_get_contents(getTemplateFilePath('member-management/A1_admin-index.html'));

// アセットパスを調整
$html = str_replace('href="assets/', 'href="' . ADMIN_TEMPLATE_WEB_PATH . '/assets/', $html);
$html = str_replace('src="assets/', 'src="' . ADMIN_TEMPLATE_WEB_PATH . '/assets/', $html);

// ユーザー名を表示
$username = $_SESSION['admin_username'] ?? 'admin';
$html = str_replace('username01', htmlspecialchars($username), $html);

// ログアウトリンクを調整
$html = str_replace('action="0_login.html"', 'action="logout.php"', $html);

// ログアウトボタンの隣に入会金・年会費管理ボタンを追加
$feeButton = '<button type="button" onclick="openFeeMasterModal()" class="button button--primary" style="margin-right: 10px;">入会金・年会費管理</button>';
$html = str_replace(
    '<button type="submit" class="button button--line">ログアウト</button>',
    $feeButton . '<button type="submit" class="button button--line">ログアウト</button>',
    $html
);

// メニューリンクを調整
$html = str_replace('href="A2_registration-list.html"', 'href="registration-list.php"', $html);
$html = str_replace('href="B1_edit-mail-index.html"', 'href="edit-mail.php"', $html);
$html = str_replace('href="C1_members-list.html"', 'href="members-list.php"', $html);

// メニューに入会金・年会費管理を追加（インラインスタイルで幅調整）
$feeMenuLink = '<a href="#" onclick="openFeeMasterModal(); return false;" class="admin-nav-item">入会金・年会費管理</a>';

// モーダルHTML
$modalHtml = '
<!-- 入会金・年会費マスタ管理モーダル -->
<div id="feeMasterModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>入会金・年会費マスタ管理</h3>
            <span class="modal-close" onclick="closeFeeMasterModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="feeMasterContent">
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
    background-color: rgba(0,0,0,0.6);
}

.modal-content {
    background-color: #1a1a1a;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #444;
    width: 80%;
    max-width: 900px;
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
    color: white;
}

.modal-body {
    padding: 20px;
}

.fee-section {
    margin-bottom: 30px;
    padding: 20px;
    border: 1px solid #444;
    border-radius: 8px;
    background: #2a2a2a;
}

.fee-section h4 {
    margin-top: 0;
    margin-bottom: 20px;
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

.form-group input,
.form-group textarea {
    flex: 1;
    padding: 10px;
    border: 1px solid #444;
    border-radius: 4px;
    background-color: #3a3a3a;
    color: white;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 60px;
}

.annual-fee-list {
    margin-top: 20px;
}

.annual-fee-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding: 10px;
    background: #3a3a3a;
    border-radius: 4px;
}

.annual-fee-item input {
    margin: 0 10px;
    padding: 5px 10px;
    border: 1px solid #444;
    border-radius: 4px;
    background: #2a2a2a;
    color: white;
}

.annual-fee-item button {
    margin-left: 10px;
    padding: 5px 15px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.annual-fee-item button:hover {
    background: #c82333;
}

.button-group {
    margin-top: 30px;
    text-align: right;
    padding-top: 20px;
    border-top: 1px solid #444;
}

.button-group button {
    margin-left: 10px;
    padding: 10px 25px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
}

.add-year-button {
    margin-top: 10px;
    padding: 8px 20px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.add-year-button:hover {
    background: #218838;
}
</style>

<script>
let feeMasterData = null;

function openFeeMasterModal() {
    const modal = document.getElementById("feeMasterModal");
    const content = document.getElementById("feeMasterContent");
    
    modal.style.display = "block";
    content.innerHTML = "読み込み中...";
    
    // APIから設定データを取得
    fetch("api/manage-fees.php?action=getMaster")
        .then(response => response.json())
        .then(data => {
            feeMasterData = data;
            showFeeMasterForm(data);
        })
        .catch(error => {
            content.innerHTML = `<div style="color: red;">エラーが発生しました: ${error}</div>`;
        });
}

function closeFeeMasterModal() {
    document.getElementById("feeMasterModal").style.display = "none";
}

function showFeeMasterForm(data) {
    const content = document.getElementById("feeMasterContent");
    let annualFeesHtml = "";
    
    // 現在の年を取得
    const currentYear = new Date().getFullYear();
    
    // 2022年から現在の年までの配列を作成
    const years = [];
    for (let year = 2022; year <= currentYear; year++) {
        years.push(year);
    }
    
    // 既存のデータがない場合は初期化
    if (!data.annual_fees || data.annual_fees.length === 0) {
        data.annual_fees = [];
    }
    
    // 各年度のデータを生成（存在しない年度は追加）
    years.forEach(year => {
        let fee = data.annual_fees.find(f => f.year === year);
        if (!fee) {
            fee = { year: year, amount: 50000, description: year + "年度年会費" };
            data.annual_fees.push(fee);
        }
    });
    
    // 年度順にソート（新しい年を上に）
    data.annual_fees.sort((a, b) => b.year - a.year);
    
    // 年会費リストを生成
    data.annual_fees.forEach((fee, index) => {
        annualFeesHtml += `
            <div class="annual-fee-item" data-index="${index}">
                <label style="color: white; width: 100px;">${fee.year}年度:</label>
                <input type="number" id="annual_amount_${index}" value="${parseInt(fee.amount)}" style="width: 150px;">
                <span style="color: white; margin-left: 10px;">円</span>
            </div>
        `;
    });
    
    content.innerHTML = `
        <div class="fee-section">
            <h4>入会金設定</h4>
            <div class="form-group">
                <label>金額（円）:</label>
                <input type="number" id="entryFeeAmount" value="${parseInt(data.entry_fee) || 300000}">
            </div>
        </div>
        
        <div class="fee-section">
            <h4>年会費設定</h4>
            <div class="annual-fee-list">
                <div id="annualFeesList">
                    ${annualFeesHtml}
                </div>
            </div>
        </div>
        
        <div class="button-group">
            <button class="button button--line" onclick="closeFeeMasterModal()" style="background: #2a2a2a; color: white; border-color: #666;">キャンセル</button>
            <button class="button button--primary" onclick="saveFeeMaster()" style="background: #0066ff; color: white;">保存</button>
        </div>
    `;
}


function saveFeeMaster() {
    // フォームからデータを収集
    const entryFee = document.getElementById("entryFeeAmount").value;
    
    // 年会費データを収集
    const annualFees = [];
    document.querySelectorAll(".annual-fee-item").forEach((item, index) => {
        const amount = document.getElementById(`annual_amount_${index}`).value;
        if (feeMasterData.annual_fees[index]) {
            annualFees.push({
                year: feeMasterData.annual_fees[index].year,
                amount: parseInt(amount),
                description: feeMasterData.annual_fees[index].year + "年度年会費"
            });
        }
    });
    
    const data = {
        entry_fee: parseInt(entryFee),
        entry_fee_description: "",
        annual_fee_description: "",
        annual_fees: annualFees
    };
    
    // APIに送信
    fetch("api/manage-fees.php?action=updateMaster", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log("Response status:", response.status);
        return response.json();
    })
    .then(result => {
        console.log("Response data:", result);
        if (result.success) {
            alert("設定を保存しました");
            closeFeeMasterModal();
        } else {
            console.error("Save failed:", result);
            alert("保存に失敗しました: " + (result.error || ""));
        }
    })
    .catch(error => {
        console.error("Save error:", error);
        alert("エラーが発生しました: " + error);
    });
}

// モーダル外クリックで閉じる
window.onclick = function(event) {
    const modal = document.getElementById("feeMasterModal");
    if (event.target == modal) {
        closeFeeMasterModal();
    }
}
</script>
';

// 左側のメニューに入会金・年会費管理を追加（承認済み会員一覧の後）
$feeMenuItem = '
        <li class="admin-menu-item">
          <a href="#" onclick="openFeeMasterModal(); return false;" class="admin-menu-link">入会金・年会費管理</a>
        </li>';

$html = str_replace(
    '        </li>
      </ul>
    </nav>',
    '        </li>' . $feeMenuItem . '
      </ul>
    </nav>',
    $html
);

// </body>タグの前にモーダルHTMLを挿入
$html = str_replace('</body>', $modalHtml . '</body>', $html);

echo $html;