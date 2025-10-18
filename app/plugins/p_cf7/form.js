document.addEventListener("DOMContentLoaded", () => {
  let val;
  let type;
  let radio;
  let check;

  // 初期値を確認画面に反映させる関数
  function updateConfirmScreen() {
    // ラジオボタンの初期値を取得
    const radioButtons = document.querySelectorAll('[type="radio"]:checked');
    radioButtons.forEach((button) => {
      radio = button.value;
      const id = button.closest("[id]").id;
      const confirmElement = document.querySelector(`.c-form-confirm_${id}`);
      if (confirmElement) {
        confirmElement.textContent = radio;
      }
    });

    // selectboxの初期値を取得
    const selectBoxes = document.querySelectorAll(".c-form__item select");
    selectBoxes.forEach((select) => {
      const id = select.id;
      const value = select.value;
      const confirmElement = document.querySelector(`.c-form-confirm_${id}`);
      if (confirmElement && value) {
        confirmElement.textContent = value;
      }
    });

    // textareaの初期値を取得
    const textareas = document.querySelectorAll(".c-form__item textarea");
    textareas.forEach((textarea) => {
      const id = textarea.id;
      const value = textarea.value;
      const confirmElement = document.querySelector(`.c-form-confirm_${id}`);
      if (confirmElement && value) {
        confirmElement.textContent = value;
      }
    });

    // input(text, email, tel, url, date, number等)の初期値を取得
    const inputs = document.querySelectorAll(".c-form__item input:not([type='radio']):not([type='checkbox'])");
    inputs.forEach((input) => {
      const id = input.id;
      const value = input.value;
      const confirmElement = document.querySelector(`.c-form-confirm_${id}`);
      if (confirmElement && value) {
        confirmElement.textContent = value;
      }
    });

    // チェックボックスの初期値を取得
    const checkboxGroups = document.querySelectorAll('[id] input[type="checkbox"]:checked');
    const processedGroups = new Set();
    checkboxGroups.forEach((checkbox) => {
      const groupId = checkbox.closest("[id]").id;
      if (!processedGroups.has(groupId)) {
        processedGroups.add(groupId);
        const allChecked = document.querySelectorAll(`#${groupId} input[type="checkbox"]:checked`);
        const values = Array.from(allChecked).map(cb => cb.value).join(' / ');
        const confirmElement = document.querySelector(`.c-form-confirm_${groupId}`);
        if (confirmElement) {
          confirmElement.textContent = values;
        }
      }
    });
  }

  // ページ読み込み時に初期値を反映
  updateConfirmScreen();

  // 入力フィールドの内容が変更された場合の処理
  const formInputs = document.querySelectorAll(".c-form__item input, .c-form__item select, .c-form__item textarea");
  formInputs.forEach((input) => {
    input.addEventListener("change", function () {
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
        const confirmElement = document.querySelector(`.c-form-confirm_${id}`);
        if (confirmElement) {
          confirmElement.textContent = radio;
        }
      } 
      // チェックボックスの場合の処理
      else if (type === "checkbox") {
        // チェックボックスの親要素からidを取得
        const id = this.closest("[id]").id;
        // 同じグループの全てのチェックされたチェックボックスを取得
        const allChecked = document.querySelectorAll(`#${id} input[type="checkbox"]:checked`);
        const values = Array.from(allChecked).map(cb => cb.value).join(' / ');
        // 取得したidをクラス名に追加し、確認画面の値を設定
        const confirmElement = document.querySelector(`.c-form-confirm_${id}`);
        if (confirmElement) {
          confirmElement.textContent = values;
        }
      } 
      // selectboxの場合の処理
      else if (this.tagName.toLowerCase() === 'select') {
        const id = this.id;
        const confirmElement = document.querySelector(`.c-form-confirm_${id}`);
        if (confirmElement) {
          confirmElement.textContent = val;
        }
      }
      // その他の場合の処理（input[type="text"], textarea等）
      else {
        // 入力フィールドのidを取得
        const id = this.id;
        // 取得したidをクラス名に追加し、確認画面の値を設定
        const confirmElement = document.querySelector(`.c-form-confirm_${id}`);
        if (confirmElement) {
          confirmElement.textContent = val;
        }
      }
    });
  });

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
    // ボタンのtype属性を確認し、submitの場合はbuttonに変更
    if (confirmButton.getAttribute('type') === 'submit') {
      confirmButton.setAttribute('type', 'button');
    }

    confirmButton.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      // 確認画面に最新の入力値を反映
      updateConfirmScreen();
      
      // Contact Form 7のフォームを取得
      const form = document.querySelector('.wpcf7-form');
      if (!form) return;

      // Contact Form 7のフォーム要素を取得
      const formElement = form.closest('.wpcf7');
      if (!formElement) return;

      // Contact Form 7の内部バリデーション用にFormDataを作成
      const formData = new FormData(form);
      
      // wpcf7オブジェクトが存在するか確認
      if (typeof wpcf7 === 'undefined') {
        console.error('Contact Form 7が読み込まれていません');
        return;
      }

      // 全ての必須フィールドをチェック
      let hasErrors = false;
      const requiredFields = form.querySelectorAll('[aria-required="true"], .wpcf7-validates-as-required');
      
      requiredFields.forEach(field => {
        // 既存のエラー表示をクリア
        const existingError = field.parentElement.querySelector('.wpcf7-not-valid-tip');
        if (existingError) {
          existingError.remove();
        }
        field.classList.remove('wpcf7-not-valid');
        field.setAttribute('aria-invalid', 'false');
      });

      // Contact Form 7のバリデーションを実行
      requiredFields.forEach(field => {
        const fieldType = field.type;
        let isEmpty = false;

        if (fieldType === 'checkbox' || fieldType === 'radio') {
          const name = field.name;
          const checkedItems = form.querySelectorAll(`[name="${name}"]:checked`);
          isEmpty = checkedItems.length === 0;
        } else if (fieldType === 'select-one' || fieldType === 'select-multiple') {
          isEmpty = !field.value || field.value === '';
        } else {
          isEmpty = !field.value || field.value.trim() === '';
        }

        if (isEmpty) {
          hasErrors = true;
          field.classList.add('wpcf7-not-valid');
          field.setAttribute('aria-invalid', 'true');
          
          // エラーメッセージを追加
          const errorMessage = document.createElement('span');
          errorMessage.className = 'wpcf7-not-valid-tip';
          errorMessage.setAttribute('aria-hidden', 'true');
          errorMessage.textContent = 'このフィールドは必須です。';
          
          // エラーメッセージを適切な位置に挿入
          if (fieldType === 'checkbox' || fieldType === 'radio') {
            const wrapper = field.closest('.wpcf7-form-control-wrap');
            if (wrapper) {
              wrapper.appendChild(errorMessage);
            }
          } else {
            field.parentElement.appendChild(errorMessage);
          }
        }
      });

      // メールアドレスのバリデーション
      const emailFields = form.querySelectorAll('input[type="email"]');
      emailFields.forEach(field => {
        const value = field.value ? field.value.trim() : '';
        if (value) {
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(value)) {
            hasErrors = true;
            field.classList.add('wpcf7-not-valid');
            field.setAttribute('aria-invalid', 'true');
            
            const errorMessage = document.createElement('span');
            errorMessage.className = 'wpcf7-not-valid-tip';
            errorMessage.setAttribute('aria-hidden', 'true');
            errorMessage.textContent = 'メールアドレスが正しくありません。';
            field.parentElement.appendChild(errorMessage);
          }
        }
      });

      // URLのバリデーション
      const urlFields = form.querySelectorAll('input[type="url"]');
      urlFields.forEach(field => {
        const value = field.value ? field.value.trim() : '';
        if (value) {
          const urlRegex = /^https?:\/\/.+/;
          if (!urlRegex.test(value)) {
            hasErrors = true;
            field.classList.add('wpcf7-not-valid');
            field.setAttribute('aria-invalid', 'true');
            
            const errorMessage = document.createElement('span');
            errorMessage.className = 'wpcf7-not-valid-tip';
            errorMessage.setAttribute('aria-hidden', 'true');
            errorMessage.textContent = 'URLが正しくありません。';
            field.parentElement.appendChild(errorMessage);
          }
        }
      });

      // 電話番号のバリデーション
      const telFields = form.querySelectorAll('input[type="tel"]');
      telFields.forEach(field => {
        const value = field.value ? field.value.trim() : '';
        if (value) {
          const telRegex = /^[0-9\-\+\(\)\s]+$/;
          if (!telRegex.test(value)) {
            hasErrors = true;
            field.classList.add('wpcf7-not-valid');
            field.setAttribute('aria-invalid', 'true');
            
            const errorMessage = document.createElement('span');
            errorMessage.className = 'wpcf7-not-valid-tip';
            errorMessage.setAttribute('aria-hidden', 'true');
            errorMessage.textContent = '電話番号が正しくありません。';
            field.parentElement.appendChild(errorMessage);
          }
        }
      });

      if (hasErrors) {
        // エラーがある場合は最初のエラーにスクロール
        const firstError = form.querySelector('.wpcf7-not-valid');
        if (firstError) {
          scrollToError(firstError);
        }
        
        // エラーメッセージを表示
        const responseOutput = formElement.querySelector('.wpcf7-response-output');
        if (responseOutput) {
          responseOutput.innerHTML = '入力内容に誤りがあります。ご確認ください。';
          responseOutput.classList.add('wpcf7-validation-errors');
          responseOutput.classList.remove('wpcf7-display-none');
          responseOutput.setAttribute('role', 'alert');
        }
      } else {
        // エラーがない場合は確認画面を表示
        const responseOutput = formElement.querySelector('.wpcf7-response-output');
        if (responseOutput) {
          responseOutput.classList.add('wpcf7-display-none');
          responseOutput.classList.remove('wpcf7-validation-errors');
        }
        
        document.querySelector(".c-form").style.display = "none";
        document.querySelector(".c-form-confirm").style.display = "block";
        // ページの一番上にスクロール
        window.scrollTo(0, 0);
      }
    });
  }

  // 戻るボタンをクリックした場合の処理
  const backButton = document.querySelector(".back_button");
  if (backButton) {
    backButton.addEventListener("click", () => {
      // エラーメッセージをクリア
      const form = document.querySelector('.wpcf7-form');
      if (form) {
        form.querySelectorAll('.wpcf7-not-valid-tip').forEach(error => error.remove());
        form.querySelectorAll('.wpcf7-not-valid').forEach(field => {
          field.classList.remove('wpcf7-not-valid');
          field.setAttribute('aria-invalid', 'false');
        });
        
        const formElement = form.closest('.wpcf7');
        if (formElement) {
          const responseOutput = formElement.querySelector('.wpcf7-response-output');
          if (responseOutput) {
            responseOutput.classList.add('wpcf7-display-none');
            responseOutput.classList.remove('wpcf7-validation-errors');
          }
        }
      }
      
      document.querySelector(".c-form").style.display = "block";
      document.querySelector(".c-form-confirm").style.display = "none";
      // ページの一番上にスクロール
      window.scrollTo(0, 0);
    });
  }

  // 送信ボタンをクリックした場合の処理（確認画面から送信する場合のみ）
  document.addEventListener(
    "wpcf7mailsent",
    (event) => {
      // 確認画面が表示されている場合のみサンクスページに遷移
      const confirmSection = document.querySelector(".c-form-confirm");
      if (confirmSection && confirmSection.style.display !== "none") {
        location.href = "http://localhost:10023/thanks/";
      }
    },
    false,
  );
});
