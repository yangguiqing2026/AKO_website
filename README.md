# Ako Website

> Author: AKO_studio

---
ako_doc_id: AKO_README_WEB_001
ako_version: v0.1.0
ako_status: 草稿
ako_title: AKO 官方网站 (AKO-Website)
ako_category: Website
ako_author: 杨越浩
ako_created: 2026-07-14
ako_source: AKO_DOC_001 v1.0.0
ako_project_root: D:\AKO_website
---

# AKO 官方网站（WEB）

## 1. 结论前置

AKO 官方网站（WEB）是贵州阿格装配式建筑智造有限公司的企业门户，承载品牌展示、产品技术背书、项目案例呈现与商务询盘四大核心功能。站点采用纯静态 HTML5 + CSS3 + 原生 JS，无框架依赖，极简体积，黄昏色调混凝土质感设计，当前已完成 6 个响应式页面，部署在 akobuild.cloud 域名下（ICP 备案中）。

## 2. 修订记录

| 版本 | 日期 | 修订人 | 修订内容 | 签发人 |
|------|------|--------|----------|--------|
| v0.1.0 | 2026-07-14 | 杨越浩 | 按 AKO_DOC_001 初始化，内容源自 AKO-Website-Whitepaper-v1.0.md | （待签发） |

## 3. 项目概述

### 3.1 定位

为 AKO（阿格建筑）构建一个极简、实用、高转化的企业官网，3 秒内让访客理解 AKO 是谁、能做什么、为什么可信。

### 3.2 核心能力

1. **品牌展示**：全屏 Hero + 一句话价值主张 + 信任标识（T/CECS 标准认证、4 大核心参数）
2. **产品技术背书**：三产品卡片（陶粒墙板 / 标准箱体 / 纹理定制），展开后含完整技术参数表
3. **项目案例呈现**：桐木岭 40 模块商业街区 + 三层别墅住宅案例，实景图 + 参数 + 叙事
4. **商务询盘转化**：联系表单（姓名/电话/项目类型/需求描述），一键获取报价

### 3.3 技术栈

| 技术 | 用途 |
|------|------|
| HTML5 + CSS3 | 页面结构与样式（CSS Variables 管理品牌色） |
| 原生 JavaScript | 极简交互（导航、表单验证、产品卡片展开） |
| Noto Sans SC | 全站字体（Google Fonts） |
| 阿里云虚拟主机 | 静态文件部署 |
| akobuild.cloud | 域名（已注册，ICP 备案中） |

## 4. 快速开始

### 4.1 环境要求

- 任意静态文件服务器（Nginx / Apache / Python http.server）
- 浏览器（Chrome / Firefox / Edge 最新版）

### 4.2 安装

无需安装。直接部署静态文件到 Web 服务器根目录即可。

### 4.3 运行

```bash
cd D:\AKO_website
python -m http.server 8080
```

打开浏览器访问 `http://localhost:8080`。

## 5. 项目结构

```
D:\AKO_website\
├── AKO-Website-Whitepaper-v1.0.md   # 官网架构白皮书
├── index.html              # 首页（Hero + 价值主张 + 信任标识）
├── products.html           # 产品页（三卡片网格 + 参数展开）
├── projects.html           # 项目案例页（桐木岭 + 别墅）
├── tech.html               # 技术背书页（参数大数字 + 标准认证）
├── about.html              # 关于页（品牌理念 + 公司信息）
├── contact.html            # 联系询盘页（表单 + 联系方式）
├── css/
│   └── main.css            # 全局样式（含 CSS Variables 品牌色）
├── js/
│   └── main.js             # 极简交互（导航、表单验证）
├── images/                 # 图片素材（Hero 背景、产品、项目）
├── api/                    # API 接口预留
├── assets/                 # 静态资源（favicon、logo 等）
└── data/                   # 数据文件
```

## 6. 相关文档

- 官网架构白皮书：`D:\AKO_website\AKO-Website-Whitepaper-v1.0.md`
- 设计哲学参考：MUJI（极简 IA）、Vitra（产品展示克制）、Tadao Ando（混凝土美学）

## 7. 术语

| 术语 | 定义 |
|------|------|
| WEB | Website，AKO 官方网站代号 |
| Hero | 首页首屏全屏区，承载核心品牌信息 |
| 三卡片 | 产品页的陶粒墙板 / 标准箱体 / 纹理定制三个入口卡片 |
| 黄昏调 | AKO 品牌专属色系：奶油金 `#EBDAB9` / 深棕黑 `#231E1C` / 熔金 `#B99B5F` |
| T/CECS 10154-2021 | 陶粒发泡混凝土一体化墙板行业标准认证 |
