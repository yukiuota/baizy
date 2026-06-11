/************************************
スムーススクロール
*************************************/
// ページ内リンクに対するスムーズスクロール
const smoothScrollTrigger = document.querySelectorAll('a[href^="#"]');
const headerHeightOption = 0; // ここに「0」または「1」を指定します（0: 配慮しない, 1: 配慮する）

for (let i = 0; i < smoothScrollTrigger.length; i++) {
  smoothScrollTrigger[i].addEventListener("click", (e) => {
    e.preventDefault();
    let href = smoothScrollTrigger[i].getAttribute("href");
    let targetElement = document.getElementById(href.replace("#", ""));
    const rect = targetElement.getBoundingClientRect().top;
    const offset = window.pageYOffset;
    const gap = headerHeightOption === 1 ? document.querySelector(".header").offsetHeight : 0;
    const target = rect + offset - gap;
    window.scrollTo({
      top: target,
      behavior: "smooth",
    });
  });
}

// 別ページへのリンクに対するスムーズスクロール
const smoothScrollToTarget = (targetElement) => {
  const rect = targetElement.getBoundingClientRect().top;
  const offset = window.pageYOffset;
  const gap = headerHeightOption === 1 ? document.querySelector(".header").offsetHeight : 0;
  const target = rect + offset - gap;
  window.scrollTo({
    top: target,
    behavior: "smooth",
  });
};

// ページ読み込み時にURLのハッシュ部分の要素へスムーズスクロール
window.addEventListener("load", () => {
  const hash = window.location.hash;
  if (hash) {
    const targetElement = document.getElementById(hash.replace("#", ""));
    if (targetElement) {
      smoothScrollToTarget(targetElement);
    }
  }
});

// 別ページへのリンクをクリックしたときのスムーズスクロール
document.addEventListener("click", (e) => {
  const targetElement = e.target;
  if (targetElement.tagName === "A" && targetElement.getAttribute("href").startsWith("#")) {
    e.preventDefault();
    const href = targetElement.getAttribute("href");
    const hash = href.replace("#", "");
    const targetElementOnDifferentPage = document.getElementById(hash);
    if (targetElementOnDifferentPage) {
      smoothScrollToTarget(targetElementOnDifferentPage);
      // URLのハッシュ部分を更新（ブラウザの履歴に追加）することで、スムーズスクロールした位置に戻るときに対応する要素に移動します。
      history.pushState(null, null, href);
    }
  }
});


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
  headerMenu();
  slideToggle();
  setUpAccordion();
  tabSelect();
});
