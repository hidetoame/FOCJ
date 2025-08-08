$(function () {
  // セッションストレージからフォームデータを取得
  const formData = JSON.parse(sessionStorage.getItem('formData') || '{}');
  
  // データがない場合は入力画面に戻る
  if (Object.keys(formData).length === 0) {
    window.location.href = "/registration-form";
    return;
  }
  
  // フィールドマッピング
  const fieldMapping = {
    'familyname': '姓',
    'firstname': '名',
    'familyname-kana': '姓（カナ）',
    'firstname-kana': '名（カナ）',
    'name-alphabet': 'ローマ字',
    'postal-code': '郵便番号',
    'prefecture': '都道府県',
    'city-address': '市区町村・番地',
    'building-name': '建物名',
    'address-type': '住所種別',
    'mobile-number': '携帯電話',
    'phone-number': '電話番号',
    'birth-year': '生年（年）',
    'birth-month': '生月（月）',
    'birth-day': '生日（日）',
    'mail-address': 'メールアドレス',
    'occupation': '職業',
    'self-introduction': '自己紹介',
    'relationship-dealer': 'ディーラー',
    'sales-person': '担当者',
    'car-model': '車種',
    'car-year': '年式',
    'car-color': '車体色',
    'car-number': '登録No',
    'referrer1': '紹介者1',
    'referrer-dealer': '紹介ディーラー',
    'referrer2': '紹介者2'
  };
  
  // 確認画面を動的に生成
  let confirmHTML = '<hr><h3>申込者の情報</h3>';
  
  // 氏名
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">氏名</div></div>
      <div class="form-item-confirm">${formData['familyname'] || '-'} ${formData['firstname'] || '-'}</div>
    </div>
  `;
  
  // 氏名（フリガナ）
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">氏名（フリガナ）</div></div>
      <div class="form-item-confirm">${formData['familyname-kana'] || '-'} ${formData['firstname-kana'] || '-'}</div>
    </div>
  `;
  
  // ローマ字
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">ローマ字</div></div>
      <div class="form-item-confirm">${formData['name-alphabet'] || '-'}</div>
    </div>
  `;
  
  // 住所
  confirmHTML += '<hr><h3>ご連絡先</h3>';
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">郵便番号</div></div>
      <div class="form-item-confirm">${formData['postal-code'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">住所</div></div>
      <div class="form-item-confirm">
        ${formData['prefecture'] || '-'} ${formData['city-address'] || '-'} ${formData['building-name'] || ''}
      </div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">住所種別</div></div>
      <div class="form-item-confirm">${formData['address-type'] === 'home' ? '自宅' : formData['address-type'] === 'work' ? '勤務先' : '-'}</div>
    </div>
  `;
  
  // 連絡先
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">携帯電話</div></div>
      <div class="form-item-confirm">${formData['mobile-number'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">電話番号</div></div>
      <div class="form-item-confirm">${formData['phone-number'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">生年月日</div></div>
      <div class="form-item-confirm">${formData['birth-year'] || '-'} 年 ${formData['birth-month'] || '-'} 月 ${formData['birth-day'] || '-'} 日</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">メールアドレス</div></div>
      <div class="form-item-confirm">${formData['mail-address'] || '-'}</div>
    </div>
  `;
  
  // その他の情報
  confirmHTML += '<hr><h3>その他の情報</h3>';
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">職業</div></div>
      <div class="form-item-confirm">${formData['occupation'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">自己紹介</div></div>
      <div class="form-item-confirm">${formData['self-introduction'] || '-'}</div>
    </div>
  `;
  
  // Ferrari情報
  confirmHTML += '<hr><h3>Ferrari情報</h3>';
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">お付き合いのあるディーラー</div></div>
      <div class="form-item-confirm">${formData['relationship-dealer'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">担当者名</div></div>
      <div class="form-item-confirm">${formData['sales-person'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">車種・Model名</div></div>
      <div class="form-item-confirm">${formData['car-model'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">年式</div></div>
      <div class="form-item-confirm">${formData['car-year'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">車体色</div></div>
      <div class="form-item-confirm">${formData['car-color'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">登録No</div></div>
      <div class="form-item-confirm">${formData['car-number'] || '-'}</div>
    </div>
  `;
  
  // 紹介者
  confirmHTML += '<hr><h3>ご紹介者</h3>';
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">紹介者1</div></div>
      <div class="form-item-confirm">${formData['referrer1'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">紹介ディーラー</div></div>
      <div class="form-item-confirm">${formData['referrer-dealer'] || '-'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">紹介者2</div></div>
      <div class="form-item-confirm">${formData['referrer2'] || '-'}</div>
    </div>
  `;
  
  // 添付ファイル
  confirmHTML += '<hr><h3>添付書類</h3>';
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">運転免許証</div></div>
      <div class="form-item-confirm">${formData['drivers-license'] ? '添付済み' : '未添付'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">車検証</div></div>
      <div class="form-item-confirm">${formData['vehicle-inspection'] ? '添付済み' : '未添付'}</div>
    </div>
  `;
  
  confirmHTML += `
    <div class="form-group">
      <div class="form-group-name"><div class="icon icon-edit icon--text">名刺</div></div>
      <div class="form-item-confirm">${formData['business-card'] ? '添付済み' : '未添付'}</div>
    </div>
  `;
  
  // ボタンエリア
  confirmHTML += `
    <div class="button-area">
      <a href="/registration-form" class="button button--line icon icon-edit-note">入力内容を変更する</a>
      <button type="submit" class="button button--primary icon icon-send">送信する</button>
    </div>
  `;
  
  // HTMLを挿入
  $("#confirm-form").html(confirmHTML);
  
  // 送信ボタンクリック時の処理
  $("#confirm-form").on("submit", function(e) {
    e.preventDefault();
    
    // フォームデータを作成
    const submitData = new FormData();
    Object.keys(formData).forEach(function(key) {
      if (formData[key]) {
        submitData.append(key, formData[key]);
      }
    });
    
    // APIに送信
    fetch("/api/members/", {
      method: "POST",
      body: submitData
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert(data.error);
      } else {
        // 完了画面へ遷移
        sessionStorage.removeItem('formData');
        window.location.href = "/registration-form-thanks";
      }
    })
    .catch(error => {
      console.error("送信エラー:", error);
      alert("送信に失敗しました。もう一度お試しください。");
    });
  });
});