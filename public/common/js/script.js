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

// ヘッダーメニュー
function headerMenu() {
  document.getElementById("menu-trigger").addEventListener("click", function () {
    let menu = document.getElementById("js-menu");
    menu.style.display = menu.style.display === "none" || menu.style.display === "" ? "block" : "none";
    this.classList.toggle("active");
    document.body.classList.toggle("js-on");
  });

  let menuLinks = document.querySelectorAll(".menu a");
  menuLinks.forEach(function (link) {
    link.addEventListener("click", function () {
      let menu = document.getElementById("js-menu");
      menu.style.display = "none";
      document.getElementById("menu-trigger").classList.toggle("active");
      document.body.classList.toggle("js-on");
    });
  });
}

document.addEventListener("DOMContentLoaded", function () {
  headerMenu();
});

document.addEventListener("DOMContentLoaded", () => {
  const observerOptions = {
    root: null,
    rootMargin: "0px",
    threshold: 0.1, // 要素の表示領域の閾値
  };

  const handleFadeIn = (entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const target = entry.target;
        const delay = target.getAttribute("data-delay") || 0; // デフォルトの遅延時間を設定 (ミリ秒)
        setTimeout(() => {
          target.classList.add("view-on");
          observer.unobserve(target);
        }, delay);
      }
    });
  };

  const observeElementsWithClass = (className) => {
    const elements = document.querySelectorAll("." + className);
    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver(handleFadeIn, observerOptions);
      elements.forEach((element) => {
        observer.observe(element);
      });
    }
  };

  // 各クラスに対してIntersectionObserverを実行
  observeElementsWithClass("view01");
  observeElementsWithClass("view02");
  observeElementsWithClass("view03");
  observeElementsWithClass("view04");
  observeElementsWithClass("view05");
});

// スライドアニメーション
document.addEventListener("DOMContentLoaded", () => {
  const btns = document.querySelectorAll(".js-slide-h_btn");
  const slideHs = document.querySelectorAll(".js-slide-h");

  if (btns.length && slideHs.length) {
    btns.forEach((btn, index) => {
      btn.addEventListener("click", () => {
        btns[index].classList.toggle("js-active");
        slideHs[index].classList.toggle("js-active");
      });
    });
  }
});

// アコーディオン
document.addEventListener("DOMContentLoaded", () => {
  setUpAccordion();
});

/**
 * ブラウザの標準機能(Web Animations API)を使ってアコーディオンのアニメーションを制御します
 */
const setUpAccordion = () => {
  const details = document.querySelectorAll(".js-details");
  const RUNNING_VALUE = "running"; // アニメーション実行中のときに付与する予定のカスタムデータ属性の値
  const IS_OPENED_CLASS = "is-opened"; // アイコン操作用のクラス名

  details.forEach((element) => {
    const summary = element.querySelector(".js-summary");
    const content = element.querySelector(".js-content");

    summary.addEventListener("click", (event) => {
      // デフォルトの挙動を無効化
      event.preventDefault();

      // 連打防止用。アニメーション中だったらクリックイベントを受け付けないでリターンする
      if (element.dataset.animStatus === RUNNING_VALUE) {
        return;
      }

      // detailsのopen属性を判定
      if (element.open) {
        // アコーディオンを閉じるときの処理
        // アイコン操作用クラスを切り替える(クラスを取り除く)
        element.classList.toggle(IS_OPENED_CLASS);

        // アニメーションを実行
        const closingAnim = content.animate(closingAnimKeyframes(content), animTiming);
        // アニメーション実行中用の値を付与
        element.dataset.animStatus = RUNNING_VALUE;

        // アニメーションの完了後に
        closingAnim.onfinish = () => {
          // open属性を取り除く
          element.removeAttribute("open");
          // アニメーション実行中用の値を取り除く
          element.dataset.animStatus = "";
        };
      } else {
        // アコーディオンを開くときの処理
        // open属性を付与
        element.setAttribute("open", "true");

        // アイコン操作用クラスを切り替える(クラスを付与)
        element.classList.toggle(IS_OPENED_CLASS);

        // アニメーションを実行
        const openingAnim = content.animate(openingAnimKeyframes(content), animTiming);
        // アニメーション実行中用の値を入れる
        element.dataset.animStatus = RUNNING_VALUE;

        // アニメーション完了後にアニメーション実行中用の値を取り除く
        openingAnim.onfinish = () => {
          element.dataset.animStatus = "";
        };
      }
    });
  });
};

/**
 * アニメーションの時間とイージング
 */
const animTiming = {
  duration: 400,
  easing: "ease-out",
};

/**
 * アコーディオンを閉じるときのキーフレーム
 */
const closingAnimKeyframes = (content) => [
  {
    height: content.offsetHeight + "px", // height: "auto"だとうまく計算されないため要素の高さを指定する
    opacity: 1,
  },
  {
    height: 0,
    opacity: 0,
  },
];

/**
 * アコーディオンを開くときのキーフレーム
 */
const openingAnimKeyframes = (content) => [
  {
    height: 0,
    opacity: 0,
  },
  {
    height: content.offsetHeight + "px",
    opacity: 1,
  },
];

// タブ切り替え
function tabSelect() {
  let tabs = document.querySelectorAll(".tab");
  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      document.querySelector(".active").classList.remove("active");
      this.classList.add("active");
      const index = Array.from(tabs).indexOf(this);
      document.querySelectorAll(".tab-content").forEach(function (content, contentIndex) {
        content.classList.toggle("show", contentIndex === index);
      });
    });
  });
}

document.addEventListener("DOMContentLoaded", function () {
  tabSelect();
});
