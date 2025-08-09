// FOCJ管理画面用JavaScript

// ログイン状態チェック
function checkLoginStatus() {
    const token = localStorage.getItem('admin_token');
    const username = localStorage.getItem('admin_username');
    
    if (!token || !username) {
        // ログインしていない場合はログインページにリダイレクト
        window.location.href = '/';
        return false;
    }
    
    // ユーザー名を表示
    const usernameElement = document.querySelector('.header-username');
    if (usernameElement) {
        usernameElement.textContent = username;
    }
    
    return true;
}

// ログアウト機能
function logout() {
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_username');
    window.location.href = '/';
}

// ページ読み込み時の処理
document.addEventListener('DOMContentLoaded', function() {
    // ログイン状態をチェック
    checkLoginStatus();
    
    // ログアウトボタンのイベントリスナー
    const logoutButton = document.querySelector('.header-logout button');
    if (logoutButton) {
        logoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
    
    // フォームのデフォルト送信を無効化
    const logoutForm = document.querySelector('.header-logout form');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            logout();
        });
    }
});

// メニューリンクの処理
document.addEventListener('DOMContentLoaded', function() {
    const menuLinks = document.querySelectorAll('.admin-menu-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            
            // リンク先に遷移
            if (href.startsWith('/')) {
                window.location.href = href;
            } else if (href.includes('.html')) {
                const path = href.replace('.html', '');
                window.location.href = path;
            } else {
                window.location.href = href;
            }
        });
    });
    
    // 申請一覧ページの初期化
    if (window.location.pathname === '/registration-list') {
        initRegistrationList();
    }
    
    // 申請詳細ページの初期化
    if (window.location.pathname === '/registration-detail') {
        initRegistrationDetail();
    }
    
    // 承認確認ページの初期化
    if (window.location.pathname === '/registration-approve') {
        initRegistrationApprove();
    }
    
    // 却下確認ページの初期化
    if (window.location.pathname === '/registration-reject') {
        initRegistrationReject();
    }
});

// 申請一覧表示機能
async function initRegistrationList() {
    const applicationsList = document.getElementById('applications-list');
    if (!applicationsList) return;
    
    try {
        // バックエンドAPIからデータを取得
        const response = await fetch('http://localhost:8020/api/admin/applications', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
            }
        });
        
        if (response.ok) {
            const applications = await response.json();
            renderApplicationsList(applications);
            updatePagination(applications.length);
        } else {
            applicationsList.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">データの取得に失敗しました</td></tr>';
            updatePagination(0);
        }
    } catch (error) {
        console.error('申請一覧取得エラー:', error);
        applicationsList.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">データの取得に失敗しました</td></tr>';
        updatePagination(0);
    }
}

// 申請一覧のレンダリング
function renderApplicationsList(applications) {
    const applicationsList = document.getElementById('applications-list');
    if (!applicationsList) return;
    
    if (!applications || applications.length === 0) {
        applicationsList.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">申請はありません</td></tr>';
        return;
    }
    
    const html = applications.map(app => `
        <tr>
            <td>${formatDate(app.created_at)}</td>
            <td>${app.family_name} ${app.first_name}</td>
            <td>${app.family_name_kana} ${app.first_name_kana}</td>
            <td>${app.prefecture}${app.city_address}${app.building_name || ''}</td>
            <td>${app.mobile_number}</td>
            <td>${app.email}</td>
            <td><a href="/registration-detail?id=${app.member_id}" class="button button--line button--small">表示</a></td>
            <td>${getStatusText(app.application_status)}</td>
        </tr>
    `).join('');
    
    applicationsList.innerHTML = html;
}

// 日付フォーマット
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP');
}

// ステータステキスト変換
function getStatusText(status) {
    const statusMap = {
        '申請中': '未対応',
        '審査中': '審査中',
        '承認済み': '承認',
        '却下': '非承認'
    };
    return statusMap[status] || status;
}

// ページネーション更新
function updatePagination(totalCount) {
    const prevButton = document.getElementById('prev-button');
    const nextButton = document.getElementById('next-button');
    
    if (!prevButton || !nextButton) return;
    
    const pageSize = 10;
    const currentPage = 1; // 現在は1ページ目固定
    
    // 前のページボタン（常に無効）
    prevButton.className = 'button button--line button--disable';
    prevButton.textContent = '前の10件';
    
    // 次のページボタン
    if (totalCount <= pageSize) {
        // データが10件以下なら無効
        nextButton.className = 'button button--line button--disable';
        nextButton.textContent = '次の10件';
    } else {
        // データが10件を超えるなら有効
        nextButton.className = 'button button--line';
        nextButton.textContent = '次の10件';
    }
}

// 申請詳細表示機能
async function initRegistrationDetail() {
    // URLパラメータからmember_idを取得
    const urlParams = new URLSearchParams(window.location.search);
    const memberId = urlParams.get('id');
    
    if (!memberId) {
        alert('申請IDが指定されていません');
        window.location.href = '/registration-list';
        return;
    }
    
    try {
        // バックエンドAPIから詳細データを取得
        const response = await fetch(`http://localhost:8020/api/admin/members/${memberId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
            }
        });
        
        console.log('API Response status:', response.status);
        
        if (response.ok) {
            const member = await response.json();
            console.log('Member data:', member);
            renderRegistrationDetail(member);
            
            // 承認ボタンにクリックイベントを追加
            setupApproveButton(memberId);
            
            // 却下ボタンにクリックイベントを追加
            setupRejectButton(memberId);
        } else {
            console.error('API Error status:', response.status);
            alert('申請詳細の取得に失敗しました');
            window.location.href = '/registration-list';
        }
    } catch (error) {
        console.error('申請詳細取得エラー:', error);
        console.error('Error details:', error.message);
        alert('申請詳細の取得に失敗しました');
        window.location.href = '/registration-list';
    }
}

// 承認ボタンの設定
function setupApproveButton(memberId) {
    const approveButton = document.querySelector('a[href*="registration-approve"]');
    if (approveButton) {
        approveButton.addEventListener('click', function(e) {
            e.preventDefault();
            // 承認確認画面に遷移（member_idをパラメータとして渡す）
            window.location.href = `/registration-approve?id=${memberId}`;
        });
    }
}

// 却下ボタンの設定
function setupRejectButton(memberId) {
    const rejectButton = document.querySelector('a[href*="registration-reject"]');
    if (rejectButton) {
        rejectButton.addEventListener('click', function(e) {
            e.preventDefault();
            // 却下確認画面に遷移（member_idをパラメータとして渡す）
            window.location.href = `/registration-reject?id=${memberId}`;
        });
    }
}

// 申請詳細のレンダリング
function renderRegistrationDetail(member) {
    console.log('Starting renderRegistrationDetail...');
    
    // 基本情報
    const elements = {
        'applicant-name': document.getElementById('applicant-name'),
        'applicant-name-kana': document.getElementById('applicant-name-kana'),
        'applicant-name-alphabet': document.getElementById('applicant-name-alphabet'),
        'applicant-address': document.getElementById('applicant-address'),
        'applicant-address-type': document.getElementById('applicant-address-type'),
        'applicant-mobile': document.getElementById('applicant-mobile'),
        'applicant-phone': document.getElementById('applicant-phone'),
        'applicant-birth': document.getElementById('applicant-birth'),
        'applicant-email': document.getElementById('applicant-email'),
        'applicant-occupation': document.getElementById('applicant-occupation'),
        'applicant-introduction': document.getElementById('applicant-introduction'),
        'applicant-dealer': document.getElementById('applicant-dealer'),
        'application-date': document.getElementById('application-date'),
        'application-status': document.getElementById('application-status'),
        'vehicle-model': document.getElementById('vehicle-model'),
        'vehicle-year': document.getElementById('vehicle-year'),
        'vehicle-color': document.getElementById('vehicle-color'),
        'vehicle-registration': document.getElementById('vehicle-registration'),
        'referrer-1-name': document.getElementById('referrer-1-name'),
        'referrer-1-dealer': document.getElementById('referrer-1-dealer'),
        'referrer-2-name': document.getElementById('referrer-2-name')
    };
    
    // 要素の存在確認
    for (const [id, element] of Object.entries(elements)) {
        if (!element) {
            console.error(`Element not found: ${id}`);
        } else {
            console.log(`Element found: ${id}`);
        }
    }
    
    // 基本情報
    if (elements['applicant-name']) elements['applicant-name'].textContent = `${member.family_name} ${member.first_name}`;
    if (elements['applicant-name-kana']) elements['applicant-name-kana'].textContent = `${member.family_name_kana} ${member.first_name_kana}`;
    if (elements['applicant-name-alphabet']) elements['applicant-name-alphabet'].textContent = member.name_alphabet;
    if (elements['applicant-address']) elements['applicant-address'].innerHTML = `〒${member.postal_code}<br>${member.prefecture}${member.city_address}${member.building_name || ''}`;
    if (elements['applicant-address-type']) elements['applicant-address-type'].textContent = member.address_type;
    if (elements['applicant-mobile']) elements['applicant-mobile'].textContent = member.mobile_number;
    if (elements['applicant-phone']) elements['applicant-phone'].textContent = member.phone_number || '-';
    if (elements['applicant-birth']) elements['applicant-birth'].textContent = formatBirthDate(member.birth_date);
    if (elements['applicant-email']) elements['applicant-email'].textContent = member.email;
    if (elements['applicant-occupation']) elements['applicant-occupation'].innerHTML = member.occupation.replace(/\n/g, '<br>');
    if (elements['applicant-introduction']) elements['applicant-introduction'].innerHTML = member.self_introduction.replace(/\n/g, '<br>');
    if (elements['applicant-dealer']) elements['applicant-dealer'].textContent = member.relationship_dealer || '-';
    if (elements['applicant-sales-person']) elements['applicant-sales-person'].textContent = member.sales_person || '-';
    
    // 申請情報
    if (elements['application-date']) elements['application-date'].textContent = formatDate(member.created_at);
    if (elements['application-status']) elements['application-status'].textContent = getStatusText(member.application_status);
    
            // 車両情報
        if (member.vehicles && member.vehicles.length > 0) {
            const vehicle = member.vehicles[0]; // 最初の車両を表示
            document.getElementById('vehicle-model').textContent = vehicle.model_name;
            document.getElementById('vehicle-year').textContent = vehicle.year;
            document.getElementById('vehicle-color').textContent = vehicle.color;
            document.getElementById('vehicle-registration').textContent = vehicle.registration_number;
        } else {
            document.getElementById('vehicle-model').textContent = '-';
            document.getElementById('vehicle-year').textContent = '-';
            document.getElementById('vehicle-color').textContent = '-';
            document.getElementById('vehicle-registration').textContent = '-';
        }
        
        // 紹介者情報
        if (member.referrers && member.referrers.length > 0) {
            // 紹介者-1（referrer_order = 1）
            const referrer1 = member.referrers.find(r => r.referrer_order === 1);
            if (referrer1) {
                document.getElementById('referrer-1-name').textContent = referrer1.referrer_name;
                document.getElementById('referrer-1-dealer').textContent = referrer1.referrer_dealer || '-';
            }
            
            // 紹介者-2（理事）（referrer_order = 2）
            const referrer2 = member.referrers.find(r => r.referrer_order === 2);
            if (referrer2) {
                document.getElementById('referrer-2-name').textContent = referrer2.referrer_name;
            }
        } else {
            document.getElementById('referrer-1-name').textContent = '-';
            document.getElementById('referrer-1-dealer').textContent = '-';
            document.getElementById('referrer-2-name').textContent = '-';
        }
}

// 生年月日フォーマット
function formatBirthDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return `${date.getFullYear()}年${date.getMonth() + 1}月 ${date.getDate()}日`;
}

// 承認確認画面の初期化
async function initRegistrationApprove() {
    const urlParams = new URLSearchParams(window.location.search);
    const memberId = urlParams.get('id');
    
    if (!memberId) {
        alert('申請IDが指定されていません');
        window.location.href = '/registration-list';
        return;
    }
    
    try {
        // バックエンドAPIから詳細データを取得
        const response = await fetch(`http://localhost:8020/api/admin/members/${memberId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
            }
        });
        
        if (response.ok) {
            const member = await response.json();
            renderRegistrationApprove(member);
            
            // 戻るボタンにクリックイベントを追加
            setupBackButton(memberId);
        } else {
            alert('申請詳細の取得に失敗しました');
            window.location.href = '/registration-list';
        }
    } catch (error) {
        console.error('申請詳細取得エラー:', error);
        alert('申請詳細の取得に失敗しました');
        window.location.href = '/registration-list';
    }
}

// 戻るボタンの設定
function setupBackButton(memberId) {
    const backButton = document.querySelector('a[href*="registration-detail"]');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            // 申請詳細ページに戻る（member_idをパラメータとして渡す）
            window.location.href = `/registration-detail?id=${memberId}`;
        });
    }
}

// 承認確認画面のレンダリング
function renderRegistrationApprove(member) {
    console.log('承認確認画面のレンダリング開始...');
    
    // 基本情報
    const elements = {
        'applicant-name': document.getElementById('applicant-name'),
        'applicant-name-kana': document.getElementById('applicant-name-kana'),
        'applicant-name-alphabet': document.getElementById('applicant-name-alphabet'),
        'applicant-address': document.getElementById('applicant-address'),
        'applicant-address-type': document.getElementById('applicant-address-type'),
        'applicant-mobile': document.getElementById('applicant-mobile'),
        'applicant-phone': document.getElementById('applicant-phone'),
        'applicant-birth': document.getElementById('applicant-birth'),
        'applicant-email': document.getElementById('applicant-email'),
        'applicant-occupation': document.getElementById('applicant-occupation'),
        'applicant-introduction': document.getElementById('applicant-introduction'),
        'applicant-dealer': document.getElementById('applicant-dealer'),
        'vehicle-model': document.getElementById('vehicle-model'),
        'vehicle-year': document.getElementById('vehicle-year'),
        'vehicle-color': document.getElementById('vehicle-color'),
        'vehicle-registration': document.getElementById('vehicle-registration'),
        'referrer-1-name': document.getElementById('referrer-1-name'),
        'referrer-1-dealer': document.getElementById('referrer-1-dealer'),
        'referrer-2-name': document.getElementById('referrer-2-name')
    };
    
    // 要素の存在確認
    for (const [id, element] of Object.entries(elements)) {
        if (!element) {
            console.error(`Element not found: ${id}`);
        } else {
            console.log(`Element found: ${id}`);
        }
    }
    
    // 基本情報
    if (elements['applicant-name']) elements['applicant-name'].textContent = `${member.family_name} ${member.first_name}`;
    if (elements['applicant-name-kana']) elements['applicant-name-kana'].textContent = `${member.family_name_kana} ${member.first_name_kana}`;
    if (elements['applicant-name-alphabet']) elements['applicant-name-alphabet'].textContent = member.name_alphabet;
    if (elements['applicant-address']) elements['applicant-address'].innerHTML = `〒${member.postal_code}<br>${member.prefecture}${member.city_address}${member.building_name || ''}`;
    if (elements['applicant-address-type']) elements['applicant-address-type'].textContent = member.address_type;
    if (elements['applicant-mobile']) elements['applicant-mobile'].textContent = member.mobile_number;
    if (elements['applicant-phone']) elements['applicant-phone'].textContent = member.phone_number || '-';
    if (elements['applicant-birth']) elements['applicant-birth'].textContent = formatBirthDate(member.birth_date);
    if (elements['applicant-email']) elements['applicant-email'].textContent = member.email;
    if (elements['applicant-occupation']) elements['applicant-occupation'].innerHTML = member.occupation.replace(/\n/g, '<br>');
    if (elements['applicant-introduction']) elements['applicant-introduction'].innerHTML = member.self_introduction.replace(/\n/g, '<br>');
    if (elements['applicant-dealer']) elements['applicant-dealer'].textContent = member.relationship_dealer || '-';
    if (elements['applicant-sales-person']) elements['applicant-sales-person'].textContent = member.sales_person || '-';
    
    // 車両情報
    if (member.vehicles && member.vehicles.length > 0) {
        const vehicle = member.vehicles[0];
        if (elements['vehicle-model']) elements['vehicle-model'].textContent = vehicle.model_name;
        if (elements['vehicle-year']) elements['vehicle-year'].textContent = vehicle.year;
        if (elements['vehicle-color']) elements['vehicle-color'].textContent = vehicle.color;
        if (elements['vehicle-registration']) elements['vehicle-registration'].textContent = vehicle.registration_number;
    } else {
        if (elements['vehicle-model']) elements['vehicle-model'].textContent = '-';
        if (elements['vehicle-year']) elements['vehicle-year'].textContent = '-';
        if (elements['vehicle-color']) elements['vehicle-color'].textContent = '-';
        if (elements['vehicle-registration']) elements['vehicle-registration'].textContent = '-';
    }
    
    // 紹介者情報
    if (member.referrers && member.referrers.length > 0) {
        const referrer1 = member.referrers.find(r => r.referrer_order === 1);
        if (referrer1) {
            if (elements['referrer-1-name']) elements['referrer-1-name'].textContent = referrer1.referrer_name;
            if (elements['referrer-1-dealer']) elements['referrer-1-dealer'].textContent = referrer1.referrer_dealer || '-';
        }
        
        const referrer2 = member.referrers.find(r => r.referrer_order === 2);
        if (referrer2) {
            if (elements['referrer-2-name']) elements['referrer-2-name'].textContent = referrer2.referrer_name;
        }
    } else {
        if (elements['referrer-1-name']) elements['referrer-1-name'].textContent = '-';
        if (elements['referrer-1-dealer']) elements['referrer-1-dealer'].textContent = '-';
        if (elements['referrer-2-name']) elements['referrer-2-name'].textContent = '-';
    }
    
    console.log('承認確認画面のレンダリング完了');
}

// 却下確認画面の初期化
async function initRegistrationReject() {
    const urlParams = new URLSearchParams(window.location.search);
    const memberId = urlParams.get('id');
    
    if (!memberId) {
        alert('申請IDが指定されていません');
        window.location.href = '/registration-list';
        return;
    }
    
    try {
        // バックエンドAPIから詳細データを取得
        const response = await fetch(`http://localhost:8020/api/admin/members/${memberId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
            }
        });
        
        if (response.ok) {
            const member = await response.json();
            renderRegistrationReject(member);
            
            // 戻るボタンにクリックイベントを追加
            setupRejectBackButton(memberId);
        } else {
            alert('申請詳細の取得に失敗しました');
            window.location.href = '/registration-list';
        }
    } catch (error) {
        console.error('申請詳細取得エラー:', error);
        alert('申請詳細の取得に失敗しました');
        window.location.href = '/registration-list';
    }
}

// 却下確認画面の戻るボタンの設定
function setupRejectBackButton(memberId) {
    const backButton = document.querySelector('a[href*="registration-detail"]');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            // 申請詳細ページに戻る（member_idをパラメータとして渡す）
            window.location.href = `/registration-detail?id=${memberId}`;
        });
    }
}

// 却下確認画面のレンダリング
function renderRegistrationReject(member) {
    console.log('却下確認画面のレンダリング開始...');
    
    // 基本情報
    const elements = {
        'applicant-name': document.getElementById('applicant-name'),
        'applicant-name-kana': document.getElementById('applicant-name-kana'),
        'applicant-name-alphabet': document.getElementById('applicant-name-alphabet'),
        'applicant-address': document.getElementById('applicant-address'),
        'applicant-address-type': document.getElementById('applicant-address-type'),
        'applicant-mobile': document.getElementById('applicant-mobile'),
        'applicant-phone': document.getElementById('applicant-phone'),
        'applicant-birth': document.getElementById('applicant-birth'),
        'applicant-email': document.getElementById('applicant-email'),
        'applicant-occupation': document.getElementById('applicant-occupation'),
        'applicant-introduction': document.getElementById('applicant-introduction'),
        'applicant-dealer': document.getElementById('applicant-dealer'),
        'vehicle-model': document.getElementById('vehicle-model'),
        'vehicle-year': document.getElementById('vehicle-year'),
        'vehicle-color': document.getElementById('vehicle-color'),
        'vehicle-registration': document.getElementById('vehicle-registration'),
        'referrer-1-name': document.getElementById('referrer-1-name'),
        'referrer-1-dealer': document.getElementById('referrer-1-dealer'),
        'referrer-2-name': document.getElementById('referrer-2-name')
    };
    
    // 要素の存在確認
    for (const [id, element] of Object.entries(elements)) {
        if (!element) {
            console.error(`Element not found: ${id}`);
        } else {
            console.log(`Element found: ${id}`);
        }
    }
    
    // 基本情報
    if (elements['applicant-name']) elements['applicant-name'].textContent = `${member.family_name} ${member.first_name}`;
    if (elements['applicant-name-kana']) elements['applicant-name-kana'].textContent = `${member.family_name_kana} ${member.first_name_kana}`;
    if (elements['applicant-name-alphabet']) elements['applicant-name-alphabet'].textContent = member.name_alphabet;
    if (elements['applicant-address']) elements['applicant-address'].innerHTML = `〒${member.postal_code}<br>${member.prefecture}${member.city_address}${member.building_name || ''}`;
    if (elements['applicant-address-type']) elements['applicant-address-type'].textContent = member.address_type;
    if (elements['applicant-mobile']) elements['applicant-mobile'].textContent = member.mobile_number;
    if (elements['applicant-phone']) elements['applicant-phone'].textContent = member.phone_number || '-';
    if (elements['applicant-birth']) elements['applicant-birth'].textContent = formatBirthDate(member.birth_date);
    if (elements['applicant-email']) elements['applicant-email'].textContent = member.email;
    if (elements['applicant-occupation']) elements['applicant-occupation'].innerHTML = member.occupation.replace(/\n/g, '<br>');
    if (elements['applicant-introduction']) elements['applicant-introduction'].innerHTML = member.self_introduction.replace(/\n/g, '<br>');
    if (elements['applicant-dealer']) elements['applicant-dealer'].textContent = member.relationship_dealer || '-';
    if (elements['applicant-sales-person']) elements['applicant-sales-person'].textContent = member.sales_person || '-';
    
    // 車両情報
    if (member.vehicles && member.vehicles.length > 0) {
        const vehicle = member.vehicles[0];
        if (elements['vehicle-model']) elements['vehicle-model'].textContent = vehicle.model_name;
        if (elements['vehicle-year']) elements['vehicle-year'].textContent = vehicle.year;
        if (elements['vehicle-color']) elements['vehicle-color'].textContent = vehicle.color;
        if (elements['vehicle-registration']) elements['vehicle-registration'].textContent = vehicle.registration_number;
    } else {
        if (elements['vehicle-model']) elements['vehicle-model'].textContent = '-';
        if (elements['vehicle-year']) elements['vehicle-year'].textContent = '-';
        if (elements['vehicle-color']) elements['vehicle-color'].textContent = '-';
        if (elements['vehicle-registration']) elements['vehicle-registration'].textContent = '-';
    }
    
    // 紹介者情報
    if (member.referrers && member.referrers.length > 0) {
        const referrer1 = member.referrers.find(r => r.referrer_order === 1);
        if (referrer1) {
            if (elements['referrer-1-name']) elements['referrer-1-name'].textContent = referrer1.referrer_name;
            if (elements['referrer-1-dealer']) elements['referrer-1-dealer'].textContent = referrer1.referrer_dealer || '-';
        }
        
        const referrer2 = member.referrers.find(r => r.referrer_order === 2);
        if (referrer2) {
            if (elements['referrer-2-name']) elements['referrer-2-name'].textContent = referrer2.referrer_name;
        }
    } else {
        if (elements['referrer-1-name']) elements['referrer-1-name'].textContent = '-';
        if (elements['referrer-1-dealer']) elements['referrer-1-dealer'].textContent = '-';
        if (elements['referrer-2-name']) elements['referrer-2-name'].textContent = '-';
    }
    
    console.log('却下確認画面のレンダリング完了');
} 