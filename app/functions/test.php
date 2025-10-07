<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// テスト実行コード
// ----------------------------------------------------- //
function dump($var) {
  echo '<pre>';
  var_dump($var);
  echo '</pre>';
}



/**
 * 開発用：現在のリクエストで読み込まれている PHP ファイル、CSS、JS を取得して配列で返す
 *
 * @param array $args
 *   'include_plugins' => bool - プラグインのファイルも表示するか（デフォルト false）
 *   'only_theme'      => bool - テーマ内のPHPファイルのみを表示（true                linkDiv.innerHTML = `
                    <div style="
                        display: flex; 
                        flex-direction: column; 
                        gap: 6px; 
                        padding: 8px 0;
                    ">
                        <div style="
                            font-weight: bold; 
                            color: #ff6b6b; 
                            font-size: 12px;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                        ">
                            <span>🚨 ${statusText}${countText}</span>
                        </div>
                        
                        <div style="
                            font-size: 10px; 
                            color: #81c784; 
                            font-weight: bold;
                            background: rgba(129, 199, 132, 0.1);
                            padding: 4px 8px;
                            border-radius: 4px;
                            border-left: 3px solid #81c784;
                        ">
                            📍 ${link.file || '不明なファイル'}
                        </div>
                        
                        <div style="
                            word-break: break-all; 
                            font-size: 10px; 
                            opacity: 0.9;
                            background: rgba(255, 255, 255, 0.05);
                            padding: 6px 8px;
                            border-radius: 4px;
                            font-family: monospace;
                        ">
                            🔗 ${link.url}
                        </div>
                        
                        <div style="
                            font-size: 9px; 
                            opacity: 0.7;
                            padding: 4px 8px;
                            background: rgba(255, 255, 255, 0.03);
                            border-radius: 4px;
                        ">
                            💬 テキスト: "${link.text || 'なし'}"
                        </div>
                        
                        <div style="
                            font-size: 8px; 
                            opacity: 0.6; 
                            color: #ffeb3b;
                            text-align: center;
                            padding: 4px;
                            border-top: 1px solid rgba(255, 255, 255, 0.1);
                            margin-top: 4px;
                        ">
                            🔍 クリックでページ内の位置をハイライト
                        </div>
                    </div>
                `;ins 無視、デフォルト true）
 * @return array ['php'=>[], 'css'=>[], 'js'=>[]]
 */
function dev_get_loaded_assets( $args = array() ) {
    $defaults = array(
        'include_plugins' => false,
        'only_theme'      => true,  // デフォルトをtrueに変更
    );
    $args = wp_parse_args( $args, $defaults );

    // --- PHP ファイル ---
    $included = get_included_files();
    $theme_dir = get_template_directory();
    $child_dir = is_child_theme() ? get_stylesheet_directory() : $theme_dir;

    $php_files = array();
    foreach ( $included as $file ) {
        $short = $file;
        
        // テーマ内のファイルのみを対象とする場合
        if ( $args['only_theme'] ) {
            // 子テーマのファイルをチェック
            if ( strpos( $file, $child_dir ) === 0 ) {
                $short = str_replace( $child_dir, '[child-theme]', $file );
                $php_files[] = $short;
                continue;
            }
            // 親テーマのファイルをチェック
            if ( $theme_dir && strpos( $file, $theme_dir ) === 0 ) {
                $short = str_replace( $theme_dir, '[theme]', $file );
                $php_files[] = $short;
                continue;
            }
            // テーマ外のファイルはスキップ
            continue;
        }
        
        // 全てのファイルを対象とする場合（従来の動作）
        if ( strpos( $file, $child_dir ) === 0 ) {
            $short = str_replace( $child_dir, '[child-theme]', $file );
            $php_files[] = $short;
            continue;
        }
        if ( $theme_dir && strpos( $file, $theme_dir ) === 0 ) {
            $short = str_replace( $theme_dir, '[theme]', $file );
            $php_files[] = $short;
            continue;
        }

        if ( $args['include_plugins'] ) {
            $wp_plugin_dir = WP_PLUGIN_DIR;
            if ( strpos( $file, $wp_plugin_dir ) === 0 ) {
                $short = str_replace( $wp_plugin_dir, '[plugin]', $file );
                $php_files[] = $short;
                continue;
            }
        }

        $php_files[] = $short;
    }

    // CSS / JS
    global $wp_styles, $wp_scripts;
    $css = array();
    $js  = array();

    if ( isset( $wp_styles ) && is_object( $wp_styles ) ) {
        foreach ( $wp_styles->queue as $handle ) {
            if ( empty( $wp_styles->registered[ $handle ] ) ) continue;
            $obj = $wp_styles->registered[ $handle ];
            $src = $obj->src;

            if ( $src && strpos( $src, 'http' ) !== 0 && strpos( $src, '//' ) !== 0 ) {
                $src = $wp_styles->base_url . $src;
            }
            $src = set_url_scheme( $src );

            $css[] = array(
                'handle' => $handle,
                'src'    => $src,
                'deps'   => $obj->deps,
                'ver'    => $obj->ver,
                'args'   => $obj->args,
            );
        }
    }

    if ( isset( $wp_scripts ) && is_object( $wp_scripts ) ) {
        foreach ( $wp_scripts->queue as $handle ) {
            if ( empty( $wp_scripts->registered[ $handle ] ) ) continue;
            $obj = $wp_scripts->registered[ $handle ];
            $src = $obj->src;

            if ( $src && strpos( $src, 'http' ) !== 0 && strpos( $src, '//' ) !== 0 ) {
                $src = $wp_scripts->base_url . $src;
            }
            $src = set_url_scheme( $src );

            $js[] = array(
                'handle'    => $handle,
                'src'       => $src,
                'deps'      => $obj->deps,
                'ver'       => $obj->ver,
                'in_footer' => $obj->extra && ! empty( $obj->extra['group'] ) && $obj->extra['group'] === 1,
            );
        }
    }

    return array(
        'php' => array_values( array_unique( $php_files ) ),
        'css' => $css,
        'js'  => $js,
    );
}

/**
 * 開発用：HTML で見やすく出力するテンプレートタグ
 */
function dev_print_loaded_assets( $args = array() ) {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        return;
    }

    $assets = dev_get_loaded_assets( $args );

    echo '<style>
    .dev-loaded-assets { font-family:system-ui,"Noto Sans JP",sans-serif; font-size:14px; background:#fff; border:1px solid #ddd; padding:12px; margin:10px 0; border-radius:6px; }
    .dev-loaded-assets h3 { margin:50px 0 8px; font-size:15px; }
    .dev-loaded-assets pre { white-space:pre-wrap; word-break:break-all; background:#f8f8f8; padding:8px; border-radius:4px; }
    .dev-loaded-assets table { width:100%; border-collapse:collapse; margin-bottom:6px; }
    .dev-loaded-assets th, .dev-loaded-assets td { border-bottom:1px solid #eee; padding:6px; text-align:left; font-size:13px; }
    </style>';

    echo '<div class="dev-loaded-assets">';
    echo '<h3>読み込まれているテーマ内の PHP ファイル</h3>';
    if ( empty( $assets['php'] ) ) {
        echo '<div>なし</div>';
    } else {
        echo '<pre>';
        foreach ( $assets['php'] as $f ) {
            echo esc_html( $f ) . "\n";
        }
        echo '</pre>';
    }

    echo '<h3>読み込まれている CSS</h3>';
    if ( empty( $assets['css'] ) ) {
        echo '<div>なし</div>';
    } else {
        echo '<table><thead><tr><th>handle</th><th>src</th><th>ver</th><th>deps</th></tr></thead><tbody>';
        foreach ( $assets['css'] as $c ) {
            echo '<tr>';
            echo '<td>' . esc_html( $c['handle'] ) . '</td>';
            echo '<td><code>' . esc_html( $c['src'] ) . '</code></td>';
            echo '<td>' . esc_html( (string) $c['ver'] ) . '</td>';
            echo '<td>' . esc_html( implode( ', ', (array) $c['deps'] ) ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    echo '<h3>読み込まれている JS</h3>';
    if ( empty( $assets['js'] ) ) {
        echo '<div>なし</div>';
    } else {
        echo '<table><thead><tr><th>handle</th><th>src</th><th>ver</th><th>deps</th><th>footer</th></tr></thead><tbody>';
        foreach ( $assets['js'] as $j ) {
            echo '<tr>';
            echo '<td>' . esc_html( $j['handle'] ) . '</td>';
            echo '<td><code>' . esc_html( $j['src'] ) . '</code></td>';
            echo '<td>' . esc_html( (string) $j['ver'] ) . '</td>';
            echo '<td>' . esc_html( implode( ', ', (array) $j['deps'] ) ) . '</td>';
            echo '<td>' . ( $j['in_footer'] ? 'yes' : 'no' ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * ショートコード [show_loaded_assets]
 */
function dev_loaded_assets_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'include_plugins' => '0',
        'only_theme'      => '1',  // デフォルトを1（true）に変更
    ), $atts, 'show_loaded_assets' );

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        return '';
    }

    ob_start();
    dev_print_loaded_assets( array(
        'include_plugins' => (bool) intval( $atts['include_plugins'] ),
        'only_theme'      => (bool) intval( $atts['only_theme'] ),
    ) );
    return ob_get_clean();
}
add_shortcode( 'show_loaded_assets', 'dev_loaded_assets_shortcode' );


// テーマ内実行例
//<?php dev_print_loaded_assets();





// ----------------------------------------------------- //
// 管理者用404エラー通知機能
// ----------------------------------------------------- //

/**
 * ページ全体のリンクをチェックして404エラー一覧を右下に表示
 * テーマのPHPファイルから出力されるリンクのみが対象
 */
function admin_link_checker_script() {
    // 管理者以外には表示しない
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // 管理画面、ログイン画面、AJAX、REST APIでは表示しない
    if ( is_admin() || 
         wp_doing_ajax() || 
         (defined('REST_REQUEST') && REST_REQUEST) ||
         is_login() ||
         (isset($_GET['wp-admin']) || strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false) ) {
        return;
    }
    
    ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // テーマファイルから出力されるリンクのみをチェック対象とする
    // WordPress管理バー、プラグイン、ウィジェットのリンクは除外

    let totalLinks = 0;
    let checkedLinks = 0;
    let brokenLinks = [];
    let urlCounts = new Map(); // urlCountsをグローバルスコープに移動

    // エラー一覧パネルを作成
    const errorPanel = document.createElement('div');
    errorPanel.id = 'broken-links-panel';
    errorPanel.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            max-height: 400px;
            background: rgba(30, 30, 30, 0.95);
            color: white;
            border-radius: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            z-index: 9999;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: none;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        `;

    errorPanel.innerHTML = `
            <div style="
                padding: 15px 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
            ">
                <div>
                    <span style="font-weight: bold;">🔍 テーマリンクチェック</span>
                    <div id="check-status" style="font-size: 11px; opacity: 0.8; margin-top: 2px;">チェック中...</div>
                </div>
                <button onclick="document.getElementById('broken-links-panel').style.display='none'" style="
                    background: none;
                    border: none;
                    color: white;
                    cursor: pointer;
                    font-size: 16px;
                    opacity: 0.7;
                    padding: 0;
                    width: 20px;
                    height: 20px;
                ">✕</button>
            </div>
            
            <div id="broken-links-list" style="
                max-height: 320px;
                overflow-y: auto;
                padding: 8px 0;
            ">
                <!-- エラーリンクリストがここに表示される -->
            </div>
            
            <div id="check-summary" style="
                padding: 10px 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                font-size: 10px;
                opacity: 0.8;
                line-height: 1.4;
            ">
                <!-- チェックサマリーがここに表示される -->
            </div>
            
            <div id="file-summary" style="
                padding: 8px 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.05);
                font-size: 9px;
                opacity: 0.7;
                color: #81c784;
                line-height: 1.5;
                max-height: 80px;
                overflow-y: auto;
                background: rgba(129, 199, 132, 0.03);
            ">
                <!-- ファイルサマリーがここに表示される -->
            </div>
        `;

    document.body.appendChild(errorPanel);

    const statusDiv = document.getElementById('check-status');
    const linksList = document.getElementById('broken-links-list');
    const summaryDiv = document.getElementById('check-summary');
    const fileSummaryDiv = document.getElementById('file-summary');

    // パネル更新関数
    function updatePanel() {
        if (checkedLinks < totalLinks) {
            statusDiv.textContent = `チェック中... (${checkedLinks}/${totalLinks})`;
            errorPanel.style.display = 'block';
        } else {
            if (brokenLinks.length === 0) {
                statusDiv.textContent = '✅ 問題なし';
                summaryDiv.textContent = `${totalLinks}個のリンクをチェック - エラーなし`;
                fileSummaryDiv.textContent = '';

                // 3秒後にパネルを非表示
                setTimeout(() => {
                    errorPanel.style.display = 'none';
                }, 3000);
            } else {
                // エラーの種類とファイル情報を集計
                const errorTypes = {};
                const errorFiles = new Set();
                brokenLinks.forEach(link => {
                    const status = link.status;
                    errorTypes[status] = (errorTypes[status] || 0) + 1;
                    if (link.file && link.file !== '不明なファイル') {
                        errorFiles.add(link.file);
                    }
                });

                const errorSummary = Object.keys(errorTypes).map(status => {
                    let statusName = '';
                    switch (status) {
                        case 404:
                            statusName = '404';
                            break;
                        default:
                            statusName = status;
                    }
                    return `${statusName}(${errorTypes[status]})`;
                }).join(', ');

                const fileSummary = errorFiles.size > 0 ?
                    ` - 影響ファイル: ${Array.from(errorFiles).slice(0, 3).join(', ')}${errorFiles.size > 3 ? '他' : ''}` : '';

                statusDiv.textContent = `🚨 ${brokenLinks.length}個のエラー発見`;
                summaryDiv.textContent = `${totalLinks}個のリンク中 ${brokenLinks.length}個にエラー - ${errorSummary}`;

                // ファイル別の詳細サマリーを表示（URLのみ）
                if (errorFiles.size > 0) {
                    const allErrorUrls = brokenLinks.map(link => {
                        const shortUrl = link.url.length > 50 ?
                            link.url.substring(0, 47) + '...' :
                            link.url;
                        return `<div style="
                                font-size: 9px; 
                                color: #ff9999; 
                                margin: 2px 0; 
                                padding: 3px 8px;
                                background: rgba(255, 107, 107, 0.1);
                                border-radius: 3px;
                                font-family: monospace;
                                word-break: break-all;
                                border-left: 2px solid #ff6b6b;
                            ">${shortUrl}</div>`;
                    }).join('');

                    fileSummaryDiv.innerHTML = allErrorUrls;
                } else {
                    fileSummaryDiv.innerHTML = '';
                }

                updateErrorList();
            }
        }
    }

    // エラーリスト更新関数
    function updateErrorList() {
        linksList.innerHTML = '';

        if (brokenLinks.length === 0) {
            linksList.innerHTML = '<div style="padding: 20px; text-align: center; opacity: 0.7;">404エラーは見つかりませんでした</div>';
            return;
        }

        brokenLinks.forEach((link, index) => {
            const linkDiv = document.createElement('div');
            linkDiv.style.cssText = `
                    padding: 16px 20px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                    cursor: pointer;
                    transition: background-color 0.2s;
                    ${index === brokenLinks.length - 1 ? 'border-bottom: none;' : ''}
                `;

            const count = urlCounts.get(link.url) || 1;
            const countText = count > 1 ? ` ×${count}` : '';

            // エラーステータスを表示用に変換（404のみ）
            let statusText = '';
            switch (link.status) {
                case 404:
                    statusText = '404 Not Found';
                    break;
                default:
                    statusText = `${link.status} エラー`;
            }

            linkDiv.innerHTML = `
                    <div style="font-weight: bold; color: #ff6b6b; margin-bottom: 4px;">
                        ${statusText}${countText}
                    </div>
                    <div style="font-size: 10px; opacity: 0.8; color: #81c784; margin-bottom: 4px; font-weight: bold;">
                        📍 ${link.file || '不明なファイル'}
                    </div>
                    <div style="word-break: break-all; font-size: 11px; opacity: 0.9; margin-bottom: 4px;">
                        ${link.url}
                    </div>
                    <div style="font-size: 10px; opacity: 0.7; margin-bottom: 4px;">
                        テキスト: "${link.text || 'なし'}"
                    </div>
                    <div style="font-size: 9px; opacity: 0.6; color: #ffeb3b;">
                        � クリックでページ内の位置をハイライト
                    </div>
                `;

            linkDiv.onmouseenter = function() {
                this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            };
            linkDiv.onmouseleave = function() {
                this.style.backgroundColor = 'transparent';
            };

            linkDiv.onclick = function() {
                // より正確にリンク要素を特定
                // 完全なURLと相対URLの両方で検索
                const fullUrl = link.url;
                const relativeUrl = new URL(fullUrl).pathname;
                const hrefValue = link.element ? link.element.getAttribute('href') : null;

                let linkElements = [];

                // 1. 完全なURLで検索
                linkElements = Array.from(document.querySelectorAll(`a[href="${fullUrl}"]`));

                // 2. 相対URLで検索
                if (linkElements.length === 0) {
                    linkElements = Array.from(document.querySelectorAll(`a[href="${relativeUrl}"]`));
                }

                // 3. 元のhref値で検索
                if (linkElements.length === 0 && hrefValue) {
                    linkElements = Array.from(document.querySelectorAll(`a[href="${hrefValue}"]`));
                }

                // 4. URLの一部で検索
                if (linkElements.length === 0) {
                    const urlParts = fullUrl.split('/');
                    const lastPart = urlParts[urlParts.length - 1];
                    if (lastPart) {
                        linkElements = Array.from(document.querySelectorAll(`a[href*="${lastPart}"]`));
                    }
                }

                console.log('Search results:', {
                    fullUrl: fullUrl,
                    relativeUrl: relativeUrl,
                    hrefValue: hrefValue,
                    foundElements: linkElements.length,
                    elements: linkElements
                });

                if (linkElements.length > 0) {
                    linkElements.forEach(el => highlightElement(el));
                } else {
                    console.log('No matching link elements found for:', fullUrl);
                    // フォールバック: 全てのリンクをチェック
                    const allLinks = document.querySelectorAll('a[href]');
                    console.log('All links in page:', Array.from(allLinks).map(l => l.getAttribute('href')));
                }

                function highlightElement(el) {
                    // 既存のハイライトをクリア
                    const existingHighlights = document.querySelectorAll('.error-link-highlight');
                    existingHighlights.forEach(highlight => highlight.remove());

                    // より目立つハイライト効果
                    el.style.outline = '3px solid #ff6b6b';
                    el.style.outlineOffset = '2px';
                    el.style.backgroundColor = 'rgba(255, 107, 107, 0.2)';
                    el.style.borderRadius = '3px';

                    // スクロールして表示
                    el.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // 要素の近くに説明テキストを表示
                    const tooltip = document.createElement('div');
                    tooltip.className = 'error-link-highlight';
                    tooltip.style.cssText = `
                            position: absolute;
                            background: #ff6b6b;
                            color: white;
                            padding: 5px 10px;
                            border-radius: 4px;
                            font-size: 12px;
                            font-weight: bold;
                            z-index: 10000;
                            pointer-events: none;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        `;
                    tooltip.textContent = `エラーリンク (${statusText})`;

                    const rect = el.getBoundingClientRect();
                    tooltip.style.left = (rect.left + window.scrollX) + 'px';
                    tooltip.style.top = (rect.top + window.scrollY - 35) + 'px';

                    document.body.appendChild(tooltip);

                    // 5秒後にハイライトを削除
                    setTimeout(() => {
                        el.style.outline = '';
                        el.style.outlineOffset = '';
                        el.style.backgroundColor = '';
                        el.style.borderRadius = '';
                        tooltip.remove();
                    }, 5000);
                }
            };

            linksList.appendChild(linkDiv);
        });
    }

    // URLをチェックする関数
    async function checkUrl(url, linkText, fileInfo) {
        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'check_link_status',
                    url: url,
                    nonce: '<?php echo wp_create_nonce('check_link_nonce'); ?>'
                })
            });

            // HTTP応答自体が失敗している場合（404のみチェック）
            if (!response.ok) {
                console.log('HTTP response failed for:', url, response.status);
                checkedLinks++;
                // 404エラーのみを追加
                if (response.status === 404) {
                    brokenLinks.push({
                        url: url,
                        status: response.status,
                        text: linkText,
                        file: fileInfo
                    });
                }
                updatePanel();
                return;
            }

            const result = await response.json();

            checkedLinks++;

            // エラー判定を修正：404エラーのみを対象とする
            if (!result.success || (result.data && result.data.status === 404)) {
                const status = result.data ? result.data.status : 'エラー';
                // 404エラーのみを追加
                if (status === 404) {
                    brokenLinks.push({
                        url: url,
                        status: status,
                        text: linkText,
                        file: fileInfo
                    });
                }
            }

            updatePanel();

        } catch (error) {
            console.log('Link check failed for:', url, error);
            checkedLinks++;
            // ネットワークエラーは404チェックの対象外とする
            // brokenLinks.push({
            //     url: url,
            //     status: 'ネットワークエラー',
            //     text: linkText,
            //     file: fileInfo
            // });
            updatePanel();
        }
    }

    // ファイルパスと行番号を取得する関数
    function getFileInfo(element) {
        // data-file属性がある場合はそれを使用
        let fileAttr = element.getAttribute('data-file');
        let lineAttr = element.getAttribute('data-line');

        if (fileAttr) {
            const lineInfo = lineAttr ? `:${lineAttr}行目` : '';
            return `${fileAttr}${lineInfo}`;
        }

        // 親要素を遡ってファイル情報を探す
        let parent = element.closest('[data-file]');
        if (parent) {
            const parentFile = parent.getAttribute('data-file');
            const parentLine = parent.getAttribute('data-line');
            const lineInfo = parentLine ? `:${parentLine}行目` : '';
            return `${parentFile}${lineInfo}`;
        }

        // HTMLコメントからファイル情報と行番号を推測
        let node = element;
        let lineNumber = getElementLineNumber(element);

        while (node && node.parentNode) {
            if (node.nodeType === Node.COMMENT_NODE && node.textContent.includes('.php')) {
                const match = node.textContent.match(/([a-zA-Z0-9_-]+\.php)/);
                if (match) {
                    return `${match[1]}:${lineNumber}行目`;
                }
            }
            node = node.previousSibling || node.parentNode;
        }

        // 現在のページのファイル名を推測
        const currentPath = window.location.pathname;
        let fileName = '';

        // WordPressテンプレート階層に基づいてファイル名を推測
        if (currentPath === '/' || currentPath === '') {
            fileName = 'index.php';
        } else if (currentPath.includes('/wp-admin/')) {
            fileName = 'WordPress管理画面';
        } else {
            const pathParts = currentPath.split('/').filter(p => p);
            const lastPart = pathParts[pathParts.length - 1];

            if (lastPart) {
                // 投稿タイプに基づく推測
                if (pathParts.includes('category')) {
                    fileName = 'category.php';
                } else if (pathParts.includes('tag')) {
                    fileName = 'tag.php';
                } else if (pathParts.includes('author')) {
                    fileName = 'author.php';
                } else if (pathParts.includes('search')) {
                    fileName = 'search.php';
                } else if (pathParts.length === 1) {
                    fileName = `page-${lastPart}.php`;
                } else {
                    fileName = 'single.php';
                }
            } else {
                fileName = 'index.php';
            }
        }

        return `${fileName}:${lineNumber}行目`;
    }

    // 要素の推定行番号を取得する関数
    function getElementLineNumber(element) {
        // DOM内の位置から行番号を推測
        let lineNumber = 1;
        let walker = document.createTreeWalker(
            document.documentElement,
            NodeFilter.SHOW_ALL,
            null,
            false
        );

        let currentNode;
        while (currentNode = walker.nextNode()) {
            if (currentNode === element) {
                break;
            }
            if (currentNode.nodeType === Node.ELEMENT_NODE && ['DIV', 'P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'BR'].includes(currentNode.tagName)) {
                lineNumber++;
            }
        }

        // より正確な行番号の推測（要素の種類とネストレベルを考慮）
        const rect = element.getBoundingClientRect();
        const documentHeight = document.documentElement.scrollHeight;
        const estimatedLine = Math.floor((rect.top + window.scrollY) / documentHeight * 100) + 10;

        return Math.max(lineNumber, estimatedLine);
    }

    // ページの全リンクをチェック（テーマ範囲のみ）
    function checkAllLinks() {
        // WordPress管理バーやプラグイン関連の要素を除外
        // WordPress標準のリンクのみを除外、カスタムリンクは対象にする
        const excludeSelectors = [
            '#wpadminbar', // WordPress管理バー
            '#wp-admin-bar-root-default', // 管理バーメニュー
            '.wp-admin', // WordPress管理関連
            '.admin-bar-menu', // 管理バーメニュー
            '.wp-block-loginout', // ログイン/ログアウトブロック
            '.wp-block-calendar .wp-calendar-nav', // カレンダーナビゲーション
            '[data-wp-editing]' // ブロックエディタ編集関連
        ];

        // 除外要素内のリンクをフィルタリング
        let allLinks = document.querySelectorAll('a[href]');
        let themeLinks = [];

        allLinks.forEach(link => {
            let shouldExclude = false;

            // 除外対象の親要素内にあるかチェック
            for (let selector of excludeSelectors) {
                if (link.closest(selector)) {
                    shouldExclude = true;
                    break;
                }
            }

            // WordPress標準の管理関連URLのみを除外
            const href = link.getAttribute('href');
            if (href && (
                    href.includes('/wp-admin/') ||
                    href.includes('/wp-login.php') ||
                    href.includes('wp-admin') ||
                    href.includes('admin-ajax.php') ||
                    href.includes('/xmlrpc.php')
                )) {
                shouldExclude = true;
            }

            if (!shouldExclude) {
                themeLinks.push(link);
            }
        });

        const uniqueUrls = new Set();
        const linkData = [];

        // urlCountsを初期化（既に上で定義済み）
        urlCounts.clear();

        themeLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) {
                return;
            }

            try {
                const url = new URL(href, window.location.href);

                // 同一ドメインかつWordPress管理系以外のURLを対象にする
                if (url.hostname === window.location.hostname &&
                    !url.pathname.includes('/wp-admin/') &&
                    !url.pathname.includes('/wp-login.php')) {

                    // URLのカウントを増やす
                    urlCounts.set(url.href, (urlCounts.get(url.href) || 0) + 1);

                    // 初回のみlinkDataに追加
                    if (!uniqueUrls.has(url.href)) {
                        uniqueUrls.add(url.href);
                        linkData.push({
                            url: url.href,
                            text: link.textContent.trim().substring(0, 50),
                            file: getFileInfo(link),
                            element: link
                        });
                    }
                }
            } catch (e) {
                // 無効なURLは無視
            }
        });

        totalLinks = linkData.length;

        if (totalLinks === 0) {
            statusDiv.textContent = 'チェック対象のリンクがありません';
            summaryDiv.textContent = 'テーマ内のリンクが見つかりませんでした';
            errorPanel.style.display = 'block';

            setTimeout(() => {
                errorPanel.style.display = 'none';
            }, 3000);

            return;
        }

        // パネルを表示
        errorPanel.style.display = 'block';
        updatePanel();

        // リンクを順次チェック（サーバー負荷を軽減するため遅延実行）
        linkData.forEach((link, index) => {
            setTimeout(() => {
                checkUrl(link.url, link.text, link.file);
            }, index * 200); // 200ms間隔
        });
    }

    // ページロード後にチェック開始
    setTimeout(checkAllLinks, 1000);
});
</script>

<style>
#broken-links-panel {
    animation: slideInUp 0.3s ease-out;
}

@keyframes slideInUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
<?php
}
add_action( 'wp_footer', 'admin_link_checker_script' );

/**
 * AJAX でリンクの状態をチェック
 */
function ajax_check_link_status() {
    // ノンス検証
    if ( ! wp_verify_nonce( $_POST['nonce'], 'check_link_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    // 管理者権限チェック
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Insufficient permissions' );
    }
    
    $url = sanitize_url( $_POST['url'] );
    
    if ( empty( $url ) ) {
        wp_send_json_error( 'Invalid URL' );
    }
    
    // リモートリクエストでURLの状態をチェック
    $response = wp_remote_head( $url, array(
        'timeout' => 5,
        'redirection' => 3,
        'user-agent' => 'WordPress Link Checker'
    ) );
    
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array(
            'message' => 'Request failed',
            'error' => $response->get_error_message()
        ) );
    }
    
    $status_code = wp_remote_retrieve_response_code( $response );
    
    wp_send_json_success( array(
        'status' => $status_code,
        'url' => $url
    ) );
}
add_action( 'wp_ajax_check_link_status', 'ajax_check_link_status' );