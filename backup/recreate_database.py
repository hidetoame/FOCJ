from app.database import engine
from app.models import Base
from sqlalchemy import text

def recreate_database():
    """データベースを再作成する"""
    print("データベースを再作成しています...")
    
    # データベース接続を取得
    with engine.connect() as connection:
        # 既存のテーブルをCASCADEで削除
        connection.execute(text("DROP SCHEMA public CASCADE;"))
        connection.execute(text("CREATE SCHEMA public;"))
        connection.execute(text("GRANT ALL ON SCHEMA public TO postgres;"))
        connection.execute(text("GRANT ALL ON SCHEMA public TO public;"))
        connection.commit()
    
    print("既存のテーブルを削除しました")
    
    # 新しいテーブルを作成
    Base.metadata.create_all(bind=engine)
    print("新しいテーブルを作成しました")
    
    print("データベースの再作成が完了しました！")

if __name__ == "__main__":
    recreate_database() 