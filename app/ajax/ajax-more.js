let offset = 5; // 初回表示件数
let moreButton = document.querySelector("#js-more");

if (moreButton) {
  let categoryId = moreButton.getAttribute("data-category-id");

  moreButton.addEventListener("click", function () {
    let xhr = new XMLHttpRequest();
    xhr.open("POST", ajax_object.ajax_url, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

    xhr.onload = function () {
      if (xhr.status >= 200 && xhr.status < 400) {
        try {
          let res = JSON.parse(xhr.responseText);

          // responseが空の場合
          if (!res.data || !res.data.has_more_posts) {
            moreButton.remove();
          } else {
            document.querySelector("#js-post").innerHTML += res.data.output;
            offset += 5; // 表示件数に応じて増加
          }
        } catch (e) {
          console.error("JSONパースエラー");
          moreButton.textContent = "エラーが発生しました";
        }
      } else {
        moreButton.textContent = "エラーが発生しました";
      }
    };

    xhr.onerror = function () {
      moreButton.textContent = "通信エラーが発生しました";
    };

    let data =
      "action=myplugin_more_posts" +
      "&nonce=" +
      encodeURIComponent(ajax_object.nonce) +
      "&offset=" +
      encodeURIComponent(offset) +
      "&category_id=" +
      encodeURIComponent(categoryId || "");

    xhr.send(data);
  });
}
