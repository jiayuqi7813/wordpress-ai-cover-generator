<?php
/**
 * AJAX处理类
 * 处理前端AJAX请求
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_aicg_generate_cover', array($this, 'handle_generate_cover'));
        add_action('wp_ajax_aicg_test_api', array($this, 'handle_test_api'));
        add_action('wp_ajax_aicg_test_text', array($this, 'handle_test_text'));
        add_action('wp_ajax_aicg_test_image', array($this, 'handle_test_image'));
        add_action('wp_ajax_aicg_generate_image_and_set', array($this, 'handle_generate_image_and_set'));
    }
    
    /**
     * 处理生成封面请求
     */
    public function handle_generate_cover() {
        // 增加PHP执行时间限制，避免超时
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @set_time_limit(1800); // 30分钟
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('max_execution_time', 1800);
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('default_socket_timeout', 600);
        
        // 验证nonce
        check_ajax_referer('aicg_generate_cover', 'nonce');
        
        // 检查权限
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '权限不足'));
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
        
        if (empty($content)) {
            wp_send_json_error(array('message' => '文章内容为空'));
            return;
        }
        
        // 初始化API类
        $api = new AICG_API();
        
        // 第一步：生成prompt
        $prompt_result = $api->generate_prompt($content);
        
        if (is_wp_error($prompt_result)) {
            wp_send_json_error(array(
                'message' => '生成提示词失败：' . $prompt_result->get_error_message()
            ));
            return;
        }
        
        $prompt = $prompt_result['prompt'];
        
        // 第二步：生成图像
        $image_result = $api->generate_image($prompt);
        
        if (is_wp_error($image_result)) {
            wp_send_json_error(array(
                'message' => '生成图像失败：' . $image_result->get_error_message()
            ));
            return;
        }
        
        $image_url = $image_result['image_url'];
        
        // 下载图片并设置为特色图片（如果提供了post_id）
        if ($post_id > 0) {
            $attachment_id = $this->download_image($image_url, $post_id);
            if ($attachment_id && !is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
        
        wp_send_json_success(array(
            'prompt' => $prompt,
            'image_url' => $image_url,
            'attachment_id' => isset($attachment_id) ? $attachment_id : null
        ));
    }
    
    /**
     * 下载远程图片并创建附件
     * 
     * @param string $url 图片URL
     * @param int $post_id 关联的文章ID
     * @return int|WP_Error 附件ID或错误
     */
    private function download_image($url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // 下载文件
        $tmp = download_url($url);
        
        if (is_wp_error($tmp)) {
            return $tmp;
        }
        
        // 准备文件数组
        $file_array = array(
            'name' => 'ai-cover-' . time() . '.jpg',
            'tmp_name' => $tmp
        );
        
        // 上传文件
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        // 清理临时文件
        wp_delete_file($tmp);
        
        return $attachment_id;
    }
    
    /**
     * 处理API测试请求
     */
    public function handle_test_api() {
        // 增加PHP执行时间限制，避免超时
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @set_time_limit(1800); // 30分钟
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('max_execution_time', 1800);
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('default_socket_timeout', 600);
        
        // 验证nonce
        check_ajax_referer('aicg_test_api', 'nonce');
        
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '权限不足'));
            return;
        }
        
        $test_content = isset($_POST['test_content']) ? sanitize_textarea_field(wp_unslash($_POST['test_content'])) : '';
        
        if (empty($test_content)) {
            $test_content = '这是一篇关于科技发展的文章。人工智能正在改变我们的生活方式，从智能手机到自动驾驶汽车，科技的力量无处不在。未来，我们将看到更多创新的技术应用，让生活变得更加便捷和智能。';
        }
        
        // 初始化API类
        $api = new AICG_API();
        
        $results = array(
            'text_test' => array('success' => false),
            'image_test' => array('success' => false)
        );
        
        // 第一步：测试文字模型
        $prompt_result = $api->generate_prompt($test_content);
        
        if (is_wp_error($prompt_result)) {
            $error_data = $prompt_result->get_error_data();
            $error_message = $prompt_result->get_error_message();
            
            // 添加更详细的错误信息
            if (is_array($error_data)) {
                if (isset($error_data['status_code'])) {
                    $error_message .= ' (HTTP ' . $error_data['status_code'] . ')';
                }
                if (isset($error_data['response_body'])) {
                    $response_body = $error_data['response_body'];
                    $error_data_parsed = json_decode($response_body, true);
                    if ($error_data_parsed && isset($error_data_parsed['error'])) {
                        if (is_array($error_data_parsed['error']) && isset($error_data_parsed['error']['message'])) {
                            $error_message .= ' - ' . $error_data_parsed['error']['message'];
                        }
                    }
                }
            }
            
            $results['text_test'] = array(
                'success' => false,
                'message' => $error_message,
                'details' => is_array($error_data) ? $error_data : null
            );
        } else {
            $results['text_test'] = array(
                'success' => true,
                'prompt' => $prompt_result['prompt']
            );
            
            // 第二步：测试图像模型（使用生成的prompt）
            $image_result = $api->generate_image($prompt_result['prompt']);
            
            if (is_wp_error($image_result)) {
                $error_data = $image_result->get_error_data();
                $error_message = $image_result->get_error_message();
                
                // 添加更详细的错误信息
                if (is_array($error_data)) {
                    if (isset($error_data['status_code'])) {
                        $error_message .= ' (HTTP ' . $error_data['status_code'] . ')';
                    }
                    if (isset($error_data['response_body'])) {
                        $response_body = $error_data['response_body'];
                        $error_data_parsed = json_decode($response_body, true);
                        if ($error_data_parsed && isset($error_data_parsed['error'])) {
                            if (is_array($error_data_parsed['error']) && isset($error_data_parsed['error']['message'])) {
                                $error_message .= ' - ' . $error_data_parsed['error']['message'];
                            }
                        }
                    }
                }
                
                $results['image_test'] = array(
                    'success' => false,
                    'message' => $error_message,
                    'details' => is_array($error_data) ? $error_data : null
                );
            } else {
                $results['image_test'] = array(
                    'success' => true,
                    'image_url' => $image_result['image_url']
                );
            }
        }
        
        // 如果文字模型测试失败，也尝试用默认prompt测试图像模型
        if (!$results['text_test']['success'] && !$results['image_test']['success']) {
            $default_prompt = 'A beautiful landscape with mountains and a lake, cinematic lighting, vibrant colors, high quality, detailed';
            $image_result = $api->generate_image($default_prompt);
            
            if (!is_wp_error($image_result)) {
                $results['image_test'] = array(
                    'success' => true,
                    'image_url' => $image_result['image_url'],
                    'note' => '使用默认提示词测试'
                );
            } else {
                // 如果默认prompt也失败，记录错误
                $error_data = $image_result->get_error_data();
                $error_message = $image_result->get_error_message();
                
                if (is_array($error_data)) {
                    if (isset($error_data['status_code'])) {
                        $error_message .= ' (HTTP ' . $error_data['status_code'] . ')';
                    }
                    if (isset($error_data['response_body'])) {
                        $response_body = $error_data['response_body'];
                        $error_data_parsed = json_decode($response_body, true);
                        if ($error_data_parsed && isset($error_data_parsed['error'])) {
                            if (is_array($error_data_parsed['error']) && isset($error_data_parsed['error']['message'])) {
                                $error_message .= ' - ' . $error_data_parsed['error']['message'];
                            }
                        }
                    }
                }
                
                $results['image_test'] = array(
                    'success' => false,
                    'message' => $error_message,
                    'details' => is_array($error_data) ? $error_data : null
                );
            }
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * 处理文字模型测试请求（文章生成提示词）
     */
    public function handle_test_text() {
        // 增加PHP执行时间限制，避免超时
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @set_time_limit(1800); // 30分钟
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('max_execution_time', 1800);
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('default_socket_timeout', 600);
        
        // 验证nonce - 支持两种来源：设置页面测试 和 编辑器生成
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $nonce_valid = false;
        
        // 尝试验证设置页面的 nonce
        if (wp_verify_nonce($nonce, 'aicg_test_api')) {
            $nonce_valid = true;
        }
        // 尝试验证编辑器的 nonce
        if (!$nonce_valid && wp_verify_nonce($nonce, 'aicg_generate_cover')) {
            $nonce_valid = true;
        }
        
        if (!$nonce_valid) {
            wp_send_json_error(array('message' => 'Nonce 验证失败'));
            return;
        }
        
        // 检查权限 - 编辑器用户只需要 edit_posts 权限
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '权限不足'));
            return;
        }
        
        $test_content = isset($_POST['test_content']) ? sanitize_textarea_field(wp_unslash($_POST['test_content'])) : '';
        
        if (empty($test_content)) {
            $test_content = '这是一篇关于科技发展的文章。人工智能正在改变我们的生活方式，从智能手机到自动驾驶汽车，科技的力量无处不在。未来，我们将看到更多创新的技术应用，让生活变得更加便捷和智能。';
        }
        
        // 初始化API类
        $api = new AICG_API();
        
        // 测试文字模型
        $prompt_result = $api->generate_prompt($test_content);
        
        if (is_wp_error($prompt_result)) {
            $error_data = $prompt_result->get_error_data();
            $error_message = $prompt_result->get_error_message();
            
            // 添加更详细的错误信息
            if (is_array($error_data)) {
                if (isset($error_data['status_code'])) {
                    $error_message .= ' (HTTP ' . $error_data['status_code'] . ')';
                }
                if (isset($error_data['response_body'])) {
                    $response_body = $error_data['response_body'];
                    $error_data_parsed = json_decode($response_body, true);
                    if ($error_data_parsed && isset($error_data_parsed['error'])) {
                        if (is_array($error_data_parsed['error']) && isset($error_data_parsed['error']['message'])) {
                            $error_message .= ' - ' . $error_data_parsed['error']['message'];
                        }
                    }
                }
            }
            
            wp_send_json_error(array(
                'message' => $error_message,
                'details' => is_array($error_data) ? $error_data : null
            ));
        } else {
            wp_send_json_success(array(
                'prompt' => $prompt_result['prompt'],
                'test_content' => $test_content
            ));
        }
    }
    
    /**
     * 处理图像模型测试请求（提示词生成图片）
     */
    public function handle_test_image() {
        // 增加PHP执行时间限制，避免超时
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @set_time_limit(1800); // 30分钟
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('max_execution_time', 1800);
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('default_socket_timeout', 600);
        
        // 验证nonce
        check_ajax_referer('aicg_test_api', 'nonce');
        
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '权限不足'));
            return;
        }
        
        $test_prompt = isset($_POST['test_prompt']) ? sanitize_text_field(wp_unslash($_POST['test_prompt'])) : '';
        
        if (empty($test_prompt)) {
            $test_prompt = 'A beautiful landscape with mountains and a lake, cinematic lighting, vibrant colors, high quality, detailed';
        }
        
        // 初始化API类
        $api = new AICG_API();
        
        // 测试图像模型
        $image_result = $api->generate_image($test_prompt);
        
        if (is_wp_error($image_result)) {
            $error_data = $image_result->get_error_data();
            $error_message = $image_result->get_error_message();
            
            // 添加更详细的错误信息
            if (is_array($error_data)) {
                if (isset($error_data['status_code'])) {
                    $error_message .= ' (HTTP ' . $error_data['status_code'] . ')';
                }
                if (isset($error_data['response_body'])) {
                    $response_body = $error_data['response_body'];
                    $error_data_parsed = json_decode($response_body, true);
                    if ($error_data_parsed && isset($error_data_parsed['error'])) {
                        if (is_array($error_data_parsed['error']) && isset($error_data_parsed['error']['message'])) {
                            $error_message .= ' - ' . $error_data_parsed['error']['message'];
                        }
                    }
                }
            }
            
            wp_send_json_error(array(
                'message' => $error_message,
                'details' => is_array($error_data) ? $error_data : null
            ));
        } else {
            wp_send_json_success(array(
                'image_url' => $image_result['image_url'],
                'prompt' => $test_prompt
            ));
        }
    }
    
    /**
     * 处理生成图片并设置为特色图片的请求
     * 用于编辑器中的分步执行，避免超时
     */
    public function handle_generate_image_and_set() {
        // 增加PHP执行时间限制，避免超时
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @set_time_limit(1800); // 30分钟
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('max_execution_time', 1800);
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('default_socket_timeout', 600);
        
        // 验证nonce
        check_ajax_referer('aicg_generate_cover', 'nonce');
        
        // 检查权限
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '权限不足'));
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $prompt = isset($_POST['prompt']) ? sanitize_text_field(wp_unslash($_POST['prompt'])) : '';
        
        if (empty($prompt)) {
            wp_send_json_error(array('message' => '提示词为空'));
            return;
        }
        
        if ($post_id <= 0) {
            wp_send_json_error(array('message' => '文章ID无效'));
            return;
        }
        
        // 初始化API类
        $api = new AICG_API();
        
        // 生成图像
        $image_result = $api->generate_image($prompt);
        
        if (is_wp_error($image_result)) {
            $error_data = $image_result->get_error_data();
            $error_message = $image_result->get_error_message();
            
            // 添加更详细的错误信息
            if (is_array($error_data)) {
                if (isset($error_data['status_code'])) {
                    $error_message .= ' (HTTP ' . $error_data['status_code'] . ')';
                }
            }
            
            wp_send_json_error(array(
                'message' => $error_message,
                'details' => is_array($error_data) ? $error_data : null
            ));
            return;
        }
        
        $image_url = $image_result['image_url'];
        
        // 下载图片并设置为特色图片
        $attachment_id = $this->download_image($image_url, $post_id);
        
        if ($attachment_id && !is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            
            wp_send_json_success(array(
                'image_url' => $image_url,
                'attachment_id' => $attachment_id,
                'message' => '封面生成成功'
            ));
        } else {
            $error_msg = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : '下载图片失败';
            wp_send_json_error(array('message' => $error_msg));
        }
    }
}

