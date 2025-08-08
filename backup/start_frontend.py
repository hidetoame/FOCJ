#!/usr/bin/env python3
"""
FOCJ会員登録システム フロントエンドサーバー起動スクリプト
ポート8010で静的ファイルサーバーを起動します
"""

import uvicorn
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.responses import HTMLResponse
import os

# フロントエンド専用のFastAPIアプリケーション
app = FastAPI(
    title="FOCJ Frontend",
    description="Ferrari Owners' Club Japan Frontend Server",
    version="1.0.0"
)

# 静的ファイルの配信設定
app.mount("/assets", StaticFiles(directory="registration-form/assets"), name="assets")

# 一般ユーザー向けページ
@app.get("/", response_class=HTMLResponse)
async def read_root():
    """トップページ"""
    with open("registration-form/index.html", "r", encoding="utf-8") as f:
        return HTMLResponse(content=f.read())

@app.get("/registration-form", response_class=HTMLResponse)
async def registration_form():
    """会員登録フォーム"""
    with open("registration-form/registration-form.html", "r", encoding="utf-8") as f:
        return HTMLResponse(content=f.read())

@app.get("/registration-form-confirm.html", response_class=HTMLResponse)
async def registration_form_confirm():
    """会員登録確認ページ"""
    with open("static/registration-form-confirm.html", "r", encoding="utf-8") as f:
        return HTMLResponse(content=f.read())

@app.get("/registration-form-thanks.html", response_class=HTMLResponse)
async def registration_form_thanks():
    """会員登録完了ページ"""
    with open("static/registration-form-thanks.html", "r", encoding="utf-8") as f:
        return HTMLResponse(content=f.read())

if __name__ == "__main__":
    print("🎨 FOCJ会員登録システム フロントエンドサーバーを起動します...")
    print("📍 ポート: 8010")
    print("🌐 アクセス: http://localhost:8010")
    print("📝 会員登録フォーム: http://localhost:8010/registration-form")
    print("=" * 50)
    
    uvicorn.run(
        "start_frontend:app",
        host="0.0.0.0",
        port=8010,
        reload=True,
        log_level="info"
    ) 