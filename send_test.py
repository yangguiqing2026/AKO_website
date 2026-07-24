#!/usr/bin/env python3
"""
AKO 邮件测试 — 本地 Python 版
通过模拟表单提交触发服务器发送真实邮件。
前提：服务器 https://akobuild.cloud 已部署并可访问。
"""

import json
import urllib.request
import urllib.error

SERVER = "https://akobuild.cloud"
# 如果服务器未上线，可改为本地测试：
# SERVER = "http://localhost"


def post_test(url: str, label: str, payload: dict):
    """POST 一份测试数据到指定接口，触发邮件发送。"""
    body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=body,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    print(f"\n  📨 {label}")
    print(f"     URL: {url}")
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            result = json.loads(resp.read().decode("utf-8"))
        print(f"     ✅ 服务器响应: {json.dumps(result, ensure_ascii=False)}")
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace")
        print(f"     ❌ HTTP {e.code}: {body[:200]}")
    except urllib.error.URLError as e:
        print(f"     ❌ 网络错误: {e.reason}")
        print(f"        (请确认服务器 {SERVER} 可访问)")
    except Exception as e:
        print(f"     ❌ 错误: {e}")


def main():
    print("=" * 60)
    print("  AKO 邮件通知 — 模拟填单测试")
    print("=" * 60)
    print(f"  目标服务器: {SERVER}")
    print(f"  收件邮箱: contact@akobuild.cloud")
    print(f"           583748052@qq.com")
    print(f"           376972621@qq.com")
    print(f"           806853039@qq.com")
    print()

    # ── 测试 1: Contact 页面询盘 ──
    post_test(
        f"{SERVER}/api/submit.php",
        "Contact 页面 12 字段询盘",
        {
            "projectName": "测试项目-XX住宅",
            "projectLocation": "贵州省贵阳市",
            "buildingArea": "300",
            "buildingType": "residential",
            "panelType": "陶粒",
            "panelThickness": "120",
            "floors": "3",
            "contactName": "张三（测试）",
            "contactPhone": "13800138000",
            "estimatedCost": "50",
            "startDate": "2026-09-01",
            "remarks": "这是系统测试，请忽略。",
        },
    )

    # ── 测试 2: Widget 留资 ──
    post_test(
        f"{SERVER}/lead_api.php",
        "Widget 留资",
        {
            "name": "李四（测试）",
            "phone": "13900139000",
            "market": "城市更新",
            "message": "Widget 留资测试，请忽略。",
        },
    )

    # ── 测试 3: XM 线索登记表 ──
    post_test(
        f"{SERVER}/lead_submit.php",
        "XM 线索登记表",
        {
            "name": "王五（测试）",
            "phone": "13700137000",
            "market": "文旅民宿",
            "项目名称": "测试民宿项目",
            "线索编号": "AKO-XM-260720-TEST",
        },
    )

    print("\n" + "=" * 60)
    print("  测试完成。请检查上述 4 个邮箱的收件箱（含垃圾箱）。")
    print("=" * 60)
    input("\n  按回车退出...")


if __name__ == "__main__":
    main()