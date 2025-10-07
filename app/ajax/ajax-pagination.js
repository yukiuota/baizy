jQuery(document).ready(function ($) {
  // ページネーションのクリックイベントを監視
  $(document).on("click", ".cp_pagenum", function (e) {
    e.preventDefault();
    const href = $(this).attr("href");
    let page = 1;

    // URLからページ番号を抽出
    if (href.includes("page/")) {
      page = href.match(/page\/(\d+)/)[1];
    } else if (href.includes("paged=")) {
      page = href.match(/paged=(\d+)/)[1];
    }

    // 現在の投稿タイプを取得
    const postType = $("body").data("post-type");

    loadPosts(page, postType);
  });

  function loadPosts(page, postType) {
    // nonceの存在確認
    if (!ajax_object || !ajax_object.nonce) {
      console.error("Ajax nonce is not available");
      return;
    }

    $.ajax({
      url: ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "load_more_posts",
        page: page,
        post_type: postType,
        nonce: ajax_object.nonce,
      },
      beforeSend: function () {
        // ローディング表示
        $(".archive-container").addClass("loading");
      },
      success: function (response) {
        if (response.success) {
          // 投稿リストを更新
          $(".archive-posts").html(response.data.posts);
          // ページネーションを更新
          $("#js-pagination").html(response.data.pagination);

          // ページ番号を更新
          if (response.data.current_page) {
            $("body").attr("data-page", response.data.current_page);
          }

          // 最大ページ数を更新
          if (response.data.max_pages) {
            $("body").attr("data-max-pages", response.data.max_pages);
          }
        } else {
          console.error("Ajax request failed:", response.data);
        }
      },
      error: function (xhr, status, error) {
        console.error("Ajax error:", error);
        console.error("Status:", status);
        console.error("Response:", xhr.responseText);
      },
      complete: function () {
        // ローディング非表示
        $(".archive-container").removeClass("loading");
      },
    });
  }
});
