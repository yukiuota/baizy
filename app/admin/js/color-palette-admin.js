jQuery(document).ready(function($) {
  let colorIndex = $('#color-palette-container .color-row').length;
  
  // カラー追加ボタン
  $('#add-color-btn').on('click', function() {
    const newRow = `
      <div class="color-row">
        <label>カラー名:</label>
        <input type="text" name="color_name[${colorIndex}]" placeholder="例: プライマリー" required>
        <label>カラーコード:</label>
        <input type="color" name="color_code[${colorIndex}]" value="#000000" required>
        <input type="text" name="color_code[${colorIndex}]" value="#000000" class="color-code-text" pattern="^#[0-9A-Fa-f]{6}$" required>
        <span class="dashicons dashicons-trash remove-color-btn"></span>
      </div>
    `;
    $('#color-palette-container').append(newRow);
    colorIndex++;
  });
  
  // カラー削除ボタン
  $(document).on('click', '.remove-color-btn', function() {
    if (confirm('このカラーを削除しますか?')) {
      $(this).closest('.color-row').remove();
    }
  });
  
  // カラーピッカーとテキストの同期
  $(document).on('change', 'input[type="color"]', function() {
    $(this).next('.color-code-text').val($(this).val());
  });
  
  $(document).on('change', '.color-code-text', function() {
    const value = $(this).val();
    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
      $(this).prev('input[type="color"]').val(value);
    }
  });
});
