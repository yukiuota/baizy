<div class="c-form js-cf7-input">
    <div class="c-form__item">
        氏名<br>
        [text* your-name autocomplete:name]
    </div>
    <br><br>

    <div class="c-form__item">
        メールアドレス<br>
        [email* your-email autocomplete:email]
    </div>
    <br><br>

    <div class="c-form__item">
        URL<br>
        [url* url]
    </div>
    <br><br>

    <div class="c-form__item">
        電話番号<br>
        [tel* tel]
    </div>
    <br><br>

    <div class="c-form__item">
        数値<br>
        [number* number]
    </div>
    <br><br>

    <div class="c-form__item">
        日付<br>
        [date* date]
    </div>
    <br><br>

    <div class="c-form__item">
        テキストエリア<br>
        [textarea* your-message]
    </div>
    <br><br>

    <div class="c-form__item">
        ドロップダウンメニュー<br>
        [select* select "選択肢 1" "選択肢 2" "選択肢 3"]
    </div>
    <br><br>

    <div class="c-form__item">
        チェックボックス<br>
        [checkbox checkbox "選択肢 1" "選択肢 2" "選択肢 3"]
    </div>
    <br><br>

    <div class="c-form__item">
        ラジオボタン<br>
        [radio radio use_label_element "選択肢 1" "選択肢 2" "選択肢 3"]
    </div>
    <br><br>
    <input type="button" class="c-form-confirm_button js-cf7-confirm-button" value="内容を確認する">
</div>


<br><br><br>

<!-- 確認画面（入力値は CF7 の反映機能 [reflection] で自動表示される） -->
<div class="form c-form-confirm js-cf7-confirm">

    <div class="c-form__item">
        氏名<br>
        [reflection your-name]
    </div>
    <br><br>

    <div class="c-form__item">
        メールアドレス<br>
        [reflection your-email]
    </div>
    <br><br>

    <div class="c-form__item">
        URL<br>
        [reflection url]
    </div>
    <br><br>

    <div class="c-form__item">
        電話番号<br>
        [reflection tel]
    </div>
    <br><br>

    <div class="c-form__item">
        数値<br>
        [reflection number]
    </div>
    <br><br>

    <div class="c-form__item">
        日付<br>
        [reflection date]
    </div>
    <br><br>

    <div class="c-form__item">
        テキストエリア<br>
        [reflection your-message]
    </div>
    <br><br>

    <div class="c-form__item">
        ドロップダウンメニュー<br>
        [reflection select]
    </div>
    <br><br>

    <div class="c-form__item">
        チェックボックス<br>
        [reflection checkbox "選択なし"]
    </div>
    <br><br>

    <div class="c-form__item">
        ラジオボタン<br>
        [reflection radio]
    </div>
    <br><br>

    <input type="button" class="back_button js-cf7-back-button" value="修正する">
    <br>
    [submit "送信"]
</div>
