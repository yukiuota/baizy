<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1回限り表示ページ機能
 * 
 * 申し込み・登録後に1度だけ表示されるページを管理
 * 2回目以降のアクセスはトップページにリダイレクト
 */
class Baizy_One_Time_Page {
    
    /**
     * オプション名
     */
    const OPTION_NAME = 'baizy_one_time_pages';
    
    /**
     * セッションキー
     */
    const SESSION_KEY = 'baizy_one_time_page_viewed';
    
    /**
     * URLパラメータ名
     */
    const URL_PARAM = 'one_time_token';
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        // セッション開始
        add_action( 'init', array( $this, 'start_session' ), 1 );
        
        // リダイレクト処理
        add_action( 'template_redirect', array( $this, 'check_and_redirect' ), 1 );
        
        // 管理画面設定
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // 投稿・固定ページ編集画面にメタボックス追加
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
        
        // トークン生成用のショートコード
        add_shortcode( 'one_time_page_link', array( $this, 'shortcode_link' ) );
        
        // Contact Form 7との自動連携
        add_action( 'wp_footer', array( $this, 'auto_redirect_cf7' ) );
        
        // 管理画面用スタイル
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }
    
    /**
     * セッション開始
     */
    public function start_session() {
        if ( ! session_id() && ! headers_sent() ) {
            session_start();
        }
    }
    
    /**
     * 1回限り表示ページかチェックしてリダイレクト
     */
    public function check_and_redirect() {
        if ( is_admin() || ! is_singular() ) {
            return;
        }
        
        global $post;
        
        // このページが1回限り表示ページに設定されているかチェック
        $is_one_time_page = get_post_meta( $post->ID, '_baizy_is_one_time_page', true );
        
        if ( $is_one_time_page !== '1' ) {
            return;
        }
        
        // トークンをチェック
        $has_valid_token = isset( $_GET[ self::URL_PARAM ] ) && ! empty( $_GET[ self::URL_PARAM ] );
        
        // セッションに表示済みフラグがあるかチェック
        $page_viewed_key = self::SESSION_KEY . '_' . $post->ID;
        $already_viewed = isset( $_SESSION[ $page_viewed_key ] ) && $_SESSION[ $page_viewed_key ] === true;
        
        if ( $has_valid_token && ! $already_viewed ) {
            // 初回アクセス: セッションにフラグを設定
            $_SESSION[ $page_viewed_key ] = true;
            
            // トークンをURLから削除してリダイレクト（クリーンなURLにする）
            $clean_url = remove_query_arg( self::URL_PARAM );
            if ( $clean_url !== $_SERVER['REQUEST_URI'] ) {
                wp_safe_redirect( $clean_url );
                exit;
            }
        } elseif ( ! $has_valid_token && $already_viewed ) {
            // 2回目以降のアクセス: トップページにリダイレクト
            $redirect_url = get_post_meta( $post->ID, '_baizy_one_time_redirect_url', true );
            if ( empty( $redirect_url ) ) {
                $redirect_url = home_url( '/' );
            }
            wp_safe_redirect( $redirect_url );
            exit;
        } elseif ( ! $has_valid_token && ! $already_viewed ) {
            // トークンなしで初回アクセス: トップページにリダイレクト
            $redirect_url = get_post_meta( $post->ID, '_baizy_one_time_redirect_url', true );
            if ( empty( $redirect_url ) ) {
                $redirect_url = home_url( '/' );
            }
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
    
    /**
     * 1回限りページへのリンクを生成
     * 
     * @param int $page_id ページID
     * @return string トークン付きURL
     */
    public function generate_link( $page_id ) {
        $token = wp_generate_password( 20, false );
        $url = get_permalink( $page_id );
        return add_query_arg( self::URL_PARAM, $token, $url );
    }
    
    /**
     * ショートコード: 1回限りページへのリンク生成
     * 
     * 使用例: [one_time_page_link page_id="123" text="こちら"]
     */
    public function shortcode_link( $atts ) {
        $atts = shortcode_atts( array(
            'page_id' => '',
            'text' => '登録完了ページへ',
            'class' => '',
        ), $atts );
        
        if ( empty( $atts['page_id'] ) ) {
            return '';
        }
        
        $link = $this->generate_link( intval( $atts['page_id'] ) );
        $class = ! empty( $atts['class'] ) ? ' class="' . esc_attr( $atts['class'] ) . '"' : '';
        
        return '<a href="' . esc_url( $link ) . '"' . $class . '>' . esc_html( $atts['text'] ) . '</a>';
    }
    
    /**
     * 管理メニューに設定ページを追加
     */
    public function add_admin_menu() {
        add_theme_page(
            '1回限り表示ページ設定',
            '1回限り表示ページ',
            'manage_options',
            'baizy-one-time-page',
            array( $this, 'render_admin_page' )
        );
    }
    
    /**
     * 設定の登録
     */
    public function register_settings() {
        register_setting( 'baizy_one_time_page_settings', self::OPTION_NAME );
    }
    
    /**
     * 管理画面の表示
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // セッションリセット処理
        if ( isset( $_POST['reset_sessions'] ) && check_admin_referer( 'baizy_reset_sessions' ) ) {
            $this->reset_all_sessions();
            echo '<div class="notice notice-success"><p>すべてのセッションをリセットしました。</p></div>';
        }
        
        // 関連付け設定の保存
        if ( isset( $_POST['save_mappings'] ) && check_admin_referer( 'baizy_save_mappings' ) ) {
            $this->save_mappings( $_POST );
            echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
        }
        
        // 関連付け削除
        if ( isset( $_POST['delete_mapping'] ) && check_admin_referer( 'baizy_delete_mapping' ) ) {
            $this->delete_mapping( $_POST['mapping_id'] );
            echo '<div class="notice notice-success"><p>関連付けを削除しました。</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>1回限り表示ページ設定</h1>
            
            <div class="card">
                <h2>機能について</h2>
                <p>このページでは、申し込み・登録後に1度だけ表示されるページを管理します。</p>
                <ul>
                    <li>各ページの編集画面で「1回限り表示ページにする」を有効にできます</li>
                    <li>フォームやページと1回限り表示ページを関連付けできます</li>
                    <li>初回アクセス時のみページが表示されます</li>
                    <li>2回目以降のアクセスは指定したURLにリダイレクトされます</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>フォームとページの関連付け設定</h2>
                <?php $this->render_mapping_form(); ?>
            </div>
            
            <div class="card">
                <h2>設定済み関連付け一覧</h2>
                <?php $this->render_mappings_list(); ?>
            </div>
            
            <div class="card">
                <h2>設定済みページ一覧</h2>
                <?php $this->render_one_time_pages_list(); ?>
            </div>
            
            <div class="card">
                <h2>セッション管理</h2>
                <p>テストなどで再度1回限りページを表示したい場合は、セッションをリセットできます。</p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'baizy_reset_sessions' ); ?>
                    <button type="submit" name="reset_sessions" class="button button-secondary" 
                            onclick="return confirm('すべてのセッションをリセットします。よろしいですか?');">
                        すべてのセッションをリセット
                    </button>
                </form>
                <p class="description">※これにより、すべてのユーザーが再度1回限りページを表示できるようになります。</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * 設定済みページ一覧を表示
     */
    private function render_one_time_pages_list() {
        $args = array(
            'post_type' => array( 'page', 'post' ),
            'posts_per_page' => -1,
            'meta_key' => '_baizy_is_one_time_page',
            'meta_value' => '1',
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>ページタイトル</th>';
            echo '<th>URL</th>';
            echo '<th>リダイレクト先</th>';
            echo '<th>トークン付きリンク</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $redirect_url = get_post_meta( $post_id, '_baizy_one_time_redirect_url', true );
                if ( empty( $redirect_url ) ) {
                    $redirect_url = home_url( '/' );
                }
                
                $token_link = $this->generate_link( $post_id );
                
                echo '<tr>';
                echo '<td><a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( get_the_title() ) . '</a></td>';
                echo '<td><a href="' . esc_url( get_permalink() ) . '" target="_blank">' . esc_html( get_permalink() ) . '</a></td>';
                echo '<td>' . esc_html( $redirect_url ) . '</td>';
                echo '<td><input type="text" value="' . esc_attr( $token_link ) . '" readonly style="width: 100%;" onclick="this.select();"></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            wp_reset_postdata();
        } else {
            echo '<p>現在、1回限り表示ページに設定されているページはありません。</p>';
        }
    }
    
    /**
     * すべてのセッションをリセット
     */
    private function reset_all_sessions() {
        if ( isset( $_SESSION ) ) {
            foreach ( $_SESSION as $key => $value ) {
                if ( strpos( $key, self::SESSION_KEY ) === 0 ) {
                    unset( $_SESSION[ $key ] );
                }
            }
        }
    }
    
    /**
     * 関連付け設定フォームを表示
     */
    private function render_mapping_form() {
        // 1回限り表示ページ一覧を取得
        $one_time_pages = $this->get_one_time_pages();
        
        // すべてのページを取得
        $all_pages = get_pages( array( 'number' => 0 ) );
        
        // Contact Form 7のフォーム一覧を取得
        $cf7_forms = array();
        if ( function_exists( 'wpcf7_contact_form' ) ) {
            $cf7_posts = get_posts( array(
                'post_type' => 'wpcf7_contact_form',
                'posts_per_page' => -1,
            ) );
            foreach ( $cf7_posts as $cf7_post ) {
                $cf7_forms[] = array(
                    'id' => $cf7_post->ID,
                    'title' => $cf7_post->post_title,
                );
            }
        }
        
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'baizy_save_mappings' ); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="mapping_type">関連付けタイプ</label>
                    </th>
                    <td>
                        <select name="mapping_type" id="mapping_type" class="regular-text">
                            <option value="cf7">Contact Form 7</option>
                            <option value="page">ページ</option>
                            <option value="custom">カスタム識別子</option>
                        </select>
                        <p class="description">フォーム送信元の種類を選択</p>
                    </td>
                </tr>
                
                <tr id="cf7_field" class="mapping-field">
                    <th scope="row">
                        <label for="cf7_form_id">Contact Form 7</label>
                    </th>
                    <td>
                        <?php if ( ! empty( $cf7_forms ) ) : ?>
                            <select name="cf7_form_id" id="cf7_form_id" class="regular-text">
                                <option value="">選択してください</option>
                                <?php foreach ( $cf7_forms as $form ) : ?>
                                    <option value="<?php echo esc_attr( $form['id'] ); ?>">
                                        <?php echo esc_html( $form['title'] ); ?> (ID: <?php echo esc_html( $form['id'] ); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <p>Contact Form 7がインストールされていないか、フォームが作成されていません。</p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr id="page_field" class="mapping-field" style="display: none;">
                    <th scope="row">
                        <label for="source_page_id">送信元ページ</label>
                    </th>
                    <td>
                        <select name="source_page_id" id="source_page_id" class="regular-text">
                            <option value="">選択してください</option>
                            <?php foreach ( $all_pages as $page ) : ?>
                                <option value="<?php echo esc_attr( $page->ID ); ?>">
                                    <?php echo esc_html( $page->post_title ); ?> (ID: <?php echo esc_html( $page->ID ); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">フォームが設置されているページ</p>
                    </td>
                </tr>
                
                <tr id="custom_field" class="mapping-field" style="display: none;">
                    <th scope="row">
                        <label for="custom_identifier">カスタム識別子</label>
                    </th>
                    <td>
                        <input type="text" name="custom_identifier" id="custom_identifier" class="regular-text" 
                               placeholder="例: my_custom_form">
                        <p class="description">独自のフォーム識別子（JavaScriptから使用する場合など）</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="target_page_id">1回限り表示ページ</label>
                    </th>
                    <td>
                        <?php if ( ! empty( $one_time_pages ) ) : ?>
                            <select name="target_page_id" id="target_page_id" class="regular-text" required>
                                <option value="">選択してください</option>
                                <?php foreach ( $one_time_pages as $page ) : ?>
                                    <option value="<?php echo esc_attr( $page->ID ); ?>">
                                        <?php echo esc_html( $page->post_title ); ?> (ID: <?php echo esc_html( $page->ID ); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">フォーム送信後にリダイレクトする1回限り表示ページ</p>
                        <?php else : ?>
                            <p>まず、ページの編集画面で「1回限り表示ページにする」を有効にしてください。</p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php if ( ! empty( $one_time_pages ) ) : ?>
                <p class="submit">
                    <button type="submit" name="save_mappings" class="button button-primary">
                        関連付けを追加
                    </button>
                </p>
            <?php endif; ?>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            $('#mapping_type').on('change', function() {
                $('.mapping-field').hide();
                var type = $(this).val();
                if (type === 'cf7') {
                    $('#cf7_field').show();
                } else if (type === 'page') {
                    $('#page_field').show();
                } else if (type === 'custom') {
                    $('#custom_field').show();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * 1回限り表示ページ一覧を取得
     */
    private function get_one_time_pages() {
        $args = array(
            'post_type' => array( 'page', 'post' ),
            'posts_per_page' => -1,
            'meta_key' => '_baizy_is_one_time_page',
            'meta_value' => '1',
        );
        
        $query = new WP_Query( $args );
        $pages = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $pages[] = get_post( get_the_ID() );
            }
            wp_reset_postdata();
        }
        
        return $pages;
    }
    
    /**
     * 関連付け設定を保存
     */
    private function save_mappings( $data ) {
        $mappings = get_option( self::OPTION_NAME, array() );
        
        $mapping = array(
            'type' => sanitize_text_field( $data['mapping_type'] ),
            'target_page_id' => intval( $data['target_page_id'] ),
            'created_at' => current_time( 'mysql' ),
        );
        
        if ( $mapping['type'] === 'cf7' && ! empty( $data['cf7_form_id'] ) ) {
            $mapping['source_id'] = intval( $data['cf7_form_id'] );
        } elseif ( $mapping['type'] === 'page' && ! empty( $data['source_page_id'] ) ) {
            $mapping['source_id'] = intval( $data['source_page_id'] );
        } elseif ( $mapping['type'] === 'custom' && ! empty( $data['custom_identifier'] ) ) {
            $mapping['source_id'] = sanitize_text_field( $data['custom_identifier'] );
        } else {
            return;
        }
        
        $mappings[] = $mapping;
        update_option( self::OPTION_NAME, $mappings );
    }
    
    /**
     * 関連付けを削除
     */
    private function delete_mapping( $index ) {
        $mappings = get_option( self::OPTION_NAME, array() );
        if ( isset( $mappings[ $index ] ) ) {
            unset( $mappings[ $index ] );
            $mappings = array_values( $mappings ); // インデックスを詰める
            update_option( self::OPTION_NAME, $mappings );
        }
    }
    
    /**
     * 関連付け一覧を表示
     */
    private function render_mappings_list() {
        $mappings = get_option( self::OPTION_NAME, array() );
        
        if ( empty( $mappings ) ) {
            echo '<p>現在、関連付け設定はありません。</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>タイプ</th>';
        echo '<th>送信元</th>';
        echo '<th>1回限り表示ページ</th>';
        echo '<th>作成日</th>';
        echo '<th>操作</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ( $mappings as $index => $mapping ) {
            $type_label = '';
            $source_label = '';
            
            switch ( $mapping['type'] ) {
                case 'cf7':
                    $type_label = 'Contact Form 7';
                    $form = get_post( $mapping['source_id'] );
                    if ( $form ) {
                        $source_label = $form->post_title . ' (ID: ' . $mapping['source_id'] . ')';
                    } else {
                        $source_label = 'フォームID: ' . $mapping['source_id'] . ' (削除済み)';
                    }
                    break;
                    
                case 'page':
                    $type_label = 'ページ';
                    $page = get_post( $mapping['source_id'] );
                    if ( $page ) {
                        $source_label = $page->post_title . ' (ID: ' . $mapping['source_id'] . ')';
                    } else {
                        $source_label = 'ページID: ' . $mapping['source_id'] . ' (削除済み)';
                    }
                    break;
                    
                case 'custom':
                    $type_label = 'カスタム';
                    $source_label = $mapping['source_id'];
                    break;
            }
            
            $target_page = get_post( $mapping['target_page_id'] );
            $target_label = $target_page ? $target_page->post_title : 'ページID: ' . $mapping['target_page_id'] . ' (削除済み)';
            
            echo '<tr>';
            echo '<td>' . esc_html( $type_label ) . '</td>';
            echo '<td>' . esc_html( $source_label ) . '</td>';
            echo '<td>' . esc_html( $target_label ) . '</td>';
            echo '<td>' . esc_html( $mapping['created_at'] ) . '</td>';
            echo '<td>';
            echo '<form method="post" action="" style="display: inline;">';
            wp_nonce_field( 'baizy_delete_mapping' );
            echo '<input type="hidden" name="mapping_id" value="' . esc_attr( $index ) . '">';
            echo '<button type="submit" name="delete_mapping" class="button button-small" ';
            echo 'onclick="return confirm(\'この関連付けを削除しますか?\');">削除</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Contact Form 7の自動リダイレクト
     */
    public function auto_redirect_cf7() {
        $mappings = get_option( self::OPTION_NAME, array() );
        
        if ( empty( $mappings ) ) {
            return;
        }
        
        // Contact Form 7用のリダイレクト設定を出力
        $cf7_mappings = array_filter( $mappings, function( $mapping ) {
            return $mapping['type'] === 'cf7';
        } );
        
        // ページ指定用のリダイレクト設定
        $page_mappings = array_filter( $mappings, function( $mapping ) {
            return $mapping['type'] === 'page';
        } );
        
        // カスタム識別子用のマッピング
        $custom_mappings = array_filter( $mappings, function( $mapping ) {
            return $mapping['type'] === 'custom';
        } );
        
        if ( empty( $cf7_mappings ) && empty( $page_mappings ) && empty( $custom_mappings ) ) {
            return;
        }
        
        ?>
        <script>
        <?php if ( ! empty( $cf7_mappings ) ) : ?>
        // Contact Form 7自動リダイレクト
        document.addEventListener('wpcf7mailsent', function(event) {
            var formId = event.detail.contactFormId;
            var redirectUrl = null;
            
            <?php foreach ( $cf7_mappings as $mapping ) : ?>
            if (formId === <?php echo intval( $mapping['source_id'] ); ?>) {
                redirectUrl = '<?php echo esc_url( $this->generate_link( $mapping['target_page_id'] ) ); ?>';
            }
            <?php endforeach; ?>
            
            if (redirectUrl) {
                location.href = redirectUrl;
            }
        }, false);
        <?php endif; ?>
        
        <?php if ( ! empty( $custom_mappings ) ) : ?>
        // カスタム識別子用のグローバル関数
        window.baizyOneTimePageRedirect = function(identifier) {
            var redirectUrl = null;
            
            <?php foreach ( $custom_mappings as $mapping ) : ?>
            if (identifier === '<?php echo esc_js( $mapping['source_id'] ); ?>') {
                redirectUrl = '<?php echo esc_url( $this->generate_link( $mapping['target_page_id'] ) ); ?>';
            }
            <?php endforeach; ?>
            
            if (redirectUrl) {
                location.href = redirectUrl;
                return true;
            }
            return false;
        };
        
        // カスタム識別子のリンク取得関数
        window.baizyOneTimePageGetLink = function(identifier) {
            var redirectUrl = null;
            
            <?php foreach ( $custom_mappings as $mapping ) : ?>
            if (identifier === '<?php echo esc_js( $mapping['source_id'] ); ?>') {
                redirectUrl = '<?php echo esc_url( $this->generate_link( $mapping['target_page_id'] ) ); ?>';
            }
            <?php endforeach; ?>
            
            return redirectUrl;
        };
        <?php endif; ?>
        </script>
        <?php
        
        // ページ指定の場合、そのページにいる場合のみスクリプトを出力
        if ( ! empty( $page_mappings ) ) {
            global $post;
            if ( $post ) {
                foreach ( $page_mappings as $mapping ) {
                    if ( $post->ID === intval( $mapping['source_id'] ) ) {
                        $redirect_url = $this->generate_link( $mapping['target_page_id'] );
                        ?>
                        <script>
                        // このページから1回限りページへのリダイレクト用のヘルパー
                        window.baizyOneTimePageRedirectFromThisPage = function() {
                            location.href = '<?php echo esc_url( $redirect_url ); ?>';
                        };
                        
                        // このページ用のリンク取得
                        window.baizyOneTimePageLinkForThisPage = '<?php echo esc_url( $redirect_url ); ?>';
                        </script>
                        <?php
                        break;
                    }
                }
            }
        }
    }
    
    /**
     * 管理画面用スクリプトとスタイルを読み込み
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( $hook !== 'appearance_page_baizy-one-time-page' ) {
            return;
        }
        
        wp_enqueue_script( 'jquery' );
    }
    
    /**
     * 投稿・固定ページ編集画面にメタボックスを追加
     */
    public function add_meta_box() {
        $post_types = array( 'post', 'page' );
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'baizy_one_time_page_meta',
                '1回限り表示ページ設定',
                array( $this, 'render_meta_box' ),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * メタボックスの表示
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'baizy_one_time_page_meta_box', 'baizy_one_time_page_nonce' );
        
        $is_one_time_page = get_post_meta( $post->ID, '_baizy_is_one_time_page', true );
        $redirect_url = get_post_meta( $post->ID, '_baizy_one_time_redirect_url', true );
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="baizy_is_one_time_page" value="1" <?php checked( $is_one_time_page, '1' ); ?>>
                1回限り表示ページにする
            </label>
        </p>
        
        <p>
            <label>
                <strong>リダイレクト先URL:</strong><br>
                <input type="text" name="baizy_one_time_redirect_url" value="<?php echo esc_attr( $redirect_url ); ?>" 
                       style="width: 100%;" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>">
            </label>
            <span class="description">2回目以降のアクセス時にリダイレクトするURL（空欄の場合はトップページ）</span>
        </p>
        
        <?php if ( $is_one_time_page === '1' ) : ?>
        <hr>
        <p><strong>トークン付きリンク:</strong></p>
        <textarea readonly style="width: 100%; height: 60px;" onclick="this.select();"><?php echo esc_url( $this->generate_link( $post->ID ) ); ?></textarea>
        <p class="description">このURLでアクセスすると1回だけページが表示されます</p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * メタボックスの保存
     */
    public function save_meta_box( $post_id, $post ) {
        // 自動保存の場合は何もしない
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // 権限チェック
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // nonceチェック
        if ( ! isset( $_POST['baizy_one_time_page_nonce'] ) || 
             ! wp_verify_nonce( $_POST['baizy_one_time_page_nonce'], 'baizy_one_time_page_meta_box' ) ) {
            return;
        }
        
        // 1回限り表示ページフラグの保存
        if ( isset( $_POST['baizy_is_one_time_page'] ) ) {
            update_post_meta( $post_id, '_baizy_is_one_time_page', '1' );
        } else {
            update_post_meta( $post_id, '_baizy_is_one_time_page', '0' );
        }
        
        // リダイレクトURLの保存
        if ( isset( $_POST['baizy_one_time_redirect_url'] ) ) {
            $redirect_url = esc_url_raw( $_POST['baizy_one_time_redirect_url'] );
            update_post_meta( $post_id, '_baizy_one_time_redirect_url', $redirect_url );
        }
    }
}

/**
 * 1回限りページへのリンクを生成するヘルパー関数
 * 
 * @param int $page_id ページID
 * @return string トークン付きURL
 */
function baizy_one_time_page_link( $page_id ) {
    static $instance = null;
    if ( $instance === null ) {
        $instance = new Baizy_One_Time_Page();
    }
    return $instance->generate_link( $page_id );
}

// 初期化
new Baizy_One_Time_Page();
