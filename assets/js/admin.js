jQuery(document).ready(function($) {
    // 测试文字模型（文章生成提示词）
    $('#aicg-test-text').on('click', function() {
        var $btn = $(this);
        var $results = $('#aicg-test-text-results');
        var $content = $('#aicg-test-text-content');
        var originalHtml = $btn.html();
        
        // 禁用按钮并显示加载状态
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> 正在测试...');
        $results.hide();
        $content.html('');
        
        // 测试用的示例文章内容
        var testContent = '这是一篇关于科技发展的文章。人工智能正在改变我们的生活方式，从智能手机到自动驾驶汽车，科技的力量无处不在。未来，我们将看到更多创新的技术应用，让生活变得更加便捷和智能。';
        
        // 检查是否使用客户端直接请求
        var useClientRequest = $('#aicg-use-client-request').is(':checked');
        
        if (useClientRequest) {
            // 客户端直接请求
            testTextModelClient(testContent, $results, $content, $btn, originalHtml);
        } else {
            // 服务器端请求（原有逻辑）
            testTextModelServer(testContent, $results, $content, $btn, originalHtml);
        }
    });
    
    // 服务器端测试文字模型
    function testTextModelServer(testContent, $results, $content, $btn, originalHtml) {
        $.ajax({
            url: aicgAdmin.ajaxUrl,
            type: 'POST',
            timeout: 1800000, // 30分钟超时（1800秒）
            data: {
                action: 'aicg_test_text',
                test_content: testContent,
                nonce: aicgAdmin.nonce
            },
            success: function(response) {
                $results.show();
                
                if (response.success) {
                    var html = '<div class="aicg-test-success">';
                    html += '<h4 style="color: #46b450; margin-top: 0;">✓ 文字模型测试成功</h4>';
                    html += '<div style="margin-top: 15px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
                    html += '<strong>测试内容：</strong><br>';
                    html += '<div style="margin-top: 5px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px; color: #666;">';
                    html += escapeHtml(response.data.test_content || '');
                    html += '</div>';
                    html += '<div style="margin-top: 15px;"><strong>生成的提示词：</strong></div>';
                    html += '<div style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">';
                    html += '<code style="word-break: break-all; white-space: pre-wrap; font-size: 13px;">' + escapeHtml(response.data.prompt) + '</code>';
                    html += '</div>';
                    html += '<div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 3px;">';
                    html += '<strong>提示：</strong> 可以将此提示词复制到下方的图像模型测试中使用。';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    $content.html(html);
                    
                    // 自动填充到图像测试的输入框
                    $('#aicg-test-prompt-input').val(response.data.prompt);
                } else {
                    var html = '<div class="aicg-test-error">';
                    html += '<h4 style="color: #dc3232; margin-top: 0;">✗ 文字模型测试失败</h4>';
                    html += '<p><strong>错误信息：</strong> ' + escapeHtml(response.data.message || '未知错误') + '</p>';
                    if (response.data.details) {
                        html += '<br><strong>详细信息：</strong><br>';
                        html += '<code style="display:block;margin-top:5px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;font-size:11px;max-height:200px;overflow:auto;word-break:break-all;">';
                        html += escapeHtml(JSON.stringify(response.data.details, null, 2));
                        html += '</code>';
                    }
                    html += '</div>';
                    $content.html(html);
                }
            },
            error: function(xhr, status, error) {
                $results.show();
                var html = '<div class="aicg-test-error">';
                html += '<h4 style="color: #dc3232; margin-top: 0;">✗ 请求失败</h4>';
                
                var errorDetails = [];
                errorDetails.push('<strong>状态：</strong> ' + escapeHtml(status || '未知'));
                
                if (xhr.status) {
                    var statusCode = xhr.status;
                    var statusText = escapeHtml(xhr.statusText || '');
                    
                    // 特殊处理常见的HTTP错误码
                    var statusExplanation = '';
                    if (statusCode === 524) {
                        statusExplanation = ' <span style="color: #d63638; font-weight: bold;">(Cloudflare超时错误)</span><br>';
                        statusExplanation += '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">';
                        statusExplanation += '<strong>问题说明：</strong>这是Cloudflare的超时错误，表示请求处理时间超过了Cloudflare的超时限制（通常为30-100秒）。<br>';
                        statusExplanation += '<strong>可能原因：</strong><br>';
                        statusExplanation += '1. AI生成需要较长时间（通常需要30-120秒）<br>';
                        statusExplanation += '2. Cloudflare的超时设置过短<br>';
                        statusExplanation += '3. 网络连接较慢<br><br>';
                        statusExplanation += '<strong>解决方案：</strong><br>';
                        statusExplanation += '1. 在Cloudflare后台增加"Origin Server"的超时时间（建议设置为180秒或更长）<br>';
                        statusExplanation += '2. 或者暂时禁用Cloudflare代理（使用DNS only模式）进行测试<br>';
                        statusExplanation += '3. 检查服务器到豆包API的网络连接速度';
                        statusExplanation += '</div>';
                    } else if (statusCode === 504) {
                        statusExplanation = ' <span style="color: #d63638;">(网关超时 - 上游服务器响应超时)</span>';
                    } else if (statusCode === 502) {
                        statusExplanation = ' <span style="color: #d63638;">(网关错误 - 上游服务器返回无效响应)</span>';
                    } else if (statusCode === 500) {
                        statusExplanation = ' <span style="color: #d63638;">(服务器内部错误)</span>';
                    } else if (statusCode === 401) {
                        statusExplanation = ' <span style="color: #d63638;">(未授权 - 请检查API密钥是否正确)</span>';
                    } else if (statusCode === 403) {
                        statusExplanation = ' <span style="color: #d63638;">(禁止访问 - 请检查API权限)</span>';
                    }
                    
                    errorDetails.push('<strong>HTTP状态码：</strong> ' + statusCode + ' ' + statusText + statusExplanation);
                }
                
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorDetails.push('<strong>错误信息：</strong> ' + escapeHtml(response.data.message));
                        }
                        if (response.data && response.data.details) {
                            errorDetails.push('<strong>详细信息：</strong> ' + escapeHtml(response.data.details));
                        }
                    } catch (e) {
                        // 如果不是JSON，显示原始响应
                        var responseText = xhr.responseText.substring(0, 500);
                        if (responseText) {
                            errorDetails.push('<strong>响应内容：</strong><br><code style="display:block;margin-top:5px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;font-size:11px;max-height:200px;overflow:auto;">' + escapeHtml(responseText) + '</code>');
                        }
                    }
                }
                
                if (error) {
                    errorDetails.push('<strong>错误类型：</strong> ' + escapeHtml(error));
                }
                
                if (xhr.readyState !== undefined) {
                    errorDetails.push('<strong>请求状态：</strong> ' + xhr.readyState + ' (0=未初始化, 1=已加载, 2=已接收, 3=处理中, 4=已完成)');
                }
                
                html += '<div style="line-height: 1.8;">' + errorDetails.join('<br>') + '</div>';
                html += '</div>';
                $content.html(html);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }
    
    // 客户端直接测试文字模型
    function testTextModelClient(testContent, $results, $content, $btn, originalHtml) {
        if (!aicgAdmin.apiKey) {
            $results.show();
            var html = '<div class="aicg-test-error">';
            html += '<h4 style="color: #dc3232; margin-top: 0;">✗ API密钥未设置</h4>';
            html += '<p>请先在上方设置API密钥后再进行测试。</p>';
            html += '</div>';
            $content.html(html);
            $btn.prop('disabled', false).html(originalHtml);
            return;
        }
        
        var systemPrompt = "你是一个专业的图像提示词生成专家。请根据以下文章内容，生成英文提示词，去掉可能存在违反安全策略的内容。提示词应该包含：主要场景、色彩风格、光影效果、构图方式、艺术风格等元素。只返回提示词，不要其他解释，最多不超出40个单词。";
        
        var requestBody = {
            model: aicgAdmin.textModel,
            max_completion_tokens: 65535,
            messages: [
                {
                    role: 'system',
                    content: systemPrompt
                },
                {
                    role: 'user',
                    content: "请为以下文章生成图像提示词：\n\n" + testContent
                }
            ],
            thinking: {
                type: 'disabled'
            }
        };
        
        fetch(aicgAdmin.apiBaseUrl + '/api/v3/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + aicgAdmin.apiKey
            },
            body: JSON.stringify(requestBody)
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    throw new Error('HTTP ' + response.status + ': ' + text);
                });
            }
            return response.json();
        })
        .then(function(data) {
            $results.show();
            
            if (data.choices && data.choices[0] && data.choices[0].message && data.choices[0].message.content) {
                var prompt = data.choices[0].message.content.trim();
                var html = '<div class="aicg-test-success">';
                html += '<h4 style="color: #46b450; margin-top: 0;">✓ 文字模型测试成功（客户端直接请求）</h4>';
                html += '<div style="margin-top: 15px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
                html += '<strong>测试内容：</strong><br>';
                html += '<div style="margin-top: 5px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px; color: #666;">';
                html += escapeHtml(testContent);
                html += '</div>';
                html += '<div style="margin-top: 15px;"><strong>生成的提示词：</strong></div>';
                html += '<div style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">';
                html += '<code style="word-break: break-all; white-space: pre-wrap; font-size: 13px;">' + escapeHtml(prompt) + '</code>';
                html += '</div>';
                html += '<div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 3px;">';
                html += '<strong>提示：</strong> 可以将此提示词复制到下方的图像模型测试中使用。';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                $content.html(html);
                
                // 自动填充到图像测试的输入框
                $('#aicg-test-prompt-input').val(prompt);
            } else {
                throw new Error('API返回格式错误');
            }
        })
        .catch(function(error) {
            $results.show();
            var html = '<div class="aicg-test-error">';
            html += '<h4 style="color: #dc3232; margin-top: 0;">✗ 客户端请求失败</h4>';
            html += '<p><strong>错误信息：</strong> ' + escapeHtml(error.message || error.toString()) + '</p>';
            html += '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">';
            html += '<strong>提示：</strong> 如果是CORS错误，说明豆包API不支持跨域请求，请取消勾选"使用客户端直接请求"。';
            html += '</div>';
            html += '</div>';
            $content.html(html);
        })
        .finally(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    }
    
    // 测试图像模型（提示词生成图片）
    $('#aicg-test-image').on('click', function() {
        var $btn = $(this);
        var $results = $('#aicg-test-image-results');
        var $content = $('#aicg-test-image-content');
        var originalHtml = $btn.html();
        
        // 禁用按钮并显示加载状态
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> 正在生成图片...');
        $results.hide();
        $content.html('');
        
        // 获取提示词
        var testPrompt = $('#aicg-test-prompt-input').val().trim();
        
        if (!testPrompt) {
            testPrompt = 'A beautiful landscape with mountains and a lake, cinematic lighting, vibrant colors, high quality, detailed';
        }
        
        // 检查是否使用客户端直接请求
        var useClientRequest = $('#aicg-use-client-request').is(':checked');
        
        if (useClientRequest) {
            // 客户端直接请求
            testImageModelClient(testPrompt, $results, $content, $btn, originalHtml);
        } else {
            // 服务器端请求（原有逻辑）
            testImageModelServer(testPrompt, $results, $content, $btn, originalHtml);
        }
    });
    
    // 服务器端测试图像模型
    function testImageModelServer(testPrompt, $results, $content, $btn, originalHtml) {
        $.ajax({
            url: aicgAdmin.ajaxUrl,
            type: 'POST',
            timeout: 1800000, // 30分钟超时（1800秒）
            data: {
                action: 'aicg_test_image',
                test_prompt: testPrompt,
                nonce: aicgAdmin.nonce
            },
            success: function(response) {
                $results.show();
                
                if (response.success) {
                    var html = '<div class="aicg-test-success">';
                    html += '<h4 style="color: #46b450; margin-top: 0;">✓ 图像模型测试成功</h4>';
                    html += '<div style="margin-top: 15px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
                    html += '<strong>使用的提示词：</strong><br>';
                    html += '<div style="margin-top: 5px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">';
                    html += '<code style="word-break: break-all; white-space: pre-wrap; font-size: 13px;">' + escapeHtml(response.data.prompt) + '</code>';
                    html += '</div>';
                    html += '<div style="margin-top: 15px;"><strong>生成的图片：</strong></div>';
                    html += '<div style="margin-top: 10px;">';
                    html += '<img src="' + escapeHtml(response.data.image_url) + '" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" />';
                    html += '</div>';
                    html += '<div style="margin-top: 15px;">';
                    html += '<strong>图片URL：</strong><br>';
                    html += '<code style="word-break: break-all; font-size: 11px; color: #666; display: block; margin-top: 5px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">' + escapeHtml(response.data.image_url) + '</code>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    $content.html(html);
                } else {
                    var html = '<div class="aicg-test-error">';
                    html += '<h4 style="color: #dc3232; margin-top: 0;">✗ 图像模型测试失败</h4>';
                    html += '<p><strong>错误信息：</strong> ' + escapeHtml(response.data.message || '未知错误') + '</p>';
                    if (response.data.details) {
                        html += '<br><strong>详细信息：</strong><br>';
                        html += '<code style="display:block;margin-top:5px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;font-size:11px;max-height:200px;overflow:auto;word-break:break-all;">';
                        html += escapeHtml(JSON.stringify(response.data.details, null, 2));
                        html += '</code>';
                    }
                    html += '</div>';
                    $content.html(html);
                }
            },
            error: function(xhr, status, error) {
                $results.show();
                var html = '<div class="aicg-test-error">';
                html += '<h4 style="color: #dc3232; margin-top: 0;">✗ 请求失败</h4>';
                
                var errorDetails = [];
                errorDetails.push('<strong>状态：</strong> ' + escapeHtml(status || '未知'));
                
                if (xhr.status) {
                    var statusCode = xhr.status;
                    var statusText = escapeHtml(xhr.statusText || '');
                    
                    // 特殊处理常见的HTTP错误码
                    var statusExplanation = '';
                    if (statusCode === 524) {
                        statusExplanation = ' <span style="color: #d63638; font-weight: bold;">(Cloudflare超时错误)</span><br>';
                        statusExplanation += '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">';
                        statusExplanation += '<strong>问题说明：</strong>这是Cloudflare的超时错误，表示请求处理时间超过了Cloudflare的超时限制（通常为30-100秒）。<br>';
                        statusExplanation += '<strong>可能原因：</strong><br>';
                        statusExplanation += '1. AI生成需要较长时间（通常需要30-120秒）<br>';
                        statusExplanation += '2. Cloudflare的超时设置过短<br>';
                        statusExplanation += '3. 网络连接较慢<br><br>';
                        statusExplanation += '<strong>解决方案：</strong><br>';
                        statusExplanation += '1. 在Cloudflare后台增加"Origin Server"的超时时间（建议设置为180秒或更长）<br>';
                        statusExplanation += '2. 或者暂时禁用Cloudflare代理（使用DNS only模式）进行测试<br>';
                        statusExplanation += '3. 检查服务器到豆包API的网络连接速度';
                        statusExplanation += '</div>';
                    } else if (statusCode === 504) {
                        statusExplanation = ' <span style="color: #d63638;">(网关超时 - 上游服务器响应超时)</span>';
                    } else if (statusCode === 502) {
                        statusExplanation = ' <span style="color: #d63638;">(网关错误 - 上游服务器返回无效响应)</span>';
                    } else if (statusCode === 500) {
                        statusExplanation = ' <span style="color: #d63638;">(服务器内部错误)</span>';
                    } else if (statusCode === 401) {
                        statusExplanation = ' <span style="color: #d63638;">(未授权 - 请检查API密钥是否正确)</span>';
                    } else if (statusCode === 403) {
                        statusExplanation = ' <span style="color: #d63638;">(禁止访问 - 请检查API权限)</span>';
                    }
                    
                    errorDetails.push('<strong>HTTP状态码：</strong> ' + statusCode + ' ' + statusText + statusExplanation);
                }
                
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorDetails.push('<strong>错误信息：</strong> ' + escapeHtml(response.data.message));
                        }
                        if (response.data && response.data.details) {
                            errorDetails.push('<strong>详细信息：</strong> ' + escapeHtml(response.data.details));
                        }
                    } catch (e) {
                        // 如果不是JSON，显示原始响应
                        var responseText = xhr.responseText.substring(0, 500);
                        if (responseText) {
                            errorDetails.push('<strong>响应内容：</strong><br><code style="display:block;margin-top:5px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;font-size:11px;max-height:200px;overflow:auto;">' + escapeHtml(responseText) + '</code>');
                        }
                    }
                }
                
                if (error) {
                    errorDetails.push('<strong>错误类型：</strong> ' + escapeHtml(error));
                }
                
                if (xhr.readyState !== undefined) {
                    errorDetails.push('<strong>请求状态：</strong> ' + xhr.readyState + ' (0=未初始化, 1=已加载, 2=已接收, 3=处理中, 4=已完成)');
                }
                
                html += '<div style="line-height: 1.8;">' + errorDetails.join('<br>') + '</div>';
                html += '</div>';
                $content.html(html);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }
    
    // 客户端直接测试图像模型
    function testImageModelClient(testPrompt, $results, $content, $btn, originalHtml) {
        if (!aicgAdmin.apiKey) {
            $results.show();
            var html = '<div class="aicg-test-error">';
            html += '<h4 style="color: #dc3232; margin-top: 0;">✗ API密钥未设置</h4>';
            html += '<p>请先在上方设置API密钥后再进行测试。</p>';
            html += '</div>';
            $content.html(html);
            $btn.prop('disabled', false).html(originalHtml);
            return;
        }
        
        var requestBody = {
            model: aicgAdmin.imageModel,
            prompt: testPrompt,
            sequential_image_generation: 'disabled',
            response_format: 'url',
            size: "2560x1440",
            stream: false,
            watermark: true
        };
        
        fetch(aicgAdmin.apiBaseUrl + '/api/v3/images/generations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + aicgAdmin.apiKey
            },
            body: JSON.stringify(requestBody)
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    throw new Error('HTTP ' + response.status + ': ' + text);
                });
            }
            return response.json();
        })
        .then(function(data) {
            $results.show();
            
            if (data.data && data.data[0] && data.data[0].url) {
                var imageUrl = data.data[0].url;
                var html = '<div class="aicg-test-success">';
                html += '<h4 style="color: #46b450; margin-top: 0;">✓ 图像模型测试成功（客户端直接请求）</h4>';
                html += '<div style="margin-top: 15px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
                html += '<strong>使用的提示词：</strong><br>';
                html += '<div style="margin-top: 5px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">';
                html += '<code style="word-break: break-all; white-space: pre-wrap; font-size: 13px;">' + escapeHtml(testPrompt) + '</code>';
                html += '</div>';
                html += '<div style="margin-top: 15px;"><strong>生成的图片：</strong></div>';
                html += '<div style="margin-top: 10px;">';
                html += '<img src="' + escapeHtml(imageUrl) + '" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" />';
                html += '</div>';
                html += '<div style="margin-top: 15px;">';
                html += '<strong>图片URL：</strong><br>';
                html += '<code style="word-break: break-all; font-size: 11px; color: #666; display: block; margin-top: 5px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">' + escapeHtml(imageUrl) + '</code>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                $content.html(html);
            } else {
                throw new Error('API返回格式错误');
            }
        })
        .catch(function(error) {
            $results.show();
            var html = '<div class="aicg-test-error">';
            html += '<h4 style="color: #dc3232; margin-top: 0;">✗ 客户端请求失败</h4>';
            html += '<p><strong>错误信息：</strong> ' + escapeHtml(error.message || error.toString()) + '</p>';
            html += '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">';
            html += '<strong>提示：</strong> 如果是CORS错误，说明豆包API不支持跨域请求，请取消勾选"使用客户端直接请求"。';
            html += '</div>';
            html += '</div>';
            $content.html(html);
        })
        .finally(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    }
    
    // HTML转义函数
    function escapeHtml(text) {
        if (text == null) return '';
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

