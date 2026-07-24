#!/usr/bin/env python3
"""
AKO 表单数据本地抓取工具
运行方式：双击 download_data.bat 或在终端执行 py download_data.py

功能：
  1. 从服务器下载 Contact 页面询盘 CSV
  2. 从服务器下载 Widget/XM 留资线索 CSV
  3. 保存到桌面 AKO_数据 文件夹
"""

import os
import sys
import urllib.request
import urllib.error
from datetime import datetime

# ====== 配置 ======
SERVER_BASE = "https://akobuild.cloud"
# 如果服务器未上线，可改为本地测试地址：
# SERVER_BASE = "http://localhost"

FILES = {
    "inquiries": {
        "url": f"{SERVER_BASE}/admin.php?download=inquiries",
        "label": "项目询盘（Contact 页面 12 字段表单）",
    },
    "leads": {
        "url": f"{SERVER_BASE}/admin.php?download=leads",
        "label": "留资线索（Widget 留资 + XM 线索登记表）",
    },
}

# 保存目录：桌面/AKO_数据/
SAVE_DIR = os.path.join(os.path.expanduser("~"), "Desktop", "AKO_数据")
os.makedirs(SAVE_DIR, exist_ok=True)


def download_file(key: str, info: dict) -> str | None:
    """下载单个 CSV，返回保存路径；失败返回 None。"""
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"AKO_{key}_{timestamp}.csv"
    filepath = os.path.join(SAVE_DIR, filename)

    print(f"  ⏳ 正在下载 {info['label']} ...")
    print(f"     URL: {info['url']}")

    try:
        req = urllib.request.Request(info["url"], headers={
            "User-Agent": "AKO-DataDownloader/1.0"
        })
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = resp.read()
        with open(filepath, "wb") as f:
            f.write(data)

        size_kb = len(data) / 1024
        print(f"  ✅ 已保存: {filepath}  ({size_kb:.1f} KB)")
        return filepath

    except urllib.error.HTTPError as e:
        print(f"  ❌ HTTP 错误 {e.code}: {e.reason}")
        return None
    except urllib.error.URLError as e:
        print(f"  ❌ 网络错误: {e.reason}")
        print(f"     请确认服务器 {SERVER_BASE} 可访问")
        return None
    except Exception as e:
        print(f"  ❌ 未知错误: {e}")
        return None


def main():
    print("=" * 56)
    print("  AKO 阿格建筑 · 表单数据本地抓取工具")
    print("=" * 56)
    print(f"  保存目录: {SAVE_DIR}")
    print(f"  服务器:   {SERVER_BASE}")
    print()

    success = 0
    fail = 0

    for key, info in FILES.items():
        result = download_file(key, info)
        if result:
            success += 1
        else:
            fail += 1
        print()

    print("-" * 56)
    print(f"  完成: {success} 个成功, {fail} 个失败")

    if success > 0:
        print(f"\n  打开文件夹: {SAVE_DIR}")
        # 自动打开文件夹
        try:
            os.startfile(SAVE_DIR)
        except Exception:
            pass

    print("\n  按任意键退出...")
    input()


if __name__ == "__main__":
    main()