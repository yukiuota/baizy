// Contact Form 7 の確認画面切り替え
// 入力値の確認画面への表示は CF7 の反映機能（[reflection] タグ）に任せ、ここでは画面の切り替えだけを行う
// https://contactform7.com/ja/reflection/
document.addEventListener("DOMContentLoaded", () => {
  const inputSection = document.querySelector(".js-cf7-input");
  const confirmSection = document.querySelector(".js-cf7-confirm");
  if (!inputSection || !confirmSection) return;

  const form = inputSection.closest(".wpcf7-form");
  if (!form) return;

  // 表示の切り替えは CSS 側で .is-confirm を見て行う
  const isConfirmVisible = () => form.classList.contains("is-confirm");

  const showInput = () => {
    form.classList.remove("is-confirm");
    window.scrollTo(0, 0);
  };

  const showConfirm = () => {
    form.classList.add("is-confirm");
    window.scrollTo(0, 0);
  };

  // エラー要素にスクロールする関数
  const scrollToError = (element) => {
    const elementTop = element.getBoundingClientRect().top + window.scrollY;
    const offset = 100; // ヘッダーなどの高さを考慮したオフセット

    window.scrollTo({
      top: elementTop - offset,
      behavior: "smooth",
    });

    element.focus();
  };

  // 確認ボタン：全項目をバリデーションし、エラーがなければ確認画面を表示
  const confirmButton = form.querySelector(".js-cf7-confirm-button");
  if (confirmButton) {
    confirmButton.addEventListener("click", (e) => {
      e.preventDefault();

      // wpcf7.validate() は同期的に全項目を検証し、検証を通った値を [reflection] に反映する
      if (typeof wpcf7 !== "undefined" && typeof wpcf7.validate === "function") {
        wpcf7.validate(form);
      }

      const firstError = form.querySelector(".wpcf7-not-valid");
      if (firstError) {
        scrollToError(firstError);
        return;
      }

      showConfirm();
    });
  }

  // 修正ボタン：入力画面に戻る
  const backButton = form.querySelector(".js-cf7-back-button");
  if (backButton) {
    backButton.addEventListener("click", showInput);
  }

  // 送信時にサーバー側バリデーションで弾かれた場合、確認画面のままだとエラーが見えないので入力画面に戻す
  form.addEventListener("wpcf7invalid", () => {
    if (!isConfirmVisible()) return;
    showInput();
    const firstError = form.querySelector(".wpcf7-not-valid");
    if (firstError) {
      scrollToError(firstError);
    }
  });

  // 確認画面から送信が完了したらサンクスページへ遷移
  form.addEventListener("wpcf7mailsent", () => {
    if (isConfirmVisible()) {
      location.href = cf7CustomSettings.thanksPageUrl;
    }
  });
});
