<?php
/**
 * 编辑器集成类
 * 在WordPress编辑器中添加生成封面按钮
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_Editor {
    
    public function __construct() {
        // 为Gutenberg编辑器添加按钮
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        
        // 为经典编辑器添加按钮
        add_action('media_buttons', array($this, 'add_classic_editor_button'), 15);
        add_action('admin_footer', array($this, 'add_classic_editor_script'));
        
        // 添加独立的元框（Meta Box），不会被主题覆盖
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_meta_box_scripts'));
    }
    
    /**
     * 加载Gutenberg编辑器资源
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'aicg-block-editor',
            AICG_PLUGIN_URL . 'assets/js/block-editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-plugins', 'wp-edit-post', 'wp-data'),
            AICG_VERSION,
            true
        );
        
        wp_enqueue_style(
            'aicg-block-editor',
            AICG_PLUGIN_URL . 'assets/css/block-editor.css',
            array(),
            AICG_VERSION
        );
        
        // 传递数据到JavaScript
        wp_localize_script('aicg-block-editor', 'aicgData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicg_generate_cover'),
            'strings' => array(
                'generateCover' => __('Generate AI Cover', 'ai-cover-generator-for-doubao'),
                'generating' => __('Generating...', 'ai-cover-generator-for-doubao'),
                'success' => __('Cover generated successfully!', 'ai-cover-generator-for-doubao'),
                'error' => __('Generation failed, please try again', 'ai-cover-generator-for-doubao')
            )
        ));
    }
    
    /**
     * 为经典编辑器添加按钮
     */
    public function add_classic_editor_button($editor_id) {
        if ($editor_id !== 'content') {
            return;
        }
        
        echo '<button type="button" id="aicg-generate-cover-btn" class="button" style="margin-left: 5px;">';
        echo '<span class="dashicons dashicons-format-image" style="margin-top: 3px;"></span> ' . esc_html__('Generate AI Cover', 'ai-cover-generator-for-doubao');
        echo '</button>';
    }
    
    /**
     * 为经典编辑器添加脚本
     */
    public function add_classic_editor_script() {
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'post') {
            return;
        }
        
        wp_enqueue_script(
            'aicg-classic-editor',
            AICG_PLUGIN_URL . 'assets/js/classic-editor.js',
            array('jquery'),
            AICG_VERSION,
            true
        );
        
        wp_localize_script('aicg-classic-editor', 'aicgClassicEditor', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicg_generate_cover'),
            'strings' => array(
                'enterContent' => __('Please enter post content first', 'ai-cover-generator-for-doubao'),
                'step1' => __('Step 1/2: Generating prompt...', 'ai-cover-generator-for-doubao'),
                'step2' => __('Step 2/2: Generating image...', 'ai-cover-generator-for-doubao'),
                'success' => __('✓ Cover generated successfully! Image set as featured image. Reloading...', 'ai-cover-generator-for-doubao'),
                'failImage' => __('✗ Failed to generate image: ', 'ai-cover-generator-for-doubao'),
                'failPrompt' => __('✗ Failed to generate prompt: ', 'ai-cover-generator-for-doubao'),
                'unknownError' => __('Unknown error', 'ai-cover-generator-for-doubao'),
                'timeout' => __('Request timeout', 'ai-cover-generator-for-doubao'),
                'serverTimeout' => __('Server timeout (524 error)', 'ai-cover-generator-for-doubao'),
                'httpError' => __('HTTP Error ', 'ai-cover-generator-for-doubao'),
                'failGenImage' => __('Failed to generate image', 'ai-cover-generator-for-doubao'),
                'failGenPrompt' => __('Failed to generate prompt', 'ai-cover-generator-for-doubao')
            )
        ));
    }
    
    /**
     * 添加独立的元框
     */
    public function add_meta_box() {
        add_meta_box(
            'aicg-meta-box',
            __('AI Cover Generator', 'ai-cover-generator-for-doubao'),
            array($this, 'render_meta_box'),
            'post',
            'side',
            'high'
        );
    }
    
    /**
     * 渲染元框内容
     */
    public function render_meta_box($post) {
        wp_nonce_field('aicg_meta_box', 'aicg_meta_box_nonce');
        ?>
        <div id="aicg-meta-box-container">
            <p><?php esc_html_e('Automatically generate AI cover image based on post content', 'ai-cover-generator-for-doubao'); ?></p>
            <button type="button" id="aicg-meta-generate-btn" class="button button-primary button-large" style="width: 100%; margin-top: 10px;">
                <span class="dashicons dashicons-format-image" style="margin-top: 4px;"></span> <?php esc_html_e('Generate AI Cover', 'ai-cover-generator-for-doubao'); ?>
            </button>
            <div id="aicg-meta-status" style="margin-top: 10px; display: none;"></div>
        </div>
        <?php
    }
    
    /**
     * 加载元框所需的脚本
     */
    public function enqueue_meta_box_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        wp_enqueue_script(
            'aicg-meta-box',
            AICG_PLUGIN_URL . 'assets/js/meta-box.js',
            array('jquery'),
            AICG_VERSION,
            true
        );
        
        wp_localize_script('aicg-meta-box', 'aicgMetaBox', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicg_generate_cover'),
            'postId' => get_the_ID()
        ));
        
        wp_enqueue_style(
            'aicg-meta-box',
            AICG_PLUGIN_URL . 'assets/css/meta-box.css',
            array(),
            AICG_VERSION
        );
    }
}

