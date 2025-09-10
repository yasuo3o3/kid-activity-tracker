<?php
/*
Plugin Name: Kid QR Grid
Description: Displays QR codes for child PWA access links on parent pages
Version: 1.0.0
License: MIT
*/

if (!defined('ABSPATH')) {
    exit;
}

class KidQRGrid {
    private $shortcode_used = false;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'maybe_enqueue_scripts'));
        add_shortcode('kid_qr_grid', array($this, 'shortcode_handler'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_uninstall_hook(__FILE__, array($this, 'uninstall'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
    }

    public function register_scripts() {
        wp_register_script(
            'kid-qr-grid',
            plugin_dir_url(__FILE__) . 'assets/qr-grid.js',
            array(),
            '1.0.0',
            true
        );

        wp_register_style(
            'kid-qr-grid',
            plugin_dir_url(__FILE__) . 'assets/qr-grid.css',
            array(),
            '1.0.0'
        );
    }

    public function maybe_enqueue_scripts() {
        if ($this->shortcode_used) {
            wp_enqueue_style('kid-qr-grid');
            wp_enqueue_script('kid-qr-grid');
        }
    }

    public function shortcode_handler($atts) {
        $this->shortcode_used = true;
        
        $api_url = get_option('kid_qr_grid_api_url', '');
        $pwa_base = get_option('kid_qr_grid_pwa_base', '');

        if (empty($api_url) || empty($pwa_base)) {
            return '<div class="kid-qr-grid-notice">QRコード表示には設定が必要です。管理画面から設定を行ってください。</div>';
        }

        $kids = $this->fetch_kids($api_url);
        
        if (is_wp_error($kids)) {
            return '<div class="kid-qr-grid-notice">子ども情報の取得に失敗しました。しばらく後にお試しください。</div>';
        }

        if (empty($kids)) {
            return '<div class="kid-qr-grid-notice">登録されている子どもが見つかりませんでした。</div>';
        }

        $kids = apply_filters('kid_qr_grid_kids', $kids);

        $output = '<div class="kid-qr-grid" id="kid-qr-grid-' . uniqid() . '">';
        
        foreach ($kids as $kid) {
            if (!isset($kid['id']) || !isset($kid['display_name'])) {
                continue;
            }
            
            $kid_id = sanitize_text_field($kid['id']);
            $display_name = sanitize_text_field($kid['display_name']);
            $pwa_url = esc_url(rtrim($pwa_base, '/') . '/?kid_id=' . $kid_id);
            
            $output .= '<div class="kid-qr-card">';
            $output .= '<div class="kid-name">' . esc_html($display_name) . '</div>';
            $output .= '<div class="qr-canvas" data-url="' . esc_attr($pwa_url) . '" data-size="180"></div>';
            $output .= '<button class="qr-copy" data-copy="' . esc_attr($pwa_url) . '" type="button" aria-label="' . esc_attr($display_name) . 'のリンクをコピー">リンクをコピー</button>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }

    private function fetch_kids($api_url) {
        $response = wp_remote_get(esc_url_raw($api_url), array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            return new WP_Error('api_error', 'API returned status code: ' . $status_code);
        }

        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Invalid JSON response');
        }

        return $data;
    }

    public function add_admin_menu() {
        add_options_page(
            'Kid QR Grid Settings',
            'Kid QR Grid',
            'manage_options',
            'kid-qr-grid',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        include_once plugin_dir_path(__FILE__) . 'inc/admin-page.php';
    }

    public function activate() {
        if (!get_option('kid_qr_grid_api_url')) {
            add_option('kid_qr_grid_api_url', '');
        }
        if (!get_option('kid_qr_grid_pwa_base')) {
            add_option('kid_qr_grid_pwa_base', '');
        }
    }

    public static function uninstall() {
        delete_option('kid_qr_grid_api_url');
        delete_option('kid_qr_grid_pwa_base');
    }
}

new KidQRGrid();