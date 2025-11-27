# 打包说明

## 如何创建干净的 WordPress 插件 ZIP 文件

### 方法 1：使用自动化脚本（推荐）

```bash
./build.sh
```

这个脚本会自动：
- 删除所有 `.DS_Store` 文件
- 删除所有 `._*` 隐藏文件
- 创建干净的 ZIP 文件
- 排除 `.git`、`node_modules` 等不需要的文件

生成的文件位于上级目录：`../ai-cover-generator-for-doubao-1.0.1.zip`

### 方法 2：手动打包（命令行）

```bash
# 1. 清理系统文件
find . -name ".DS_Store" -type f -delete
find . -name "._*" -type f -delete

# 2. 创建 ZIP（从父目录）
cd ..
zip -r ai-cover-generator-for-doubao.zip wordpress-ai \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*.DS_Store" \
  -x "*__MACOSX*" \
  -x "*.gitignore" \
  -x "*.log"
```

### 方法 3：使用 macOS 访达（不推荐）

**注意：** macOS 的访达压缩功能会自动包含 `.DS_Store` 和 `__MACOSX` 文件夹，不适合用于 WordPress 插件提交。

如果必须使用访达：

1. 先运行清理命令：
   ```bash
   find . -name ".DS_Store" -type f -delete
   ```

2. 在访达中右键压缩

3. 解压 ZIP 文件，删除 `__MACOSX` 文件夹

4. 重新压缩

## 验证打包结果

解压 ZIP 文件并检查：

```bash
unzip -l ai-cover-generator-for-doubao-1.0.1.zip | grep -E "\.DS_Store|__MACOSX"
```

如果没有输出，说明打包成功！

## 防止 .DS_Store 生成

在终端运行（只影响网络驱动器）：

```bash
defaults write com.apple.desktopservices DSDontWriteNetworkStores true
```

重启访达：

```bash
killall Finder
```

## 提交前检查清单

- [ ] 运行 `./build.sh` 创建 ZIP
- [ ] 解压验证没有 `.DS_Store` 和 `__MACOSX`
- [ ] 使用 WordPress 插件检查器验证
- [ ] 测试安装到 WordPress 网站


