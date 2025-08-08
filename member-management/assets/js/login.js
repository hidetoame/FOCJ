// FOCJ管理画面ログイン機能
const ADMIN_API_BASE = "http://localhost:8020/api/admin";

// ログインフォームの処理
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    const usernameInput = document.querySelector('input[type="text"]');
    const passwordInput = document.querySelector('input[type="password"]');
    
                    if (loginForm) {
                    // 入力時にエラーメッセージを非表示
                    usernameInput.addEventListener('input', hideError);
                    passwordInput.addEventListener('input', hideError);
                    
                    loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();
            
                                    if (!username || !password) {
                            showError('ユーザー名またはパスワードが違います');
                            return;
                        }
            
            try {
                const response = await fetch(`${ADMIN_API_BASE}/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    // ログイン成功：トークンを保存
                    localStorage.setItem('admin_token', data.access_token);
                    localStorage.setItem('admin_username', username);
                    
                    // 管理画面トップにリダイレクト
                    window.location.href = '/admin-index';
                } else {
                    // ログイン失敗：エラーページにリダイレクト
                    window.location.href = '/login-error';
                }
                                    } catch (error) {
                            console.error('ログインエラー:', error);
                            showError('ログインに失敗しました。もう一度お試しください。');
                        }
        });
    }
});

// ログイン状態チェック
function checkLoginStatus() {
    const token = localStorage.getItem('admin_token');
    const username = localStorage.getItem('admin_username');
    
    if (!token || !username) {
        // ログインしていない場合はログインページにリダイレクト
        if (window.location.pathname !== '/') {
            window.location.href = '/';
        }
        return false;
    }
    
    return true;
}

// ログアウト機能
            function logout() {
                localStorage.removeItem('admin_token');
                localStorage.removeItem('admin_username');
                window.location.href = '/';
            }

            // エラーメッセージ表示関数
            function showError(message) {
                const errorElement = document.getElementById('error-message');
                if (errorElement) {
                    errorElement.textContent = message;
                    errorElement.style.display = 'block';
                }
            }

            // エラーメッセージ非表示関数
            function hideError() {
                const errorElement = document.getElementById('error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }

// ページ読み込み時にログイン状態をチェック
if (window.location.pathname !== '/' && window.location.pathname !== '/login-error') {
    checkLoginStatus();
} 