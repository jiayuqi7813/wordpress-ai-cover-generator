jQuery(document).ready(function($) {
    $('#aicg-meta-generate-btn').on('click', function() {
        var $btn = $(this);
        var $status = $('#aicg-meta-status');
        var originalHtml = $btn.html();
        
        // 禁用按钮
        $btn.prop('disabled', true);
        $status.show().html('');
        
        // 获取文章内容
        var postContent = '';
        
        // Gutenberg 编辑器
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            var content = wp.data.select('core/editor').getEditedPostContent();
            postContent = content.replace(/<[^>]*>/g, '').trim();
        }
        // 经典编辑器
        else if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
            postContent = tinymce.get('content').getContent({format: 'text'});
        } else {
            postContent = $('#content').val();
        }
        
        if (!postContent || postContent.trim() === '') {
            $status.html('<div style="color: #d63638; padding: 10px; background: #fcf0f1; border-left: 3px solid #d63638; border-radius: 3px;">请先输入文章内容</div>');
            $btn.prop('disabled', false);
            return;
        }
        
        var postId = aicgMetaBox.postId || $('#post_ID').val();
        
        // 第一步：生成提示词
        $btn.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> 步骤 1/2：生成提示词...');
        $status.html('<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px;">⏳ 正在分析文章内容，生成图像提示词...</div>');
        
        $.ajax({
            url: aicgMetaBox.ajaxUrl,
            type: 'POST',
            timeout: 1800000, // 30分钟
            data: {
                action: 'aicg_test_text',
                test_content: postContent,
                nonce: aicgMetaBox.nonce
            },
            success: function(response) {
                if (response.success && response.data.prompt) {
                    var prompt = response.data.prompt;
                    
                    // 显示生成的提示词
                    $status.html('<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #46b450; border-radius: 3px;">✓ 提示词生成成功<br><small style="color: #666;">' + escapeHtml(prompt.substring(0, 100)) + '...</small></div>');
                    
                    // 第二步：生成图片
                    setTimeout(function() {
                        generateImage(prompt, postId, postContent, $btn, $status, originalHtml);
                    }, 500);
                } else {
                    $status.html('<div style="color: #d63638; padding: 10px; background: #fcf0f1; border-left: 3px solid #d63638; border-radius: 3px;"><strong>✗ 生成提示词失败</strong><br>' + (response.data.message || '未知错误') + '</div>');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr, status, error) {
                handleError(xhr, status, error, '生成提示词', $btn, $status, originalHtml);
            }
        });
    });
    
    // 第二步：生成图片
    function generateImage(prompt, postId, postContent, $btn, $status, originalHtml) {
        $btn.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> 步骤 2/2：生成图片...');
        $status.html('<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px;">⏳ 正在使用 AI 生成封面图片，请稍候...</div>');
        
        $.ajax({
            url: aicgMetaBox.ajaxUrl,
            type: 'POST',
            timeout: 1800000, // 30分钟
            data: {
                action: 'aicg_generate_image_and_set',
                post_id: postId,
                prompt: prompt,
                nonce: aicgMetaBox.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<div style="color: #46b450; padding: 10px; background: #f0f6fc; border-left: 3px solid #46b450; border-radius: 3px;"><strong>✓ 封面生成成功！</strong><br>图片已自动设置为特色图片。页面将在 2 秒后刷新...</div>');
                    
                    // 2秒后刷新页面
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $status.html('<div style="color: #d63638; padding: 10px; background: #fcf0f1; border-left: 3px solid #d63638; border-radius: 3px;"><strong>✗ 生成图片失败</strong><br>' + (response.data.message || '未知错误') + '</div>');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr, status, error) {
                handleError(xhr, status, error, '生成图片', $btn, $status, originalHtml);
            }
        });
    }
    
    // 错误处理
    function handleError(xhr, status, error, step, $btn, $status, originalHtml) {
        var errorMsg = '请求失败';
        
        if (status === 'timeout') {
            errorMsg = '请求超时，生成时间过长。建议：<br>1. 检查网络连接<br>2. 前往"设置 > AI封面生成器"进行测试<br>3. 尝试勾选"使用客户端直接请求"';
        } else if (xhr.status === 524) {
            errorMsg = '服务器超时（524错误）。这通常是因为：<br>1. AI生成时间过长<br>2. 服务器或CDN的超时限制<br><br>建议前往"设置 > AI封面生成器"测试页面，勾选"使用客户端直接请求"进行测试。';
        } else if (xhr.status) {
            errorMsg = 'HTTP错误 ' + xhr.status + ': ' + (error || xhr.statusText);
        }
        
        $status.html('<div style="color: #d63638; padding: 10px; background: #fcf0f1; border-left: 3px solid #d63638; border-radius: 3px;"><strong>✗ ' + step + '失败</strong><br>' + errorMsg + '</div>');
        $btn.prop('disabled', false).html(originalHtml);
    }
    
    // HTML转义
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});

