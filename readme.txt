=== AI Cover Generator for Doubao ===
Contributors: jiayuqi
Donate link: https://www.snowywar.top
Tags: ai, cover, image, doubao, automation
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate beautiful cover images for WordPress posts using Doubao AI.

== Description ==

AI Cover Generator for Doubao is a powerful WordPress plugin that automatically generates beautiful AI-powered cover images based on your post content.

**Key Features:**

* 🎨 **Smart Analysis**: Automatically analyzes post content to generate appropriate image prompts
* 🖼️ **AI Generation**: Creates high-quality covers using Doubao AI's advanced image generation technology
* ⚡ **One-Click Generation**: Generate and set cover images with a single button click in the editor
* 🎯 **Dual Editor Support**: Works seamlessly with both Gutenberg and Classic Editor
* 🔧 **Flexible Configuration**: Customizable API settings and model selection
* 🧪 **Testing Tools**: Built-in API testing tool for quick configuration verification

**How It Works:**

1. The plugin reads your post content
2. Uses Doubao AI text model to generate image description prompts
3. Uses Doubao AI image model to generate cover images based on prompts
4. Automatically sets the generated image as the post's featured image

**Supported AI Models:**

* Text Model: doubao-seed-1-6-251015
* Image Model: doubao-seedream-4-0-250828

== Installation ==

**Automatic Installation:**

1. Log in to your WordPress admin panel
2. Navigate to "Plugins" > "Add New"
3. Search for "AI Cover Generator for Doubao"
4. Click "Install Now" and then "Activate"

**Manual Installation:**

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to "Plugins" > "Add New" > "Upload Plugin"
4. Select the ZIP file and upload
5. Click "Activate Plugin"

**Configuration:**

1. Go to "Settings" > "AI Cover Generator"
2. Enter the Doubao AI API base URL (default: https://ark.cn-beijing.volces.com/api/v3)
3. Enter your Doubao API key
4. Configure text and image models (default values are provided)
5. Click "Save Settings"
6. Use the "API Test" feature to verify your configuration

== Frequently Asked Questions ==

= How do I get a Doubao AI API key? =

Please visit the Doubao AI official website (https://www.volcengine.com/) to register an account and obtain an API key.

= Which editors does the plugin support? =

The plugin supports both WordPress Gutenberg block editor and the Classic Editor.

= Are generated images automatically saved? =

Yes, generated images are automatically uploaded to the media library and set as the post's featured image.

= How long does it take to generate a cover image? =

It usually takes 30-60 seconds, depending on network conditions and AI server load.

= What should I do if generation fails? =

1. Check your network connection
2. Verify your API key is correct
3. Use the "API Test" feature on the settings page for diagnostics
4. Review detailed error messages

= Does the plugin incur additional costs? =

The plugin itself is free, but using Doubao AI services requires payment according to Doubao's pricing structure.

== Screenshots ==

1. AI Cover Generator meta box in the editor
2. Settings page - API configuration
3. Settings page - API testing tool
4. AI Cover Generator panel in Gutenberg editor

== Changelog ==

= 1.0.1 =
* Optimization: Step-by-step generation process to avoid timeout issues
* Optimization: Added detailed error messages
* Optimization: Improved UI interaction experience
* Fixed: Nonce verification issues
* Fixed: Code standards compliance with WordPress guidelines

= 1.0.0 =
* Initial release
* Support for automatic AI cover generation
* Support for Gutenberg and Classic Editor
* Built-in API testing tool
* Customizable API configuration

== Upgrade Notice ==

= 1.0.1 =
This version fixes timeout issues and improves error handling. Upgrade recommended for all users.

= 1.0.0 =
Initial release. Welcome!

== Additional Information ==

**Developer Information:**

* GitHub: https://github.com/jiayuqi7813/wordpress-ai-cover-generator
* Author Website: https://www.snowywar.top

**Support:**

If you encounter any issues, please submit feedback in the WordPress support forum or GitHub Issues.

**Privacy Notice:**

This plugin sends post content to Doubao AI servers to generate cover images. Please ensure you understand and agree to Doubao AI's privacy policy.

