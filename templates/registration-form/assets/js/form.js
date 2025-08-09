$.extend($.validator.messages, {
  required: "入力必須項目です。",
});

$(function () {
  const $form = $("#registration-form");

  const validator = $form.validate({
    errorClass: "--error",
    onfocusout: function (element) {
      const $pref = $("#prefecture");
      const $city = $("#city-address");
      if ($(element).attr("id") === "postal-code") {
        $pref.valid();
        $city.valid();
      }
      $(element).valid();
    },
    invalidHandler: function (event, validator) {
      const errors = validator.numberOfInvalids();
      const $errorMsg = $("#form-invalid-message");
      if (errors) {
        const message = "入力内容に不備があります。表示内容をご確認の上、正しい情報をご入力ください。";
        $errorMsg.html(message);
        $errorMsg.show();
      } else {
        $errorMsg.hide();
      }
    },
    groups: {
      addressType: "address-home address-work",
    },
    rules: {
      "mail-address": {
        email: true,
      },
      "address-type": {
        required: true,
      },
    },
    messages: {
      prefecture: {
        required: "都道府県を選択してください。",
      },
      "address-type": {
        required: "住所の種類を選択してください。",
      },
      "privacy-agreement": {
        required: "ご登録には個人情報のお取り扱いについての同意が必要です。",
      },
      "drivers-license": {
        required: "画像を選択してください。",
      },
      "vehicle-inspection": {
        required: "画像を選択してください。",
      },
      "business-card": {
        required: "画像を選択してください。",
      },
    },
    errorPlacement: function (error, element) {
      const $label = $(element).closest(".form-item-label");
      let $error;

      if (element.attr("id") === "address-home" || element.attr("id") === "address-work") {
        $error = $("#form-item-error-address-type");
      } else {
        $error = $label.find(".form-item-error");
      }

      $error.empty();
      $error.append(error);
    },
  });
});
