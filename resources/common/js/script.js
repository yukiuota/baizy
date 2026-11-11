/************************************
スムーススクロール
*************************************/
const headerHeightOption = 0; // ここに「0」または「1」を指定します（0: ヘッダーの高さを配慮しない, 1: 配慮する）

// 対象の要素までスムーズスクロールする
const smoothScrollToTarget = (targetElement) => {
  const header = document.querySelector(".header");
  const gap = headerHeightOption === 1 && header ? header.offsetHeight : 0;
  const target = targetElement.getBoundingClientRect().top + window.scrollY - gap;
  window.scrollTo({
    top: target,
    behavior: "smooth",
  });
};

// ハッシュ（#foo）に対応する要素を返す。対象がなければ null
const getHashTarget = (hash) => {
  const id = hash.replace(/^#/, "");
  if (!id) return null;
  // location.hash は日本語の id を URL エンコードして返すため、復号してから探す
  try {
    return document.getElementById(decodeURIComponent(id));
  } catch {
    return document.getElementById(id);
  }
};

function smoothScroll() {
  // ページ内リンク（リンク内の子要素をクリックした場合も拾えるよう、document で受けて closest でたどる）
  document.addEventListener("click", (e) => {
    const link = e.target instanceof Element ? e.target.closest('a[href^="#"]') : null;
    if (!link) return;

    const href = link.getAttribute("href");
    const targetElement = getHashTarget(href);
    // href="#" など対象の要素がないリンクは、ブラウザ標準の挙動に任せる
    if (!targetElement) return;

    e.preventDefault();
    smoothScrollToTarget(targetElement);
    // 履歴に追加して、ブラウザの「戻る」でスクロール前の位置に戻れるようにする
    history.pushState(null, "", href);
  });

  // 別ページから #付きの URL で遷移してきたとき
  window.addEventListener("load", () => {
    const targetElement = getHashTarget(window.location.hash);
    if (targetElement) {
      smoothScrollToTarget(targetElement);
    }
  });
}


/************************************
ハンバーガーメニュー
*************************************/
// 開閉アニメーションとスクロールロックはCSS側（@starting-style / :has()）で処理
function headerMenu() {
  const menuTrigger = document.getElementById("js-menu-trigger");
  const menu = document.getElementById("js-menu");

  if (!menuTrigger || !menu) return;

  menuTrigger.addEventListener("click", () => {
    menu.classList.toggle("is-open");
    menuTrigger.classList.toggle("active");
  });

  const menuLinks = document.querySelectorAll(".header-menu a");

  menuLinks.forEach((link) => {
    link.addEventListener("click", () => {
      menu.classList.remove("is-open");
      menuTrigger.classList.remove("active");
    });
  });
}


/************************************
スクロールフェード（.view01〜.view05）
表示領域に入ったら .view-on を一度だけ付与（アニメーション本体は_fade.scss）
data-delay="ミリ秒" で遅延を指定可能
*************************************/
document.addEventListener("DOMContentLoaded", () => {
  const targets = document.querySelectorAll(".view01, .view02, .view03, .view04, .view05");
  if (targets.length === 0) return;

  const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      const target = entry.target;
      const delay = Number(target.dataset.delay) || 0;
      setTimeout(() => target.classList.add("view-on"), delay);
      observer.unobserve(target);
    });
  });

  targets.forEach((target) => observer.observe(target));
});


/************************************
スライドアニメーション
*************************************/
// 開閉アニメーションはCSS側（grid-template-rows）で処理
function slideToggle() {
  const btns = document.querySelectorAll(".js-slide-h_btn");
  const slides = document.querySelectorAll(".js-slide-h");

  if (btns.length === 0 || slides.length === 0) return;

  btns.forEach((btn, index) => {
    btn.addEventListener("click", () => {
      btn.classList.toggle("js-active");
      slides[index]?.classList.toggle("js-active");
    });
  });
}


/************************************
アコーディオン
Web Animations APIで開閉アニメーションを制御
*************************************/
// アニメーションの時間とイージング
const animTiming = {
  duration: 400,
  easing: "ease-out",
};

// 閉じるときのキーフレーム（height: "auto"だとうまく計算されないため要素の高さを指定する）
const closingAnimKeyframes = (content) => [
  { height: content.offsetHeight + "px", opacity: 1 },
  { height: 0, opacity: 0 },
];

// 開くときのキーフレーム
const openingAnimKeyframes = (content) => [
  { height: 0, opacity: 0 },
  { height: content.offsetHeight + "px", opacity: 1 },
];

function setUpAccordion() {
  const details = document.querySelectorAll(".js-details");
  const RUNNING_VALUE = "running"; // アニメーション実行中に付与するカスタムデータ属性の値
  const IS_OPENED_CLASS = "is-opened"; // アイコン操作用のクラス名

  details.forEach((element) => {
    const summary = element.querySelector(".js-summary");
    const content = element.querySelector(".js-content");

    if (!summary || !content) return;

    summary.addEventListener("click", (event) => {
      event.preventDefault();

      // 連打防止。アニメーション中はクリックを受け付けない
      if (element.dataset.animStatus === RUNNING_VALUE) return;

      if (element.open) {
        // 閉じるときの処理
        element.classList.remove(IS_OPENED_CLASS);
        const closingAnim = content.animate(closingAnimKeyframes(content), animTiming);
        element.dataset.animStatus = RUNNING_VALUE;

        closingAnim.onfinish = () => {
          element.removeAttribute("open");
          element.dataset.animStatus = "";
        };
      } else {
        // 開くときの処理
        element.setAttribute("open", "true");
        element.classList.add(IS_OPENED_CLASS);
        const openingAnim = content.animate(openingAnimKeyframes(content), animTiming);
        element.dataset.animStatus = RUNNING_VALUE;

        openingAnim.onfinish = () => {
          element.dataset.animStatus = "";
        };
      }
    });
  });
}


/************************************
タブ切り替え
*************************************/
function tabSelect() {
  const tabs = document.querySelectorAll(".tab");
  const contents = document.querySelectorAll(".tab-content");

  if (tabs.length === 0) return;

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
      tabs.forEach((t, i) => t.classList.toggle("active", i === index));
      contents.forEach((content, i) => content.classList.toggle("show", i === index));
    });
  });
}


/************************************
実行
*************************************/
document.addEventListener("DOMContentLoaded", () => {
  smoothScroll();
  headerMenu();
  slideToggle();
  setUpAccordion();
  tabSelect();
});
