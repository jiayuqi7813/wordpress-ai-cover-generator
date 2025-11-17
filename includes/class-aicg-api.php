<?php
/**
 * API调用类
 * 处理与豆包AI的API交互
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_API {
    
    private $api_key;
    private $api_base_url;
    private $text_model;
    private $image_model;
    
    public function __construct() {
        $this->api_key = get_option('aicg_api_key', '');
        $this->api_base_url = get_option('aicg_api_base_url', 'https://ark.cn-beijing.volces.com');
        $this->text_model = get_option('aicg_text_model', 'doubao-seed-1-6-251015');
        $this->image_model = get_option('aicg_image_model', 'doubao-seedream-4-0-250828');
    }
    
    /**
     * 生成图像提示词
     * 
     * @param string $content 文章内容
     * @return array|WP_Error 返回prompt或错误
     */
    public function generate_prompt($content) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API密钥未设置');
        }
        
        // 构建提示词，让AI总结文章并生成图像描述
        $system_prompt = "你是一个专业的图像提示词生成专家。请根据以下文章内容，生成英文提示词，去掉可能存在违反安全策略的内容。提示词应该包含：主要场景、色彩风格、光影效果、构图方式、艺术风格等元素。只返回提示词，不要其他解释，最多不超出40个单词。";
        
        $url = $this->api_base_url . '/api/v3/chat/completions';
        
        $body = array(
            'model' => $this->text_model,
            'max_completion_tokens' => 65535,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => "请为以下文章生成图像提示词：\n\n" . $content
                )
            ),
            'thinking' => array(
                'type' => 'disabled'
            )
        );
        
        $response = $this->make_request($url, $body);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // 解析响应
        if (isset($response['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'prompt' => trim($response['choices'][0]['message']['content'])
            );
        }
        
        return new WP_Error('invalid_response', 'API返回格式错误');
    }
    
    /**
     * 生成图像
     * 
     * @param string $prompt 图像提示词
     * @return array|WP_Error 返回图像URL或错误
     */
    public function generate_image($prompt) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API密钥未设置');
        }
        
        $url = $this->api_base_url . '/api/v3/images/generations';
        
        $body = array(
            'model' => $this->image_model,
            'prompt' => $prompt,
            'sequential_image_generation' => 'disabled',
            'response_format' => 'url',
            'size' => "2560x1440",
            'stream' => false,
            'watermark' => true
        );
        
        $response = $this->make_request($url, $body);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // 解析响应
        if (isset($response['data'][0]['url'])) {
            return array(
                'success' => true,
                'image_url' => $response['data'][0]['url']
            );
        }
        
        return new WP_Error('invalid_response', 'API返回格式错误');
    }
    
    /**
     * 执行API请求
     * 
     * @param string $url API地址
     * @param array $body 请求体
     * @return array|WP_Error
     */
    private function make_request($url, $body) {
        // 增加PHP执行时间限制，避免脚本超时
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @set_time_limit(1800); // 30分钟
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
        @ini_set('max_execution_time', 1800);
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($body),
            'timeout' => 600, // 10分钟，AI生成可能需要较长时间
            'connect_timeout' => 120, // 连接超时2分钟
            'stream' => false,
            'redirection' => 5
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        if ($response_code !== 200) {
            $error_message = 'API请求失败';
            $error_message .= ' | HTTP状态码: ' . $response_code;
            
            // 尝试解析错误响应
            $error_data = json_decode($response_body, true);
            if ($error_data && isset($error_data['error'])) {
                if (is_string($error_data['error'])) {
                    $error_message .= ' | 错误信息: ' . $error_data['error'];
                } elseif (is_array($error_data['error'])) {
                    if (isset($error_data['error']['message'])) {
                        $error_message .= ' | 错误信息: ' . $error_data['error']['message'];
                    }
                    if (isset($error_data['error']['type'])) {
                        $error_message .= ' | 错误类型: ' . $error_data['error']['type'];
                    }
                    if (isset($error_data['error']['code'])) {
                        $error_message .= ' | 错误代码: ' . $error_data['error']['code'];
                    }
                }
            } else {
                // 如果无法解析JSON，显示原始响应的前500个字符
                $error_message .= ' | 响应内容: ' . substr($response_body, 0, 500);
            }
            
            return new WP_Error('api_error', $error_message, array(
                'status_code' => $response_code,
                'response_body' => $response_body,
                'response_headers' => $response_headers
            ));
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'JSON解析失败：' . json_last_error_msg());
        }
        
        return $data;
    }
}

