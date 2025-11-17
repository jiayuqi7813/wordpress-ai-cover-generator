=== Doubao AI Cover Generator ===
Contributors: sn1war
Donate link: https://www.snowywar.top
Tags: ai, cover, image, doubao, automation
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

自动使用豆包 AI 为 WordPress 文章生成精美的封面图片。

== Description ==

Doubao AI Cover Generator 是一款强大的 WordPress 插件，能够根据文章内容自动生成精美的 AI 封面图片。

**主要特性：**

* 🎨 **智能分析**：自动分析文章内容，生成合适的图像提示词
* 🖼️ **AI 生成**：使用豆包 AI 的先进图像生成技术创建高质量封面
* ⚡ **一键生成**：在编辑器中点击一个按钮即可生成并设置封面
* 🎯 **双编辑器支持**：同时支持 Gutenberg 和经典编辑器
* 🔧 **灵活配置**：可自定义 API 设置和模型选择
* 🧪 **测试功能**：内置 API 测试工具，快速验证配置

**工作流程：**

1. 插件读取文章内容
2. 使用豆包 AI 文字模型生成图像描述提示词
3. 使用豆包 AI 图像模型根据提示词生成封面图片
4. 自动将生成的图片设置为文章特色图片

**支持的 AI 模型：**

* 文字模型：doubao-seed-1-6-251015
* 图像模型：doubao-seedream-4-0-250828

== Installation ==

**自动安装：**

1. 登录 WordPress 后台
2. 进入"插件" > "安装插件"
3. 搜索 "Doubao AI Cover Generator"
4. 点击"立即安装"，然后点击"启用"

**手动安装：**

1. 下载插件 ZIP 文件
2. 登录 WordPress 后台
3. 进入"插件" > "安装插件" > "上传插件"
4. 选择 ZIP 文件并上传
5. 点击"立即启用"

**配置：**

1. 前往"设置" > "AI 封面生成器"
2. 输入豆包 AI 的 API 基础 URL（默认：https://ark.cn-beijing.volces.com/api/v3）
3. 输入你的豆包 API 密钥
4. 配置文字模型和图像模型（可使用默认值）
5. 点击"保存设置"
6. 使用"API 测试"功能验证配置是否正确

== Frequently Asked Questions ==

= 如何获取豆包 AI API 密钥？ =

请访问豆包 AI 官方网站（https://www.volcengine.com/）注册账号并获取 API 密钥。

= 插件支持哪些编辑器？ =

插件同时支持 WordPress 的 Gutenberg 区块编辑器和经典编辑器。

= 生成的图片会自动保存吗？ =

是的，生成的图片会自动上传到媒体库并设置为文章的特色图片。

= 生成一张封面需要多长时间？ =

通常需要 30-60 秒，具体时间取决于网络状况和 AI 服务器的负载。

= 如果生成失败怎么办？ =

1. 检查网络连接
2. 确认 API 密钥正确
3. 使用设置页面的"API 测试"功能进行诊断
4. 查看详细的错误信息

= 插件会产生额外费用吗？ =

插件本身免费，但使用豆包 AI 服务需要按照豆包的定价标准付费。

== Screenshots ==

1. 编辑器中的 AI 封面生成器元框
2. 设置页面 - API 配置
3. 设置页面 - API 测试工具
4. Gutenberg 编辑器中的 AI 封面生成面板

== Changelog ==

= 1.0.1 =
* 优化：分步执行生成流程，避免超时问题
* 优化：增加详细的错误提示信息
* 优化：改进 UI 交互体验
* 修复：Nonce 验证问题
* 修复：代码规范问题以符合 WordPress 标准

= 1.0.0 =
* 首次发布
* 支持自动生成 AI 封面
* 支持 Gutenberg 和经典编辑器
* 内置 API 测试工具
* 可自定义 API 配置

== Upgrade Notice ==

= 1.0.1 =
此版本修复了超时问题并改进了错误处理，建议所有用户升级。

= 1.0.0 =
首次发布，欢迎使用！

== Additional Information ==

**开发者信息：**

* GitHub: https://github.com/jiayuqi7813/wordpress-ai-cover-generator
* 作者网站: https://www.snowywar.top

**技术支持：**

如遇到问题，请在 WordPress 支持论坛或 GitHub Issues 中提交反馈。

**隐私说明：**

本插件会将文章内容发送到豆包 AI 服务器以生成封面图片。请确保你了解并同意豆包 AI 的隐私政策。

