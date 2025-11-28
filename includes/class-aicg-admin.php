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
            __('AI Cover Generator Settings', 'ai-cover-generator-for-doubao'),
            __('AI Cover Generator', 'ai-cover-generator-for-doubao'),
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
            __('API Settings', 'ai-cover-generator-for-doubao'),
            array($this, 'render_section_description'),
            'ai-cover-generator'
        );
        
        add_settings_field(
            'aicg_api_key',
            __('API Key', 'ai-cover-generator-for-doubao'),
            array($this, 'render_api_key_field'),
            'ai-cover-generator',
            'aicg_main_section'
        );
        
        add_settings_field(
            'aicg_api_base_url',
            __('API Base URL', 'ai-cover-generator-for-doubao'),
            array($this, 'render_api_base_url_field'),
            'ai-cover-generator',
            'aicg_main_section'
        );
        
        add_settings_field(
            'aicg_text_model',
            __('Text Model', 'ai-cover-generator-for-doubao'),
            array($this, 'render_text_model_field'),
            'ai-cover-generator',
            'aicg_main_section'
        );
        
        add_settings_field(
            'aicg_image_model',
            __('Image Model', 'ai-cover-generator-for-doubao'),
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
            add_settings_error('aicg_messages', 'aicg_message', __('Settings Saved', 'ai-cover-generator-for-doubao'), 'updated');
        }
        
        settings_errors('aicg_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('aicg_settings');
                do_settings_sections('ai-cover-generator');
                submit_button(__('Save Settings', 'ai-cover-generator-for-doubao'));
                ?>
            </form>
            
            <div class="aicg-test-section" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2><?php esc_html_e('API Test', 'ai-cover-generator-for-doubao'); ?></h2>
                <p><?php esc_html_e('Test the functionality of text model (article to prompt) and image model (prompt to image).', 'ai-cover-generator-for-doubao'); ?></p>
                
                <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="aicg-use-client-request" style="margin-right: 8px;">
                        <strong><?php esc_html_e('Use Client-side Request (Recommended)', 'ai-cover-generator-for-doubao'); ?></strong>
                    </label>
                    <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">
                        <?php esc_html_e('If you encounter timeout errors, check this option to call the API directly from the browser, bypassing server-side timeout limits.', 'ai-cover-generator-for-doubao'); ?><br>
                        <strong><?php esc_html_e('Note:', 'ai-cover-generator-for-doubao'); ?></strong> <?php esc_html_e('The API key will be exposed in the browser, use only for testing, do not use in production environment.', 'ai-cover-generator-for-doubao'); ?>
                    </p>
                </div>
                
                <div style="margin-top: 20px;">
                    <h3 style="margin-top: 0;">1. <?php esc_html_e('Test Text Model (Article → Prompt)', 'ai-cover-generator-for-doubao'); ?></h3>
                    <p style="color: #666;"><?php esc_html_e('Test generating image prompts using sample article content.', 'ai-cover-generator-for-doubao'); ?></p>
                    <button type="button" id="aicg-test-text" class="button button-primary" style="margin-top: 10px;">
                        <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span> <?php esc_html_e('Test Text Model', 'ai-cover-generator-for-doubao'); ?>
                    </button>
                    <div id="aicg-test-text-results" style="margin-top: 15px; display: none;">
                        <h4><?php esc_html_e('Test Results', 'ai-cover-generator-for-doubao'); ?></h4>
                        <div id="aicg-test-text-content"></div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #ddd;">
                    <h3 style="margin-top: 0;">2. <?php esc_html_e('Test Image Model (Prompt → Image)', 'ai-cover-generator-for-doubao'); ?></h3>
                    <p style="color: #666;"><?php esc_html_e('Test generating images using prompts. You can enter a custom prompt or use the prompt generated above.', 'ai-cover-generator-for-doubao'); ?></p>
                    <div style="margin-top: 10px;">
                        <label for="aicg-test-prompt-input" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e('Prompt:', 'ai-cover-generator-for-doubao'); ?></label>
                        <textarea id="aicg-test-prompt-input" rows="3" style="width: 100%; max-width: 600px;" placeholder="<?php esc_attr_e('Enter English prompt, e.g.: A beautiful landscape with mountains and a lake, cinematic lighting, vibrant colors', 'ai-cover-generator-for-doubao'); ?>"></textarea>
                        <p class="description" style="margin-top: 5px;"><?php esc_html_e('Leave empty to use default prompt for testing', 'ai-cover-generator-for-doubao'); ?></p>
                    </div>
                    <button type="button" id="aicg-test-image" class="button button-primary" style="margin-top: 10px;">
                        <span class="dashicons dashicons-format-image" style="margin-top: 3px;"></span> <?php esc_html_e('Test Image Model', 'ai-cover-generator-for-doubao'); ?>
                    </button>
                    <div id="aicg-test-image-results" style="margin-top: 15px; display: none;">
                        <h4><?php esc_html_e('Test Results', 'ai-cover-generator-for-doubao'); ?></h4>
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
        echo '<p>' . esc_html__('Configure Doubao AI API information. Please ensure you have obtained a valid API key.', 'ai-cover-generator-for-doubao') . '</p>';
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
               placeholder="<?php esc_attr_e('Enter API Key', 'ai-cover-generator-for-doubao'); ?>" />
        <p class="description"><?php esc_html_e('Doubao AI API Key (Bearer Token)', 'ai-cover-generator-for-doubao'); ?></p>
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
        <p class="description"><?php esc_html_e('API Service Base URL', 'ai-cover-generator-for-doubao'); ?></p>
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
        <p class="description"><?php esc_html_e('Text model used for generating image prompts', 'ai-cover-generator-for-doubao'); ?></p>
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
        <p class="description"><?php esc_html_e('Image model used for generating images', 'ai-cover-generator-for-doubao'); ?></p>
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

