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
                'generateCover' => '生成AI封面',
                'generating' => '正在生成...',
                'success' => '封面生成成功！',
                'error' => '生成失败，请重试'
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
        echo '<span class="dashicons dashicons-format-image" style="margin-top: 3px;"></span> 生成AI封面';
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
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aicg-generate-cover-btn').on('click', function() {
                var $btn = $(this);
                var originalText = $btn.html();
                
                var postContent = '';
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    postContent = tinymce.get('content').getContent({format: 'text'});
                } else {
                    postContent = $('#content').val();
                }
                
                if (!postContent || postContent.trim() === '') {
                    alert('请先输入文章内容');
                    return;
                }
                
                var postId = $('#post_ID').val();
                var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
                var nonce = '<?php echo esc_js(wp_create_nonce('aicg_generate_cover')); ?>';
                
                // 第一步：生成提示词
                $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> 步骤 1/2：生成提示词...');
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    timeout: 1800000, // 30分钟
                    data: {
                        action: 'aicg_test_text',
                        test_content: postContent,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.prompt) {
                            var prompt = response.data.prompt;
                            
                            // 第二步：生成图片
                            $btn.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> 步骤 2/2：生成图片...');
                            
                            $.ajax({
                                url: ajaxUrl,
                                type: 'POST',
                                timeout: 1800000, // 30分钟
                                data: {
                                    action: 'aicg_generate_image_and_set',
                                    post_id: postId,
                                    prompt: prompt,
                                    nonce: nonce
                                },
                                success: function(response) {
                                    if (response.success) {
                                        alert('✓ 封面生成成功！图片已自动设置为特色图片。页面即将刷新...');
                                        location.reload();
                                    } else {
                                        alert('✗ 生成图片失败：' + (response.data.message || '未知错误'));
                                        $btn.prop('disabled', false).html(originalText);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    var errorMsg = '生成图片失败';
                                    if (status === 'timeout') {
                                        errorMsg += '：请求超时';
                                    } else if (xhr.status === 524) {
                                        errorMsg += '：服务器超时（524错误）';
                                    } else if (xhr.status) {
                                        errorMsg += '：HTTP错误 ' + xhr.status;
                                    }
                                    alert('✗ ' + errorMsg);
                                    $btn.prop('disabled', false).html(originalText);
                                }
                            });
                        } else {
                            alert('✗ 生成提示词失败：' + (response.data.message || '未知错误'));
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = '生成提示词失败';
                        if (status === 'timeout') {
                            errorMsg += '：请求超时';
                        } else if (xhr.status === 524) {
                            errorMsg += '：服务器超时（524错误）';
                        } else if (xhr.status) {
                            errorMsg += '：HTTP错误 ' + xhr.status;
                        }
                        alert('✗ ' + errorMsg);
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * 添加独立的元框
     */
    public function add_meta_box() {
        add_meta_box(
            'aicg-meta-box',
            'AI 封面生成器',
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
            <p>基于文章内容自动生成 AI 封面图片</p>
            <button type="button" id="aicg-meta-generate-btn" class="button button-primary button-large" style="width: 100%; margin-top: 10px;">
                <span class="dashicons dashicons-format-image" style="margin-top: 4px;"></span> 生成 AI 封面
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

