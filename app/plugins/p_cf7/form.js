document.addEventListener("DOMContentLoaded", () => {
  let val;
  let type;
  let radio;
  let check;

  // ラジオボタンの初期値を取得し、確認画面に反映させる
  const radioButtons = document.querySelectorAll('[type="radio"]:checked');
  radioButtons.forEach((button) => {
    // ラジオボタンの選択値を取得
    radio = button.value;
    // ラジオボタンの親要素からidを取得
    const id = button.closest("[id]").id;
    // 取得したidをクラス名に追加し、確認画面の値を設定
    document.querySelector(`.c-form-confirm_${id}`).textContent = radio;
  });

  // 入力フィールドの内容が変更された場合の処理
  const formInputs = document.querySelectorAll(".c-form__item input, .c-form__item select, .c-form__item textarea");
  formInputs.forEach((input) => {
    input.addEventListener("change", function () {
      // エラー表示をクリア
      clearFieldError(this);

      // 入力内容を取得
      val = this.value;
      // 入力フィールドのタイプを取得
      type = this.getAttribute("type");
      // ラジオボタンの場合の処理
      if (type === "radio") {
        // ラジオボタンの選択値を取得
        radio = this.value;
        // ラジオボタンの親要素からidを取得
        const id = this.closest("[id]").id;
        // 取得したidをクラス名に追加し、確認画面の値を設定
        document.querySelector(`.c-form-confirm_${id}`).textContent = radio;
      } // チェックボックスの場合の処理
      else if (type === "checkbox") {
        // チェックボックスの選択値を取得
        check = this.value;
        // チェックボックスの親要素からidを取得
        const id = this.closest("[id]").id;
        // 取得したidをクラス名に追加し、確認画面の値を設定
        document.querySelector(`.c-form-confirm_${id}`).textContent += check + " / ";
      } // その他の場合の処理
      else {
        // 入力フィールドのidを取得
        const id = this.id;
        // 取得したidをクラス名に追加し、確認画面の値を設定
        document.querySelector(`.c-form-confirm_${id}`).textContent = val;
      }
    });

    // リアルタイムバリデーション（入力中）
    input.addEventListener("input", function () {
      clearFieldError(this);
    });
  });

  // フィールドのエラー表示をクリアする関数
  function clearFieldError(element) {
    // エラーメッセージを削除
    const parentElement = element.parentElement;
    const errorMessage = parentElement.querySelector(".error-message");
    if (errorMessage) {
      errorMessage.remove();
    }

    // 赤い枠線を削除
    element.style.borderColor = "";
    element.style.borderWidth = "";
  }

  // バリデーション関数
  function validateForm() {
    let isValid = true;
    const errors = [];
    let firstErrorElement = null;

    // 既存のエラーメッセージを削除
    document.querySelectorAll(".error-message").forEach((error) => error.remove());

    // 必須項目のバリデーション
    const requiredFields = [
      {
        id: "your-name",
        label: "氏名",
        type: "text",
      },
      {
        id: "your-email",
        label: "メールアドレス",
        type: "email",
      },
      {
        id: "url",
        label: "URL",
        type: "url",
      },
      {
        id: "tel",
        label: "電話番号",
        type: "tel",
      },
      {
        id: "number",
        label: "数値",
        type: "number",
      },
      {
        id: "date",
        label: "日付",
        type: "date",
      },
      {
        id: "your-message",
        label: "テキストエリア",
        type: "your-message",
      },
      {
        id: "select",
        label: "ドロップダウンメニュー",
        type: "select",
      },
      {
        id: "checkbox",
        label: "チェックボックス",
        type: "checkbox",
      },
      {
        id: "radio",
        label: "ラジオボタン",
        type: "radio",
      },
    ];

    requiredFields.forEach((field) => {
      const element = document.getElementById(field.id);
      if (!element) return;

      let value = "";
      let fieldIsValid = true;

      if (field.type === "checkbox") {
        const checkedBoxes = element.querySelectorAll('input[type="checkbox"]:checked');
        fieldIsValid = checkedBoxes.length > 0;
      } else if (field.type === "radio") {
        const checkedRadio = element.querySelector('input[type="radio"]:checked');
        fieldIsValid = checkedRadio !== null;
      } else if (field.type === "select") {
        value = element.value;
        fieldIsValid = value !== "" && value !== "選択してください";
      } else {
        value = element.value.trim();
        fieldIsValid = value !== "";
      }

      if (!fieldIsValid) {
        isValid = false;
        showError(element, `${field.label}は必須項目です。`);
        // 最初のエラー要素を記録
        if (!firstErrorElement) {
          firstErrorElement = element;
        }
      } else {
        // 詳細なバリデーション
        if (field.type === "email" && value) {
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(value)) {
            isValid = false;
            showError(element, "正しいメールアドレスを入力してください。");
            if (!firstErrorElement) {
              firstErrorElement = element;
            }
          }
        }

        if (field.type === "url" && value) {
          const urlRegex = /^https?:\/\/.+\..+/;
          if (!urlRegex.test(value)) {
            isValid = false;
            showError(element, "正しいURLを入力してください。（http://またはhttps://から始まる形式）");
            if (!firstErrorElement) {
              firstErrorElement = element;
            }
          }
        }

        if (field.type === "tel" && value) {
          const telRegex = /^[\d\-\+\(\)\s]+$/;
          if (!telRegex.test(value)) {
            isValid = false;
            showError(element, "正しい電話番号を入力してください。");
            if (!firstErrorElement) {
              firstErrorElement = element;
            }
          }
        }

        if (field.type === "number" && value) {
          if (isNaN(value)) {
            isValid = false;
            showError(element, "数値を入力してください。");
            if (!firstErrorElement) {
              firstErrorElement = element;
            }
          }
        }
      }
    });

    // エラーがある場合は最初のエラー要素にスクロール
    if (!isValid && firstErrorElement) {
      scrollToError(firstErrorElement);
    }

    return isValid;
  }

  // エラーメッセージを表示する関数
  function showError(element, message) {
    const errorDiv = document.createElement("div");
    errorDiv.className = "error-message";
    errorDiv.style.color = "red";
    errorDiv.style.fontSize = "14px";
    errorDiv.style.marginTop = "5px";
    errorDiv.textContent = message;

    // エラーメッセージを要素の後に挿入
    if (element.type === "checkbox" || (element.querySelector && element.querySelector('input[type="checkbox"]'))) {
      // チェックボックスの場合は親要素の後に挿入
      element.parentElement.appendChild(errorDiv);
    } else if (element.querySelector && element.querySelector('input[type="radio"]')) {
      // ラジオボタンの場合は親要素の後に挿入
      element.parentElement.appendChild(errorDiv);
    } else {
      element.parentElement.appendChild(errorDiv);
    }

    // 入力フィールドに赤い枠線を追加（ラジオボタンとチェックボックスは除く）
    if (
      element.type !== "checkbox" &&
      element.type !== "radio" &&
      !element.querySelector('input[type="checkbox"]') &&
      !element.querySelector('input[type="radio"]')
    ) {
      element.style.borderColor = "red";
      element.style.borderWidth = "2px";
    }
  }

  // エラー要素にスクロールする関数
  function scrollToError(element) {
    const elementTop = element.getBoundingClientRect().top + window.scrollY;
    const offset = 100; // ヘッダーなどの高さを考慮したオフセット

    window.scrollTo({
      top: elementTop - offset,
      behavior: "smooth",
    });

    // フォーカスを当てる
    element.focus();
  }

  // 確認ボタンをクリックした場合の処理
  const confirmButton = document.querySelector(".c-form-confirm_button");
  if (confirmButton) {
    confirmButton.addEventListener("click", () => {
      // バリデーションチェック
      if (!validateForm()) {
        // エラーがある場合は確認画面に進まない
        return;
      }

      document.querySelector(".c-form").style.display = "none";
      document.querySelector(".c-form-confirm").style.display = "block";
      // ページの一番上にスクロール
      window.scrollTo(0, 0);
    });
  }

  // 戻るボタンをクリックした場合の処理
  const backButton = document.querySelector(".back_button");
  if (backButton) {
    backButton.addEventListener("click", () => {
      document.querySelector(".c-form").style.display = "block";
      document.querySelector(".c-form-confirm").style.display = "none";
      // ページの一番上にスクロール
      window.scrollTo(0, 0);
    });
  }

  // 送信ボタンをクリックした場合の処理
  document.addEventListener(
    "wpcf7mailsent",
    (event) => {
      location.href = "http://localhost:10023/thanks/";
    },
    false,
  );
});
