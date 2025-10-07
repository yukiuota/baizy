document.addEventListener("DOMContentLoaded", function () {
  let ajaxurl = ajax_object.ajax_url;
  let nonce = ajax_object.nonce;

  let checkboxes = document.querySelectorAll('.ajax-search input[type="checkbox"]');
  let pagination = document.getElementById("js-pagination");

  function updatePaginationVisibility() {
    if (!pagination) {
      return;
    }

    let anyChecked = Array.from(checkboxes).some(function (cb) {
      return cb.checked;
    });

    if (anyChecked) {
      pagination.style.display = "none";
    } else {
      pagination.style.display = "block";
    }
  }

  checkboxes.forEach(function (checkbox) {
    checkbox.addEventListener("change", function () {
      let selectedTerms = {
        cat01: [],
        cat02: [],
      };

      checkboxes.forEach(function (cb) {
        if (cb.checked) {
          if (cb.name === "cat01") {
            selectedTerms.cat01.push(cb.value);
          } else if (cb.name === "cat02") {
            selectedTerms.cat02.push(cb.value);
          }
        }
      });

      updatePaginationVisibility();

      let xhr = new XMLHttpRequest();
      xhr.open("POST", ajaxurl, true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

      xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 400) {
          if (xhr.responseText.includes("wp_die")) {
            document.getElementById("ajax-posts").innerHTML = "<p>エラーが発生しました。</p>";
            return;
          }
          document.getElementById("ajax-posts").innerHTML = xhr.responseText;
        } else {
          document.getElementById("ajax-posts").innerHTML = "<p>エラーが発生しました。</p>";
        }
      };

      xhr.onerror = function () {
        document.getElementById("ajax-posts").innerHTML = "<p>通信エラーが発生しました。</p>";
      };

      let data =
        "action=filter_posts&nonce=" +
        encodeURIComponent(nonce) +
        "&terms=" +
        encodeURIComponent(JSON.stringify(selectedTerms));
      xhr.send(data);
    });
  });

  updatePaginationVisibility();
});
