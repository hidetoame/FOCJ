from fastapi import FastAPI, Request
from fastapi.responses import HTMLResponse
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
import uvicorn

app = FastAPI()

# 静的ファイルをマウント
app.mount("/assets", StaticFiles(directory="member-management/assets"), name="assets")

# テンプレートディレクトリを設定
templates = Jinja2Templates(directory="member-management")

@app.get("/", response_class=HTMLResponse)
async def admin_login():
    """管理者ログインページ"""
    with open("member-management/0_login.html", "r", encoding="utf-8") as f:
        template_html = f.read()
    return HTMLResponse(content=template_html)

@app.get("/login-error", response_class=HTMLResponse)
async def admin_login_error():
    """ログインエラーページ"""
    with open("member-management/0_login-error.html", "r", encoding="utf-8") as f:
        template_html = f.read()
    return HTMLResponse(content=template_html)

@app.get("/admin-index", response_class=HTMLResponse)
async def admin_index():
    """管理者インデックスページ"""
    with open("member-management/A1_admin-index.html", "r", encoding="utf-8") as f:
        template_html = f.read()
    template_html = template_html.replace('A2_registration-list.html', '/registration-list')
    template_html = template_html.replace('B1_edit-mail-index.html', '/edit-mail-index')
    template_html = template_html.replace('C1_members-list.html', '/members-list')
    template_html = template_html.replace('username01', 'admin')
    script_tag = '<script src="assets/js/admin.js"></script>'
    template_html = template_html.replace('</body>', f'{script_tag}\n</body>')
    return HTMLResponse(content=template_html)

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8015) 