<?php
/**
 * Plugin Name: AI Cover Generator for Doubao
 * Plugin URI: https://github.com/jiayuqi7813/wordpress-ai-cover-generator
 * Description: Automatically generate beautiful AI-powered cover images for WordPress posts using Doubao AI
 * Version: 1.0.1
 * Author: jiayuqi
 * Author URI: https://www.snowywar.top
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-cover-generator-for-doubao
 * Domain Path: /languages
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('AICG_VERSION', '1.0.0');
define('AICG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICG_PLUGIN_URL', plugin_dir_url(__FILE__));

// 引入必要的文件
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-api.php';
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-admin.php';
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-editor.php';
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-ajax.php';

// 初始化插件
class AICG_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // 激活和停用插件时的钩子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 初始化各个组件
        add_action('plugins_loaded', array($this, 'load_components'));
    }
    
    public function activate() {
        // 设置默认选项
        if (!get_option('aicg_api_key')) {
            add_option('aicg_api_key', '');
        }
        if (!get_option('aicg_api_base_url')) {
            add_option('aicg_api_base_url', 'https://ark.cn-beijing.volces.com');
        }
        if (!get_option('aicg_text_model')) {
            add_option('aicg_text_model', 'doubao-seed-1-6-251015');
        }
        if (!get_option('aicg_image_model')) {
            add_option('aicg_image_model', 'doubao-seedream-4-0-250828');
        }
    }
    
    public function deactivate() {
        // 清理工作（可选）
    }
    
    public function load_components() {
        // 初始化后台管理
        if (is_admin()) {
            new AICG_Admin();
        }
        
        // 初始化编辑器功能
        new AICG_Editor();
        
        // 初始化AJAX处理
        new AICG_Ajax();
    }
}

// 启动插件
AICG_Plugin::get_instance();

