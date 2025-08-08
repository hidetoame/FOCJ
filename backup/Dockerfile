FROM python:3.11-slim

WORKDIR /app

# システムパッケージのインストール
RUN apt-get update && apt-get install -y \
    gcc \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Pythonパッケージのインストール
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# アプリケーションファイルのコピー
COPY app ./app
COPY registration-form ./registration-form
COPY member-management ./member-management
COPY static ./static

# uploadsディレクトリの作成
RUN mkdir -p uploads

# ポート公開
EXPOSE 8000

# 起動スクリプト作成
RUN echo '#!/bin/bash\n\
echo "Waiting for PostgreSQL to be ready..."\n\
while ! pg_isready -h focj-db -p 5432 -U postgres; do\n\
  sleep 1\n\
done\n\
echo "PostgreSQL is ready!"\n\
exec uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload' > /app/start.sh && \
chmod +x /app/start.sh

# アプリケーション起動
CMD ["/app/start.sh"]