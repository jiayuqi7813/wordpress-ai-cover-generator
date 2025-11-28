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
            alert(aicgClassicEditor.strings.enterContent);
            return;
        }
        
        var postId = $('#post_ID').val();
        var ajaxUrl = aicgClassicEditor.ajaxUrl;
        var nonce = aicgClassicEditor.nonce;
        
        // 第一步：生成提示词
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> ' + aicgClassicEditor.strings.step1);
        
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
                    $btn.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> ' + aicgClassicEditor.strings.step2);
                    
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
                                alert(aicgClassicEditor.strings.success);
                                location.reload();
                            } else {
                                alert(aicgClassicEditor.strings.failImage + (response.data.message || aicgClassicEditor.strings.unknownError));
                                $btn.prop('disabled', false).html(originalText);
                            }
                        },
                        error: function(xhr, status, error) {
                            var errorMsg = aicgClassicEditor.strings.failGenImage;
                            if (status === 'timeout') {
                                errorMsg += '：' + aicgClassicEditor.strings.timeout;
                            } else if (xhr.status === 524) {
                                errorMsg += '：' + aicgClassicEditor.strings.serverTimeout;
                            } else if (xhr.status) {
                                errorMsg += '：' + aicgClassicEditor.strings.httpError + xhr.status;
                            }
                            alert('✗ ' + errorMsg);
                            $btn.prop('disabled', false).html(originalText);
                        }
                    });
                } else {
                    alert(aicgClassicEditor.strings.failPrompt + (response.data.message || aicgClassicEditor.strings.unknownError));
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = aicgClassicEditor.strings.failGenPrompt;
                if (status === 'timeout') {
                    errorMsg += '：' + aicgClassicEditor.strings.timeout;
                } else if (xhr.status === 524) {
                    errorMsg += '：' + aicgClassicEditor.strings.serverTimeout;
                } else if (xhr.status) {
                    errorMsg += '：' + aicgClassicEditor.strings.httpError + xhr.status;
                }
                alert('✗ ' + errorMsg);
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
