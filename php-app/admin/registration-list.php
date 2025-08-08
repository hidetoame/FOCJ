<?php
/**
 * 管理画面 - 会員希望申請一覧
 */
// データベース接続（config.phpがsession_start()を呼ぶ）
require_once '../config/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
$db = Database::getInstance()->getConnection();

// ステータスフィルタを取得（デフォルトは未対応）
$statusFilter = $_GET['status'] ?? 'pending';

// ページネーション設定
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// WHERE句を構築
$whereClause = '';
$params = [];
if ($statusFilter === 'pending') {
    $whereClause = "WHERE status = 'pending' AND is_withdrawn = FALSE";
} elseif ($statusFilter === 'approved') {
    $whereClause = "WHERE status = 'approved' AND is_withdrawn = FALSE";
} elseif ($statusFilter === 'withdrawn') {
    $whereClause = "WHERE is_withdrawn = TRUE";
} elseif ($statusFilter === 'rejected') {
    $whereClause = "WHERE status = 'rejected' AND is_withdrawn = FALSE";
} elseif ($statusFilter === 'all') {
    $whereClause = "";
}

// 総件数を取得
$countSql = "SELECT COUNT(*) as total FROM registrations " . $whereClause;
$countStmt = $db->query($countSql);
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCount / $perPage);

// 申込一覧を取得（ページネーション対応）
$sql = "SELECT * FROM registrations " . $whereClause . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// テンプレート読み込み
$html = file_get_contents('/var/www/html/templates/member-management/A2_registration-list.html');

// アセットパスを調整
$html = str_replace('href="assets/', 'href="/templates/member-management/assets/', $html);
$html = str_replace('src="assets/', 'src="/templates/member-management/assets/', $html);

// ユーザー名を表示
$username = $_SESSION['admin_username'] ?? 'admin';
$html = str_replace('username01', htmlspecialchars($username), $html);

// ログアウトリンクを調整
$html = str_replace('action="0_login.html"', 'action="logout.php"', $html);

// メニューリンクを調整
$html = str_replace('href="A2_registration-list.html"', 'href="registration-list.php"', $html);
$html = str_replace('href="B1_edit-mail-index.html"', 'href="edit-mail.php"', $html);
$html = str_replace('href="C1_members-list.html"', 'href="members-list.php"', $html);

// 詳細リンクを調整
$html = str_replace('href="A3_registration-detail.html"', 'href="registration-detail.php?id=1"', $html);

// ステータスフィルタセクションを追加（タイトルと同じ行に配置）
$filterSection = '
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
    <h2 class="admin-contents-title" style="margin: 0;">会員希望申請一覧</h2>
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-weight: bold; margin-right: 10px;">ステータス絞り込み：</span>
        <a href="registration-list.php?status=pending" class="button ' . ($statusFilter === 'pending' ? 'button--primary' : 'button--line') . ' button--small">未対応</a>
        <a href="registration-list.php?status=approved" class="button ' . ($statusFilter === 'approved' ? 'button--primary' : 'button--line') . ' button--small">承認済</a>
        <a href="registration-list.php?status=rejected" class="button ' . ($statusFilter === 'rejected' ? 'button--primary' : 'button--line') . ' button--small">否認済</a>
        <a href="registration-list.php?status=withdrawn" class="button ' . ($statusFilter === 'withdrawn' ? 'button--primary' : 'button--line') . ' button--small">退会済</a>
        <a href="registration-list.php?status=all" class="button ' . ($statusFilter === 'all' ? 'button--primary' : 'button--line') . ' button--small">すべて</a>
    </div>
</div>
';

// 元のタイトルをフィルタセクションで置換
$html = str_replace(
    '<h2 class="admin-contents-title">会員希望申請一覧</h2>',
    $filterSection,
    $html
);

// テーブルのデータを動的に生成
$tableRows = '';
foreach ($registrations as $reg) {
    $statusText = '';
    // 退会済みチェック
    if ($reg['is_withdrawn']) {
        $statusText = '<span style="color: gray;">退会済</span>';
    } else {
        switch ($reg['status']) {
            case 'pending':
                $statusText = '未対応';
                break;
            case 'approved':
                $statusText = '<span style="color: green;">承認済</span>';
                break;
            case 'rejected':
                $statusText = '<span style="color: red;">否認</span>';
                break;
        }
    }
    
    $address = h($reg['prefecture'] . $reg['city_address'] . ' ' . $reg['building_name']);
    
    $tableRows .= '
        <tr>
            <td>' . date('Y/n/j', strtotime($reg['created_at'])) . '</td>
            <td>' . h($reg['family_name'] . ' ' . $reg['first_name']) . '</td>
            <td>' . h($reg['family_name_kana'] . ' ' . $reg['first_name_kana']) . '</td>
            <td>' . $address . '</td>
            <td>' . h($reg['mobile_number']) . '</td>
            <td>' . h($reg['email']) . '</td>
            <td><a href="registration-detail.php?id=' . $reg['id'] . '" class="button button--line button--small">表示</a></td>
            <td>' . $statusText . '</td>
        </tr>';
}

// サンプルデータを実データで置き換え
if ($tableRows) {
    $html = preg_replace('/<tbody>.*?<\/tbody>/s', '<tbody>' . $tableRows . '</tbody>', $html);
} else {
    $html = preg_replace('/<tbody>.*?<\/tbody>/s', '<tbody><tr><td colspan="8" style="text-align: center;">申請データがありません。</td></tr></tbody>', $html);
}

// ページネーションボタンを作成（ステータスパラメータを維持）
$prevButton = '';
$nextButton = '';

$statusParam = $statusFilter ? '&status=' . $statusFilter : '';

if ($page > 1) {
    $prevPage = $page - 1;
    $prevButton = '<a href="?page=' . $prevPage . $statusParam . '" class="button button--line">前の10件</a>';
} else {
    $prevButton = '<span class="button button--line button--disable">前の10件</span>';
}

if ($page < $totalPages) {
    $nextPage = $page + 1;
    $nextButton = '<a href="?page=' . $nextPage . $statusParam . '" class="button button--line">次の10件</a>';
} else {
    $nextButton = '<span class="button button--line button--disable">次の10件</span>';
}

// ページネーションセクションを置換
$paginationHtml = '<div class="button-area pagenation">
    ' . $prevButton . '
    <span style="margin: 0 20px;">ページ ' . $page . ' / ' . $totalPages . ' (全' . $totalCount . '件)</span>
    ' . $nextButton . '
</div>';

$html = preg_replace('/<div class="button-area pagenation">.*?<\/div>/s', $paginationHtml, $html);

echo $html;