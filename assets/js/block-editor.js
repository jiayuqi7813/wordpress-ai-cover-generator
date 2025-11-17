(function(blocks, element, editor, components, i18n, plugins, editPost, data) {
    const { registerPlugin } = plugins;
    const { PluginDocumentSettingPanel } = editPost;
    const { Button, Spinner, Notice } = components;
    const { useState } = element;
    const { __ } = i18n;
    const { select, dispatch } = data;
    
    const AICoverGenerator = () => {
        const [isGenerating, setIsGenerating] = useState(false);
        const [message, setMessage] = useState('');
        const [messageType, setMessageType] = useState('');
        const [currentStep, setCurrentStep] = useState('');
        
        const generateCover = async () => {
            setIsGenerating(true);
            setMessage('');
            setCurrentStep('');
            
            // 获取文章内容
            const content = select('core/editor').getEditedPostContent();
            const postId = select('core/editor').getCurrentPostId();
            
            // 提取纯文本内容
            const textContent = content.replace(/<[^>]*>/g, '').trim();
            
            if (!textContent) {
                setMessage('请先输入文章内容');
                setMessageType('error');
                setIsGenerating(false);
                return;
            }
            
            try {
                // 第一步：生成提示词
                setCurrentStep('步骤 1/2：生成提示词...');
                setMessage('正在分析文章内容，生成图像提示词...');
                setMessageType('info');
                
                const formData1 = new FormData();
                formData1.append('action', 'aicg_test_text');
                formData1.append('test_content', textContent);
                formData1.append('nonce', aicgData.nonce);
                
                const controller1 = new AbortController();
                const timeoutId1 = setTimeout(() => controller1.abort(), 1800000); // 30分钟
                
                const response1 = await fetch(aicgData.ajaxUrl, {
                    method: 'POST',
                    body: formData1,
                    signal: controller1.signal
                });
                
                clearTimeout(timeoutId1);
                const result1 = await response1.json();
                
                if (!result1.success || !result1.data.prompt) {
                    throw new Error('生成提示词失败：' + (result1.data?.message || '未知错误'));
                }
                
                const prompt = result1.data.prompt;
                setMessage('✓ 提示词生成成功：' + prompt.substring(0, 100) + '...');
                setMessageType('success');
                
                // 等待500ms，让用户看到第一步成功
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // 第二步：生成图片
                setCurrentStep('步骤 2/2：生成图片...');
                setMessage('正在使用 AI 生成封面图片，请稍候...');
                setMessageType('info');
                
                const formData2 = new FormData();
                formData2.append('action', 'aicg_generate_image_and_set');
                formData2.append('post_id', postId);
                formData2.append('prompt', prompt);
                formData2.append('nonce', aicgData.nonce);
                
                const controller2 = new AbortController();
                const timeoutId2 = setTimeout(() => controller2.abort(), 1800000); // 30分钟
                
                const response2 = await fetch(aicgData.ajaxUrl, {
                    method: 'POST',
                    body: formData2,
                    signal: controller2.signal
                });
                
                clearTimeout(timeoutId2);
                const result2 = await response2.json();
                
                if (result2.success) {
                    setMessage('✓ 封面生成成功！图片已自动设置为特色图片。');
                    setMessageType('success');
                    setCurrentStep('');
                    
                    // 刷新编辑器以显示新的特色图片
                    if (result2.data.attachment_id) {
                        dispatch('core/editor').refreshPost();
                    }
                } else {
                    throw new Error('生成图片失败：' + (result2.data?.message || '未知错误'));
                }
            } catch (error) {
                let errorMsg = '✗ 请求失败：' + error.message;
                if (error.name === 'AbortError') {
                    errorMsg = '✗ 请求超时，生成时间过长。建议前往"设置 > AI封面生成器"进行测试。';
                }
                setMessage(errorMsg);
                setMessageType('error');
                setCurrentStep('');
            } finally {
                setIsGenerating(false);
            }
        };
        
        return (
            <PluginDocumentSettingPanel
                name="ai-cover-generator"
                title="AI封面生成器"
                className="aicg-panel"
            >
                <p>基于当前文章内容，使用AI生成封面图片。</p>
                
                {message && (
                    <Notice
                        status={messageType === 'success' ? 'success' : (messageType === 'info' ? 'info' : 'error')}
                        isDismissible={!isGenerating}
                        onRemove={() => setMessage('')}
                    >
                        {message}
                    </Notice>
                )}
                
                <Button
                    isPrimary
                    onClick={generateCover}
                    disabled={isGenerating}
                    style={{ marginTop: '10px' }}
                >
                    {isGenerating ? (
                        <>
                            <Spinner />
                            <span style={{ marginLeft: '8px' }}>{currentStep || '正在生成...'}</span>
                        </>
                    ) : (
                        '生成AI封面'
                    )}
                </Button>
            </PluginDocumentSettingPanel>
        );
    };
    
    registerPlugin('ai-cover-generator', {
        render: AICoverGenerator,
        icon: 'format-image'
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.editor,
    window.wp.components,
    window.wp.i18n,
    window.wp.plugins,
    window.wp.editPost,
    window.wp.data
);

