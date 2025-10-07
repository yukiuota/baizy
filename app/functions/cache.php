<?php
if (!defined('ABSPATH')) exit;

/**
 * ======================================================
 * キャッシュ最適化
 * ======================================================
 */

/**
 * -------------------------------
 * ブラウザキャッシュ制御
 * -------------------------------
 */
add_filter('wp_headers', function($headers) {
    if (is_admin()) return $headers;
    
    // 動的コンテンツの判定
    $is_dynamic = (
        is_singular(['news', 'blog']) ||
        is_search() ||
        is_author() ||
        is_date() ||
        is_404() ||
        is_page(['contact', 'form', 'inquiry']) ||
        (function_exists('wpcf7_get_current_contact_form') && wpcf7_get_current_contact_form())
    );
    
    if ($is_dynamic) {
        $headers['Cache-Control'] = 'no-cache, must-revalidate, no-store';
        $headers['Pragma'] = 'no-cache';
        $headers['Expires'] = '0';
    } else {
        // 静的コンテンツは1日キャッシュ
        $headers['Cache-Control'] = 'public, max-age=86400';
        $headers['Expires'] = gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT';
    }
    
    return $headers;
});

/**
 * -------------------------------
 * オブジェクトキャッシュクラス
 * -------------------------------
 */
class Optimized_Object_Cache {
    private $cache_group = 'optimized_cache';
    private $cache_expiration = 3600;
    private $memory_cache = [];
    private $no_cache_conditions = [
        'contact', 'form', 'inquiry', 'mail',
        'login', 'register', 'account', 
        'checkout', 'cart', 'payment'
    ];

    public function __construct() {
        add_filter('posts_pre_query', [$this, 'serve_cached_posts'], 10, 2);
        add_action('the_posts', [$this, 'store_cache_posts'], 10, 2);
        add_action('save_post', [$this, 'clear_related_cache']);
        add_action('delete_post', [$this, 'clear_related_cache']);
        add_action('transition_post_status', [$this, 'clear_related_cache']);
    }

    /**
     * キャッシュ取得
     */
    public function serve_cached_posts($posts, $query) {
        if (is_admin() || !$query->is_main_query() || $this->should_skip_cache()) {
            return $posts;
        }

        $cache_key = $this->generate_cache_key($query);
        
        // メモリキャッシュを優先
        if (isset($this->memory_cache[$cache_key])) {
            return $this->memory_cache[$cache_key];
        }

        // オブジェクトキャッシュから取得
        $cached_data = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached_data !== false && is_array($cached_data)) {
            try {
                // シリアライズされた完全なWP_Postオブジェクトを復元
                $restored_posts = array_map(function($post_data) {
                    if (!$post_data) return null;
                    
                    // WP_Postオブジェクトとして復元
                    $post = new WP_Post((object)$post_data);
                    
                    // 投稿が削除されていないか確認
                    if (!$post || $post->post_status === 'trash') {
                        return null;
                    }
                    
                    return $post;
                }, $cached_data);
                
                // nullを除去し、数値添字をリセットして順序を安定化
                $posts = array_values(array_filter($restored_posts));
                
                // メモリキャッシュに保存
                $this->memory_cache[$cache_key] = $posts;
                return $posts;
                
            } catch (Exception $e) {
                error_log('キャッシュ復元エラー: ' . $e->getMessage());
                // エラー時はキャッシュを削除
                wp_cache_delete($cache_key, $this->cache_group);
            }
        }

        return $posts;
    }

    /**
     * キャッシュ保存
     */
    public function store_cache_posts($posts, $query) {
        if (is_admin() || !$query->is_main_query() || $this->should_skip_cache() || empty($posts)) {
            return $posts;
        }

        $cache_key = $this->generate_cache_key($query);

        try {
            // WP_Postオブジェクトを配列に変換して保存
            $cache_data = array_map(function($post) {
                return is_object($post) ? get_object_vars($post) : $post;
            }, $posts);

            // オブジェクトキャッシュに保存
            wp_cache_set($cache_key, $cache_data, $this->cache_group, $this->cache_expiration);
            
            // メモリキャッシュにも保存
            $this->memory_cache[$cache_key] = $posts;
            
            // キャッシュキーを軽量に追跡（TTLベース）
            $this->track_cache_key($cache_key);
            
        } catch (Exception $e) {
            error_log('キャッシュ保存エラー: ' . $e->getMessage());
        }

        return $posts;
    }

    /**
     * キャッシュ除外判定
     */
    private function should_skip_cache() {
        if (is_user_logged_in() || $_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET)) {
            return true;
        }

        global $post;
        if ($post && $post->post_name) {
            foreach ($this->no_cache_conditions as $cond) {
                if (strpos($post->post_name, $cond) !== false) return true;
            }
        }

        if (function_exists('wpcf7_get_current_contact_form') && wpcf7_get_current_contact_form()) {
            return true;
        }

        return apply_filters('nextgen_skip_cache', false);
    }

    /**
     * キャッシュキー生成（安定化）
     */
    private function generate_cache_key($query) {
        $key_data = [
            'is_home' => $query->is_home(),
            'is_archive' => $query->is_archive(),
            'is_search' => $query->is_search(),
            'post_type' => $query->get('post_type'),
            'posts_per_page' => $query->get('posts_per_page'),
            'paged' => $query->get('paged'),
            's' => $query->get('s'),
            'tax_query' => $this->normalize_query_param($query->get('tax_query')),
            'meta_query' => $this->normalize_query_param($query->get('meta_query')),
            'author' => $query->get('author'),
            'date_query' => $this->normalize_query_param($query->get('date_query')),
            'category_name' => $query->get('category_name'),
            'tag' => $query->get('tag'),
        ];
        
        // 配列をソートして安定したキーを生成
        ksort($key_data);
        return 'query_' . hash('sha256', serialize($key_data));
    }

    /**
     * クエリパラメータの正規化
     */
    private function normalize_query_param($param) {
        if (is_array($param)) {
            array_walk_recursive($param, function(&$item) {
                if (is_array($item)) ksort($item);
            });
            ksort($param);
        }
        return $param;
    }

    /**
     * 軽量なキー追跡（TTLベース）
     */
    private function track_cache_key($cache_key) {
        $current_keys = wp_cache_get('cache_keys_list', $this->cache_group);
        if (!is_array($current_keys)) $current_keys = [];
        
        $current_keys[$cache_key] = time();
        
        // 古いキー（2時間以上）を削除
        $current_keys = array_filter($current_keys, function($timestamp) {
            return (time() - $timestamp) < 7200;
        });
        
        wp_cache_set('cache_keys_list', $current_keys, $this->cache_group, 7200);
    }

    /**
     * 効率的な関連キャッシュクリア
     */
    public function clear_related_cache($post_id = null) {
        // 特定の投稿に関連するキャッシュのみクリア
        $post = $post_id ? get_post($post_id) : null;
        
        $keys_to_clear = wp_cache_get('cache_keys_list', $this->cache_group);
        if (!is_array($keys_to_clear)) return;
        
        foreach ($keys_to_clear as $key => $timestamp) {
            $should_clear = false;
            
            // ホーム・アーカイブページは常にクリア
            if (strpos($key, 'is_home') !== false || 
                strpos($key, 'is_archive') !== false) {
                $should_clear = true;
            }
            
            // 特定の投稿タイプに関連するキャッシュをクリア
            if ($post && strpos($key, $post->post_type) !== false) {
                $should_clear = true;
            }
            
            if ($should_clear) {
                wp_cache_delete($key, $this->cache_group);
                unset($keys_to_clear[$key]);
            }
        }
        
        // 更新されたキーリストを保存
        wp_cache_set('cache_keys_list', $keys_to_clear, $this->cache_group, 7200);
        
        // メモリキャッシュもクリア
        $this->memory_cache = [];
    }

    /**
     * キャッシュ除外条件追加
     */
    public function add_no_cache_condition($condition) {
        if (!in_array($condition, $this->no_cache_conditions)) {
            $this->no_cache_conditions[] = $condition;
        }
    }

    /**
     * キャッシュ統計情報取得（デバッグ用）
     */
    public function get_cache_stats() {
        $keys = wp_cache_get('cache_keys_list', $this->cache_group);
        return [
            'total_keys' => is_array($keys) ? count($keys) : 0,
            'memory_cached' => count($this->memory_cache),
            'cache_group' => $this->cache_group,
        ];
    }
}

// インスタンス生成
global $optimized_cache;
$optimized_cache = new Optimized_Object_Cache();

/**
 * ヘルパー関数
 */
function add_no_cache_conditions($conditions) {
    global $optimized_cache;
    if (!$optimized_cache) return;
    if (is_string($conditions)) $conditions = [$conditions];
    foreach ($conditions as $c) {
        $optimized_cache->add_no_cache_condition($c);
    }
}

function get_cache_stats() {
    global $optimized_cache;
    return $optimized_cache ? $optimized_cache->get_cache_stats() : [];
}

/**
 * -------------------------------
 * ファイル監視クラス
 * -------------------------------
 */
class Improved_File_Watcher {
    private $theme_path;
    private $cache_key = 'theme_files_hash';
    private $watch_dirs = [];

    public function __construct() {
        $this->theme_path = get_template_directory();
        $this->watch_dirs = [
            $this->theme_path . '/app/functions',
            $this->theme_path . '/public/common',
            $this->theme_path . '/public/pages',
            $this->theme_path
        ];
        
        // 管理画面とフロント両方で監視
        add_action('init', [$this, 'check_theme_files']);
        
        // 管理画面では定期的にチェック
        if (is_admin()) {
            add_action('admin_init', [$this, 'check_theme_files']);
        }
    }

    public function check_theme_files() {
        // 負荷軽減：10回に1回のみ実行
        if (rand(1, 10) !== 1) return;
        
        $current_hash = $this->get_recursive_files_hash();
        $stored_hash = get_option($this->cache_key);

        if ($stored_hash && $stored_hash !== $current_hash) {
            // キャッシュクリア
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            // 特定のキャッシュグループのみクリア
            global $optimized_cache;
            if ($optimized_cache) {
                $optimized_cache->clear_related_cache();
            }
            
            update_option($this->cache_key, $current_hash);
            
            // 管理画面でのみ通知表示
            if (is_admin()) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-info is-dismissible"><p>テーマファイル変更によりキャッシュをクリアしました。</p></div>';
                });
            }
            
            // ログに記録（フロント側でも確認可能）
            error_log('テーマファイル変更検知：キャッシュクリア実行');
        } elseif (!$stored_hash) {
            update_option($this->cache_key, $current_hash);
        }
    }
    /**
     * 再帰的ファイル監視（RecursiveDirectoryIterator使用）
     */
    private function get_recursive_files_hash() {
        $important_files = [
            $this->theme_path . '/style.css',
            $this->theme_path . '/functions.php',
        ];
        
        // ディレクトリ内のファイルを再帰的に取得
        foreach ($this->watch_dirs as $dir) {
            if (!is_dir($dir)) continue;
            
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($iterator as $file) {
                    $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                    if (in_array($ext, ['php', 'css', 'js']) && 
                        !$this->is_excluded_file($file->getPathname())) {
                        $important_files[] = $file->getPathname();
                    }
                }
            } catch (Exception $e) {
                error_log('ファイル監視エラー: ' . $e->getMessage());
                continue;
            }
        }

        // ハッシュ生成（ファイル数制限で負荷軽減）
        $important_files = array_unique($important_files);
        if (count($important_files) > 100) {
            // ファイル数が多い場合は重要度順にソートして上位100件のみ
            usort($important_files, function($a, $b) {
                $priority_a = $this->get_file_priority($a);
                $priority_b = $this->get_file_priority($b);
                return $priority_b - $priority_a;
            });
            $important_files = array_slice($important_files, 0, 100);
        }
        
        $hash_data = [];
        foreach ($important_files as $file) {
            if (file_exists($file)) {
                $hash_data[] = $file . ':' . filemtime($file);
            }
        }
        
        return hash('sha256', implode('|', $hash_data));
    }

    /**
     * ファイル優先度取得（重要なファイルを優先監視）
     */
    private function get_file_priority($filepath) {
        $filename = basename($filepath);
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        
        // 優先度設定
        if (strpos($filepath, '/functions/') !== false && $ext === 'php') return 10;
        if (in_array($filename, ['style.css', 'functions.php'])) return 9;
        if (strpos($filepath, '/common/') !== false) return 8;
        if ($ext === 'php') return 7;
        if ($ext === 'css') return 6;
        if ($ext === 'js') return 5;
        
        return 1;
    }

    /**
     * 監視除外ファイル判定
     */
    private function is_excluded_file($filepath) {
        $excluded_patterns = [
            '/node_modules/',
            '/vendor/',
            '/.git/',
            '/build/',
            '/dist/',
            '.min.js',
            '.min.css'
        ];
        
        foreach ($excluded_patterns as $pattern) {
            if (strpos($filepath, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

// ファイル監視開始
new Improved_File_Watcher();