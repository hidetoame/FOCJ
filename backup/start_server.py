#!/usr/bin/env python3
"""
FOCJ会員登録システム サーバー起動スクリプト
ポート8020でFastAPIサーバーを起動します
"""

import uvicorn
import os
import sys

# プロジェクトルートをPythonパスに追加
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

if __name__ == "__main__":
    print("🚀 FOCJ会員登録システム サーバーを起動します...")
    print("📍 ポート: 8020")
    print("🌐 アクセス: http://localhost:8020")
    print("📚 API ドキュメント: http://localhost:8020/docs")
    print("🔧 管理者画面: http://localhost:8020/admin")
    print("📝 会員登録フォーム: http://localhost:8020/registration-form")
    print("=" * 50)
    
    uvicorn.run(
        "app.main:app",
        host="0.0.0.0",
        port=8020,
        reload=True,
        log_level="info"
    ) 