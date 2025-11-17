#!/bin/bash

# WordPress 插件打包脚本
# 此脚本会创建一个干净的 ZIP 文件，不包含 .DS_Store 和其他系统文件

PLUGIN_SLUG="doubao-ai-cover-generator"
VERSION="1.0.1"
OUTPUT_DIR="../"
OUTPUT_FILE="${OUTPUT_DIR}${PLUGIN_SLUG}-${VERSION}.zip"

echo "正在清理系统文件..."
# 删除所有 .DS_Store 文件
find . -name ".DS_Store" -type f -delete
find . -name "._*" -type f -delete

echo "正在创建 ZIP 文件..."
# 使用 zip 命令创建压缩包，排除不需要的文件
zip -r "${OUTPUT_FILE}" . \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*.DS_Store" \
  -x "*__MACOSX*" \
  -x "*.gitignore" \
  -x "build.sh" \
  -x "*.log"

echo "完成！生成的文件: ${OUTPUT_FILE}"
echo ""
echo "文件大小："
ls -lh "${OUTPUT_FILE}"


