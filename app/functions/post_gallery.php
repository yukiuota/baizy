<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// シンプル画像ギャラリー機能（投稿別ギャラリー版）
// ----------------------------------------------------- //

/**
 * 投稿編集画面にギャラリーメタボックスを追加
 */
function add_post_gallery_meta_box() {
    $post_types = array('post'); // 通常の投稿
    
    // カスタム投稿タイプも追加
    $custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
    $post_types = array_merge($post_types, $custom_post_types);
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'post-gallery-metabox',
            '画像ギャラリー',
            'post_gallery_meta_box_callback',
            $post_type,
            'normal',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'add_post_gallery_meta_box');

/**
 * ギャラリーメタボックスのコールバック関数
 */
function post_gallery_meta_box_callback($post) {
    // ナンスフィールドを追加
    wp_nonce_field('save_post_gallery_meta', 'post_gallery_meta_nonce');
    
    // 保存されている画像データを取得
    $gallery_images = get_post_meta($post->ID, '_post_gallery_images', true);
    if (!is_array($gallery_images)) {
        $gallery_images = array();
    }
    
    ?>
<div class="gallery-info">
    <p><strong>現在の画像数:</strong> <?php echo count($gallery_images); ?>枚</p>
    <p><strong>PHP関数での表示:</strong> <code>&lt;?php the_custom_post_gallery(); ?&gt;</code></p>
    <p><strong>ショートコードでの表示:</strong> <code>[post_gallery]</code></p>
</div>

<div id="simple-gallery-container">
    <div class="gallery-controls">
        <button type="button" id="add-gallery-image" class="button button-primary">画像を追加</button>
        <p class="description">画像ボックス全体をドラッグ&ドロップして並び替えることができます。</p>
    </div>

    <div id="gallery-images-list">
        <?php foreach ($gallery_images as $index => $image_data): ?>
        <div class="gallery-image-item" data-index="<?php echo $index; ?>">
            <div class="image-preview">
                <?php if (!empty($image_data['url'])): 
                            $alt_text = '';
                            if (!empty($image_data['id'])) {
                                $alt_text = get_post_meta($image_data['id'], '_wp_attachment_image_alt', true);
                            }
                        ?>
                <img src="<?php echo esc_url($image_data['url']); ?>" alt="<?php echo esc_attr($alt_text); ?>" style="max-width: 150px; height: auto;">
                <?php endif; ?>
            </div>
            <div class="image-fields">
                <input type="hidden" name="gallery_images[<?php echo $index; ?>][id]" value="<?php echo esc_attr($image_data['id']); ?>">
                <input type="hidden" name="gallery_images[<?php echo $index; ?>][url]" value="<?php echo esc_url($image_data['url']); ?>">

                <?php
                        // 画像のサイズ情報を取得
                        if (!empty($image_data['id'])) {
                            $image_meta = wp_get_attachment_metadata($image_data['id']);
                            $file_size = size_format(filesize(get_attached_file($image_data['id'])));
                            $alt_text = get_post_meta($image_data['id'], '_wp_attachment_image_alt', true);
                            
                            if ($image_meta && isset($image_meta['width']) && isset($image_meta['height'])) {
                                echo '<p class="image-info"><strong>サイズ:</strong> ' . 
                                     $image_meta['width'] . ' × ' . $image_meta['height'] . 'px | ' . 
                                     '<strong>ファイルサイズ:</strong> ' . $file_size . '</p>';
                            }
                            
                            // Alt情報を表示
                            if (!empty($alt_text)) {
                                echo '<p class="image-info"><strong>Alt:</strong> ' . esc_html($alt_text) . '</p>';
                            } else {
                                echo '<p class="image-info"><strong>Alt:</strong> <span style="color: #999;">未設定</span></p>';
                            }
                        }
                        ?>

                <button type="button" class="button remove-image">画像を削除</button>
                <p>※ドラッグで並び替えできます。</p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.gallery-info {
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
}

.gallery-info p {
    margin: 5px 0;
}

.gallery-info code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 13px;
}

#simple-gallery-container {
    margin: 20px 0;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.gallery-image-item {
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    background: #f9f9f9;
    display: flex;
    gap: 20px;
    position: relative;
    cursor: move;
    border-radius: 4px;
}

.gallery-image-item:hover {
    background: #f0f0f0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-color: #0073aa;
}

.gallery-image-item::before {
    content: '⋮⋮';
    position: absolute;
    top: 10px;
    right: 10px;
    color: #666;
    font-size: 16px;
    font-weight: bold;
    background: #fff;
    padding: 5px 8px;
    border-radius: 3px;
    border: 1px solid #ddd;
    pointer-events: none;
}

.image-preview {
    flex-shrink: 0;
}

.image-preview img {
    border: 1px solid #ccc;
    border-radius: 4px;
}

.image-fields {
    flex-grow: 1;
}

.image-info {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 8px 12px;
    margin: 10px 0;
    font-size: 13px;
    color: #495057;
}

.image-info strong {
    color: #212529;
}

.gallery-controls {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}

.remove-image {
    color: #a00;
}

.ui-sortable-helper {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transform: rotate(2deg);
}

.ui-state-highlight {
    height: 100px;
    background: #e8f4fd;
    border: 2px dashed #0073aa;
    border-radius: 4px;
    margin-bottom: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    let imageIndex = <?php echo count($gallery_images); ?>;

    // 画像追加ボタンのクリックイベント
    $('#add-gallery-image').click(function() {
        const mediaUploader = wp.media({
            title: '画像を選択',
            button: {
                text: '選択'
            },
            multiple: true
        });

        mediaUploader.on('select', function() {
            const attachments = mediaUploader.state().get('selection').toJSON();

            attachments.forEach(function(attachment) {
                addImageToGallery(attachment);
            });
        });

        mediaUploader.open();
    });

    // 画像をギャラリーに追加する関数
    function addImageToGallery(attachment) {
        // ファイルサイズを人間が読みやすい形式に変換
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // 画像サイズ情報を準備
        const width = attachment.width || 'N/A';
        const height = attachment.height || 'N/A';
        const fileSize = attachment.filesizeInBytes ? formatFileSize(attachment.filesizeInBytes) : 'N/A';
        const altText = attachment.alt || '';

        const imageHtml = `
                <div class="gallery-image-item" data-index="${imageIndex}">
                    <div class="image-preview">
                        <img src="${attachment.url}" alt="${attachment.alt}" style="max-width: 150px; height: auto;">
                    </div>
                    <div class="image-fields">
                        <input type="hidden" name="gallery_images[${imageIndex}][id]" value="${attachment.id}">
                        <input type="hidden" name="gallery_images[${imageIndex}][url]" value="${attachment.url}">
                        
                        <p class="image-info"><strong>サイズ:</strong> ${width} × ${height}px | <strong>ファイルサイズ:</strong> ${fileSize}</p>
                        <p class="image-info"><strong>Alt:</strong> ${altText || '<span style="color: #999;">未設定</span>'}</p>
                        
                        <button type="button" class="button remove-image">画像を削除</button>
                        <p>※ドラッグで並び替えできます。</p>
                    </div>
                </div>
            `;

        $('#gallery-images-list').append(imageHtml);
        imageIndex++;
    }

    // 画像削除ボタンのクリックイベント
    $(document).on('click', '.remove-image', function() {
        if (confirm('この画像を削除しますか？')) {
            $(this).closest('.gallery-image-item').remove();
            updateImageIndexes();
        }
    });

    // インデックスを更新する関数
    function updateImageIndexes() {
        $('#gallery-images-list .gallery-image-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input, textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // ソート機能を有効化
    $('#gallery-images-list').sortable({
        placeholder: 'ui-state-highlight',
        cursor: 'move',
        helper: 'clone',
        tolerance: 'pointer',
        update: function() {
            updateImageIndexes();
        }
    });

    $('#gallery-images-list').disableSelection();
});
</script>
<?php
}

/**
 * 投稿保存時にギャラリーデータを保存
 */
function save_post_gallery_meta($post_id) {
    // ナンスの確認
    if (!isset($_POST['post_gallery_meta_nonce']) || 
        !wp_verify_nonce($_POST['post_gallery_meta_nonce'], 'save_post_gallery_meta')) {
        return;
    }
    
    // 自動保存の場合はスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 権限チェック
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // 画像データの処理
    $gallery_images = array();
    if (isset($_POST['gallery_images']) && is_array($_POST['gallery_images'])) {
        foreach ($_POST['gallery_images'] as $image_data) {
            if (!empty($image_data['id']) && !empty($image_data['url'])) {
                $gallery_images[] = array(
                    'id' => intval($image_data['id']),
                    'url' => esc_url_raw($image_data['url'])
                );
            }
        }
    }
    
    // カスタムフィールドとして保存
    update_post_meta($post_id, '_post_gallery_images', $gallery_images);
}
add_action('save_post', 'save_post_gallery_meta');

/**
 * 管理画面でメディアアップローダーのスクリプトを読み込み
 */
function post_gallery_admin_scripts($hook) {
    global $post_type;
    
    // 投稿編集画面でのみ読み込み
    if (in_array($hook, array('post-new.php', 'post.php'))) {
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
    }
}
add_action('admin_enqueue_scripts', 'post_gallery_admin_scripts');

// ----------------------------------------------------- //
// フロントエンド出力用関数
// ----------------------------------------------------- //

/**
 * 投稿のギャラリー画像データを取得
 * 
 * @param int $post_id 投稿ID（省略時は現在の投稿）
 * @return array 画像データの配列
 */
function get_custom_post_gallery_images($post_id = null) {
    if ($post_id === null) {
        $post_id = get_the_ID();
    }
    
    if (!$post_id) {
        return array();
    }
    
    $gallery_images = get_post_meta($post_id, '_post_gallery_images', true);
    return is_array($gallery_images) ? $gallery_images : array();
}

/**
 * 投稿のギャラリーを表示
 * 
 * @param array $args 表示オプション
 * @param int $post_id 投稿ID（省略時は現在の投稿）
 * @return string HTML出力
 */
function display_custom_post_gallery($args = array(), $post_id = null) {
    $default_args = array(
        'image_size' => 'full',
        'columns' => 3,
        'lightbox' => false,
        'css_class' => 'post-gallery'
    );
    
    $args = wp_parse_args($args, $default_args);
    $gallery_images = get_custom_post_gallery_images($post_id);
    
    if (empty($gallery_images)) {
        return '<p>ギャラリーに画像がありません。</p>';
    }
    
    $output = '<div class="' . esc_attr($args['css_class']) . '" data-columns="' . intval($args['columns']) . '">';
    $output .= '<div class="gallery-grid">';
    
    foreach ($gallery_images as $image) {
        $image_url = $image['url'];
        $image_alt = '';
        
        // WordPress標準の代替テキストを取得
        if (!empty($image['id'])) {
            $image_alt = get_post_meta($image['id'], '_wp_attachment_image_alt', true);
        }
        
        // 指定されたサイズの画像URLを取得
        if ($args['image_size'] !== 'full' && !empty($image['id'])) {
            $image_src = wp_get_attachment_image_src($image['id'], $args['image_size']);
            if ($image_src) {
                $image_url = $image_src[0];
            }
        }
        
        $output .= '<div class="gallery-item">';
        
        if ($args['lightbox']) {
            $output .= '<a href="' . esc_url($image['url']) . '" data-lightbox="post-gallery">';
        }
        
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" loading="lazy">';
        
        if ($args['lightbox']) {
            $output .= '</a>';
        }
        
        $output .= '</div>';
    }
    
    $output .= '</div>'; // .gallery-grid
    $output .= '</div>'; // .post-gallery
    
    return $output;
}

/**
 * 投稿のギャラリーを出力（echo版）
 * 
 * @param array $args 表示オプション
 * @param int $post_id 投稿ID（省略時は現在の投稿）
 */
function the_custom_post_gallery($args = array(), $post_id = null) {
    echo display_custom_post_gallery($args, $post_id);
}

/**
 * 投稿のギャラリー画像数を取得
 * 
 * @param int $post_id 投稿ID（省略時は現在の投稿）
 * @return int 画像数
 */
function get_custom_post_gallery_count($post_id = null) {
    $images = get_custom_post_gallery_images($post_id);
    return count($images);
}

/**
 * 投稿にギャラリー画像があるかチェック
 * 
 * @param int $post_id 投稿ID（省略時は現在の投稿）
 * @return bool 画像があるかどうか
 */
function has_custom_post_gallery($post_id = null) {
    return get_custom_post_gallery_count($post_id) > 0;
}

/**
 * ショートコード: [post_gallery]
 */
function post_gallery_shortcode($atts) {
    $atts = shortcode_atts(array(
        'columns' => 3,
        'image_size' => 'full',
        'lightbox' => false,
        'css_class' => 'post-gallery',
        'post_id' => null
    ), $atts);
    
    // 文字列の true/false を boolean に変換
    $bool_attrs = array('lightbox');
    foreach ($bool_attrs as $attr) {
        $atts[$attr] = ($atts[$attr] === 'true' || $atts[$attr] === '1');
    }
    
    // post_idが指定されていない場合は現在の投稿を使用
    $post_id = $atts['post_id'] ? intval($atts['post_id']) : null;
    unset($atts['post_id']);
    
    return display_custom_post_gallery($atts, $post_id);
}
add_shortcode('post_gallery', 'post_gallery_shortcode');