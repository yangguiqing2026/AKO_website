@echo off
chcp 65001 >nul
title AKO 表单数据抓取

cd /d "%~dp0"

echo.
echo   ┌──────────────────────────────────────────┐
echo   │  AKO 阿格建筑 · 表单数据本地抓取工具       │
echo   │  下载到桌面\AKO_数据 文件夹                │
echo   └──────────────────────────────────────────┘
echo.

py download_data.py

pause