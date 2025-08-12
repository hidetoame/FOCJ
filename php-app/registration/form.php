<?php
/**
 * 登録フォーム - 入力画面
 */
require_once '../config/config.php';

// CSRFトークン生成
$csrf_token = generateCsrfToken();

// セッションからデータ復元（確認画面から戻った場合）
$formData = getFormData();

// エラーメッセージ取得
$error = getError();

// テンプレート読み込み
$html = file_get_contents(TEMPLATE_PATH . '/registration-form/registration-form.html');

// フォームのaction属性を変更
$html = str_replace('action="registration-form-confirm.html"', 'action="confirm.php"', $html);

// アセットパスを調整
$html = str_replace('href="assets/', 'href="' . REGISTRATION_TEMPLATE_WEB_PATH . '/assets/', $html);
$html = str_replace('src="assets/', 'src="' . REGISTRATION_TEMPLATE_WEB_PATH . '/assets/', $html);

// CSRFトークンを追加
$csrfField = '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
$html = str_replace('<div id="form-invalid-message"></div>', '<div id="form-invalid-message"></div>' . $csrfField, $html);

// name属性が欠けているフィールドに追加
$html = str_replace('id="car-model" required>', 'id="car-model" name="car-model" required>', $html);

// サンプルデータボタンを追加
$sampleButton = '
<div style="background: #f0f0f0; padding: 15px; margin-bottom: 20px; border: 2px dashed #999;">
    <button type="button" id="sample-data-btn" style="background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px;">
        🧪 サンプルデータ挿入（テスト用）
    </button>
</div>
';
$html = str_replace('<h3>申込者の情報</h3>', $sampleButton . '<h3>申込者の情報</h3>', $html);

// セッションデータがある場合は値を復元
if ($formData) {
    foreach ($formData as $key => $value) {
        if (is_string($value)) {
            // input text
            $html = preg_replace(
                '/(<input[^>]*name="' . preg_quote($key, '/') . '"[^>]*value=")[^"]*(")/i',
                '$1' . h($value) . '$2',
                $html
            );
            // textarea
            $html = preg_replace(
                '/(<textarea[^>]*name="' . preg_quote($key, '/') . '"[^>]*>)(.*?)(<\/textarea>)/is',
                '$1' . h($value) . '$3',
                $html
            );
            // select
            $html = preg_replace(
                '/(<option value="' . preg_quote($value, '/') . '")([^>]*)>/i',
                '$1 selected$2>',
                $html
            );
            // radio
            if (strpos($html, 'name="' . $key . '" value="' . $value . '"') !== false) {
                $html = preg_replace(
                    '/(<input[^>]*type="radio"[^>]*name="' . preg_quote($key, '/') . '"[^>]*value="' . preg_quote($value, '/') . '"[^>]*)>/i',
                    '$1 checked>',
                    $html
                );
            }
        }
    }
}

// サンプルデータ用JavaScript追加
$sampleDataScript = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("sample-data-btn").addEventListener("click", function() {
        // 申込者の基本情報
        if (document.getElementById("familyname")) document.getElementById("familyname").value = "山田";
        if (document.getElementById("firstname")) document.getElementById("firstname").value = "太郎";
        if (document.getElementById("familyname-kana")) document.getElementById("familyname-kana").value = "ヤマダ";
        if (document.getElementById("firstname-kana")) document.getElementById("firstname-kana").value = "タロウ";
        if (document.getElementById("name-alphabet")) document.getElementById("name-alphabet").value = "YAMADA TARO";
        
        // 住所情報
        if (document.getElementById("postal-code")) document.getElementById("postal-code").value = "106-0032";
        if (document.getElementById("prefecture")) document.getElementById("prefecture").value = "東京都";
        if (document.getElementById("city-address")) document.getElementById("city-address").value = "港区六本木1-2-3";
        if (document.getElementById("building-name")) document.getElementById("building-name").value = "フェラーリビル5F";
        
        // 住所タイプ（ラジオボタン）
        var addressHome = document.getElementById("address-home");
        if (addressHome) addressHome.checked = true;
        
        // 連絡先
        if (document.getElementById("mobile-number")) document.getElementById("mobile-number").value = "090-1234-5678";
        if (document.getElementById("phone-number")) document.getElementById("phone-number").value = "03-1234-5678";
        
        // 生年月日
        if (document.getElementById("birth-year")) document.getElementById("birth-year").value = "1980";
        if (document.getElementById("birth-month")) document.getElementById("birth-month").value = "1";
        if (document.getElementById("birth-day")) document.getElementById("birth-day").value = "15";
        
        // メールアドレス
        if (document.getElementById("mail-address")) document.getElementById("mail-address").value = "yamada@example.com";
        
        // 職業情報
        if (document.getElementById("occupation")) document.getElementById("occupation").value = "株式会社サンプル商事\\n代表取締役社長";
        if (document.getElementById("self-introduction")) document.getElementById("self-introduction").value = "フェラーリを愛する経営者です。\\n2015年から488GTBに乗っています。\\nサーキット走行会にも積極的に参加したいと考えています。";
        
        // ディーラー情報（セレクトボックス）
        if (document.getElementById("relationship-dealer")) document.getElementById("relationship-dealer").value = "コーンズ芝ショールーム";
        if (document.getElementById("sales-person")) document.getElementById("sales-person").value = "田中 一郎";
        
        // 車両情報  
        if (document.getElementById("car-model")) document.getElementById("car-model").value = "488 GTB";
        if (document.getElementById("car-year")) document.getElementById("car-year").value = "2020";
        if (document.getElementById("car-color")) document.getElementById("car-color").value = "ロッソコルサ";
        if (document.getElementById("car-number")) document.getElementById("car-number").value = "品川330 は 12-34";
        
        // 紹介者情報
        if (document.getElementById("referrer1")) document.getElementById("referrer1").value = "佐藤 次郎";
        if (document.getElementById("referrer-dealer")) document.getElementById("referrer-dealer").value = "コーンズ芝ショールーム";
        if (document.getElementById("referrer2")) document.getElementById("referrer2").value = "鈴木 三郎";
        
        // プライバシーポリシー同意
        var privacyCheckbox = document.getElementById("privacy-agreement");
        if (privacyCheckbox) privacyCheckbox.checked = true;
        
        alert("サンプルデータを入力しました。\\n\\n※ 以下は手動で設定してください：\\n・運転免許証の画像\\n・車検証の画像\\n・名刺の画像（任意）");
    });
});
</script>
';
$html = str_replace('</body>', $sampleDataScript . '</body>', $html);

// バリデーションルールを上書きするスクリプトと画像プレビュー機能を追加
$validationOverride = '
<style>
.image-preview {
    margin-top: 10px;
    max-width: 100%;
}
.image-preview img {
    max-width: 300px;
    max-height: 200px;
    border: 1px solid #ddd;
    padding: 5px;
    border-radius: 4px;
    display: block;
    margin-top: 5px;
}
.image-preview .file-name {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
.image-preview .file-size {
    font-size: 11px;
    color: #999;
}
.image-preview .remove-image {
    color: #dc3545;
    cursor: pointer;
    font-size: 12px;
    margin-left: 10px;
}
</style>
<script>
$(function() {
    // ページ読み込み後、バリデーションルールを調整
    setTimeout(function() {
        var validator = $("#registration-form").data("validator");
        if (validator) {
            // ファイルアップロードを任意にする（デバッグ用）
            delete validator.settings.rules["drivers-license"];
            delete validator.settings.rules["vehicle-inspection"];
            delete validator.settings.rules["business-card"];
            delete validator.settings.messages["drivers-license"];
            delete validator.settings.messages["vehicle-inspection"];
            delete validator.settings.messages["business-card"];
            console.log("バリデーションルールを調整しました");
        }
    }, 100);
    
    // 画像プレビュー機能
    function setupImagePreview(inputId, labelText) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        // プレビューコンテナを作成
        const previewContainer = document.createElement("div");
        previewContainer.className = "image-preview";
        previewContainer.id = inputId + "-preview";
        input.parentNode.insertBefore(previewContainer, input.nextSibling);
        
        input.addEventListener("change", function() {
            const file = this.files[0];
            previewContainer.innerHTML = "";
            
            if (file) {
                // ファイルサイズチェック（100MB）
                if (file.size > 100 * 1024 * 1024) {
                    previewContainer.innerHTML = \'<div style="color: red;">ファイルサイズが100MBを超えています</div>\';
                    this.value = "";
                    return;
                }
                
                // 画像ファイルチェック
                if (!file.type.startsWith("image/")) {
                    previewContainer.innerHTML = \'<div style="color: red;">画像ファイルを選択してください</div>\';
                    this.value = "";
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = `
                        <img src="${e.target.result}" alt="${labelText}のプレビュー">
                        <div class="file-name">ファイル名: ${file.name}</div>
                        <div class="file-size">サイズ: ${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                    `;
                    previewContainer.innerHTML = preview;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // 各画像入力フィールドにプレビュー機能を設定
    setupImagePreview("drivers-license", "運転免許証");
    setupImagePreview("vehicle-inspection", "車検証");
    setupImagePreview("business-card", "名刺");
});
</script>
';
$html = str_replace('</body>', $validationOverride . '</body>', $html);

echo $html;