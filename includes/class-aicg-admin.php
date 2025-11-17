<?php
/**
 * 后台管理类
 * 处理插件设置页面
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * 添加后台菜单
     */
    public function add_admin_menu() {
        add_options_page(
            'AI封面生成器设置',
            'AI封面生成器',
            'manage_options',
            'ai-cover-generator',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * 注册设置
     */
    public function register_settings() {
        register_setting('aicg_settings', 'aicg_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('aicg_settings', 'aicg_api_base_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://ark.cn-beijing.volces.com'
        ));
        
        register_setting('aicg_settings', 'aicg_text_model', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'doubao-seed-1-6-251015'
        ));
        
        register_setting('aicg_settings', 'aicg_image_model', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'doubao-seedream-4-0-250828'
        ));
        
        add_settings_section(
            'aicg_main_section',
            'API设置',
            array($this, 'render_section_description'),
            'ai-cover-generator'
        );
        
        add_settings_field(
            'aicg_api_key',
            'API密钥',
            array($this, 'render_api_key_field'),
            'ai-cover-generator',
            'aicg_main_section'
        );
        
        add_settings_field(
            'aicg_api_base_url',
            'API基础URL',
            array($this, 'render_api_base_url_field'),
            'ai-cover-generator',
            'aicg_main_section'
        );
        
        add_settings_field(
            'aicg_text_model',
            '文字模型',
            array($this, 'render_text_model_field'),
            'ai-cover-generator',
            'aicg_main_section'
        );
        
        add_settings_field(
            'aicg_image_model',
            '图像模型',
            array($this, 'render_image_model_field'),
            'ai-cover-generator',
            'aicg_main_section'
        );
    }
    
    /**
     * 渲染设置页面
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by settings_fields()
        if (isset($_GET['settings-updated'])) {
            add_settings_error('aicg_messages', 'aicg_message', '设置已保存', 'updated');
        }
        
        settings_errors('aicg_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('aicg_settings');
                do_settings_sections('ai-cover-generator');
                submit_button('保存设置');
                ?>
            </form>
            
            <div class="aicg-test-section" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2>API测试</h2>
                <p>分别测试文字模型（文章生成提示词）和图像模型（提示词生成图片）的功能。</p>
                
                <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="aicg-use-client-request" style="margin-right: 8px;">
                        <strong>使用客户端直接请求（推荐）</strong>
                    </label>
                    <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">
                        如果遇到超时错误，勾选此选项将直接从浏览器调用 API，绕过服务器端的超时限制。<br>
                        <strong>注意：</strong>API 密钥会暴露在浏览器中，仅用于测试，不要在生产环境使用。
                    </p>
                </div>
                
                <div style="margin-top: 20px;">
                    <h3 style="margin-top: 0;">1. 测试文字模型（文章 → 提示词）</h3>
                    <p style="color: #666;">测试使用示例文章内容生成图像提示词的功能。</p>
                    <button type="button" id="aicg-test-text" class="button button-primary" style="margin-top: 10px;">
                        <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span> 测试文字模型
                    </button>
                    <div id="aicg-test-text-results" style="margin-top: 15px; display: none;">
                        <h4>测试结果</h4>
                        <div id="aicg-test-text-content"></div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #ddd;">
                    <h3 style="margin-top: 0;">2. 测试图像模型（提示词 → 图片）</h3>
                    <p style="color: #666;">测试使用提示词生成图片的功能。可以输入自定义提示词，或使用上方测试生成的提示词。</p>
                    <div style="margin-top: 10px;">
                        <label for="aicg-test-prompt-input" style="display: block; margin-bottom: 5px; font-weight: 600;">提示词：</label>
                        <textarea id="aicg-test-prompt-input" rows="3" style="width: 100%; max-width: 600px;" placeholder="输入英文提示词，例如：A beautiful landscape with mountains and a lake, cinematic lighting, vibrant colors"></textarea>
                        <p class="description" style="margin-top: 5px;">留空将使用默认提示词进行测试</p>
                    </div>
                    <button type="button" id="aicg-test-image" class="button button-primary" style="margin-top: 10px;">
                        <span class="dashicons dashicons-format-image" style="margin-top: 3px;"></span> 测试图像模型
                    </button>
                    <div id="aicg-test-image-results" style="margin-top: 15px; display: none;">
                        <h4>测试结果</h4>
                        <div id="aicg-test-image-content"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 渲染章节描述
     */
    public function render_section_description() {
        echo '<p>配置豆包AI的API信息。请确保已获取有效的API密钥。</p>';
    }
    
    /**
     * 渲染API密钥字段
     */
    public function render_api_key_field() {
        $value = get_option('aicg_api_key', '');
        ?>
        <input type="text" 
               id="aicg_api_key" 
               name="aicg_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               placeholder="请输入API密钥" />
        <p class="description">豆包AI的API密钥（Bearer Token）</p>
        <?php
    }
    
    /**
     * 渲染API基础URL字段
     */
    public function render_api_base_url_field() {
        $value = get_option('aicg_api_base_url', 'https://ark.cn-beijing.volces.com');
        ?>
        <input type="url" 
               id="aicg_api_base_url" 
               name="aicg_api_base_url" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">API服务的基础URL地址</p>
        <?php
    }
    
    /**
     * 渲染文字模型字段
     */
    public function render_text_model_field() {
        $value = get_option('aicg_text_model', 'doubao-seed-1-6-251015');
        ?>
        <input type="text" 
               id="aicg_text_model" 
               name="aicg_text_model" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">用于生成图像提示词的文字模型</p>
        <?php
    }
    
    /**
     * 渲染图像模型字段
     */
    public function render_image_model_field() {
        $value = get_option('aicg_image_model', 'doubao-seedream-4-0-250828');
        ?>
        <input type="text" 
               id="aicg_image_model" 
               name="aicg_image_model" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">用于生成图像的图像模型</p>
        <?php
    }
    
    /**
     * 加载后台脚本和样式
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_ai-cover-generator') {
            return;
        }
        
        wp_enqueue_style(
            'aicg-admin',
            AICG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AICG_VERSION
        );
        
        wp_enqueue_script(
            'aicg-admin',
            AICG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AICG_VERSION,
            true
        );
        
        wp_localize_script('aicg-admin', 'aicgAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicg_test_api'),
            'apiKey' => get_option('aicg_api_key', ''),
            'apiBaseUrl' => get_option('aicg_api_base_url', 'https://ark.cn-beijing.volces.com'),
            'textModel' => get_option('aicg_text_model', 'doubao-seed-1-6-251015'),
            'imageModel' => get_option('aicg_image_model', 'doubao-seedream-4-0-250828')
        ));
    }
}

