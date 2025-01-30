from flask import Flask, render_template, request, jsonify, redirect, url_for
from datetime import datetime
import gspread
from google.oauth2.service_account import Credentials
import logging
import os
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload
from google.oauth2 import service_account

# Flask アプリケーションの初期化
app = Flask(__name__, static_folder='static', template_folder='templates')
# リクエストの最大サイズ (例: 50MB)
app.config['MAX_CONTENT_LENGTH'] = 500 * 1024 * 1024  # 50MB


# ログの設定
logging.basicConfig(level=logging.DEBUG, format="%(asctime)s - %(levelname)s - %(message)s")
logger = logging.getLogger(__name__)
file_handler = logging.FileHandler("error.log")
file_handler.setLevel(logging.ERROR)
formatter = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
file_handler.setFormatter(formatter)
app.logger.addHandler(file_handler)

# Google Drive 設定
SERVICE_ACCOUNT_FILE = os.getenv("GOOGLE_APPLICATION_CREDENTIALS", "service-account.json")
DRIVE_SCOPES = ['https://www.googleapis.com/auth/drive']
DRIVE_FOLDER_ID = "1N_Bxkx3_kyhnPVwFGOV6S1AygUCre_yH"

drive_credentials = service_account.Credentials.from_service_account_file(
    SERVICE_ACCOUNT_FILE, scopes=DRIVE_SCOPES
)
drive_service = build('drive', 'v3', credentials=drive_credentials)

# 許可されるファイル拡張子
ALLOWED_EXTENSIONS = {'mp4', 'mov', 'avi'}

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

# Google スプレッドシートの設定
SCOPES = ['https://www.googleapis.com/auth/spreadsheets']
credentials = Credentials.from_service_account_file(SERVICE_ACCOUNT_FILE, scopes=SCOPES)
gc = gspread.authorize(credentials)

# スプレッドシート ID
DATA_ANALYSIS_SHEET_URL = "https://docs.google.com/spreadsheets/d/1wY4auEBpm3lA_uQ9T37vb9tSOHnUdJhhtzzDkefD-Yk/edit?usp=sharing"
ACCOUNTS_SPREADSHEET_ID = '1nkJe1DGTiHNccsXWvGhTetvG000f2mmzSqoO7TQHsx8'
LOGIN_INFO_SPREADSHEET_ID = '1nkJe1DGTiHNccsXWvGhTetvG000f2mmzSqoO7TQHsx8'

@app.route('/')
def home():
    """ ホーム画面 """
    return redirect(url_for('login'))

@app.route('/login', methods=["POST", "GET"])
def login():
    """ ログイン画面 """
    if request.method == "POST":
        email = request.form.get("email")
        password = request.form.get("password")

        try:
            sheet = gc.open_by_key(ACCOUNTS_SPREADSHEET_ID).worksheet("アカウント")
            records = sheet.get_all_records()

            for record in records:
                if record["メールアドレス"] == email and record["パスワード"] == password:
                    name = record["氏名"]
                    login_sheet = gc.open_by_key(LOGIN_INFO_SPREADSHEET_ID).worksheet("ログイン情報")
                    login_sheet.append_row([name, email, datetime.now().strftime("%Y-%m-%d %H:%M:%S")])
                    return redirect(url_for("dashboard", name=name))
            return render_template("login.html", error="メールアドレスまたはパスワードが正しくありません。")
        except Exception as e:
            logger.error(f"エラーが発生しました: {e}")
            return render_template("login.html", error="エラーが発生しました。")
    return render_template("login.html")

@app.route('/register', methods=['GET', 'POST'])
def register():
    """ 登録画面 """
    if request.method == 'POST':
        name = request.form.get('name')
        email = request.form.get('email')
        password = request.form.get('password')

        try:
            sheet_accounts = gc.open_by_key(ACCOUNTS_SPREADSHEET_ID).worksheet('アカウント')
            records = sheet_accounts.get_all_records()

            for record in records:
                if record['メールアドレス'] == email:
                    return render_template('register.html', error="このメールアドレスは既に登録されています。")
            sheet_accounts.append_row([name, email, password])
            return redirect(url_for('login'))
        except Exception as e:
            logger.error(f"エラーが発生しました: {e}")
            return f"エラーが発生しました: {e}"
    return render_template('register.html')

@app.route('/dashboard')
def dashboard():
    """ ダッシュボード画面 """
    name = request.args.get("name", "ゲスト")
    return render_template('dashboard.html', name=name)

@app.route('/data_analysis', methods=["GET"])
def data_analysis():
    """ データ分析ページ """
    return redirect(DATA_ANALYSIS_SHEET_URL)

@app.route('/video_upload', methods=["GET"])
def video_upload():
    """ 動画解析ページ """
    return render_template("video_upload.html")

@app.route('/upload', methods=["POST"])
def upload():
    """ 動画ファイルをアップロード """
    if 'video_file' not in request.files:
        logger.error("アップロードエラー: ファイルが選択されていません")
        return render_template('input_data.html', message="ファイルが選択されていません")

    file = request.files['video_file']
    if file.filename == '':
        logger.error("アップロードエラー: ファイル名が無効です")
        return render_template('input_data.html', message="ファイル名が無効です")

    if not allowed_file(file.filename):
        logger.error(f"許可されていないファイル形式: {file.filename}")
        return render_template('input_data.html', message="許可されていないファイル形式です")

    upload_folder = "/tmp/uploads"
    os.makedirs(upload_folder, exist_ok=True)
    file_path = os.path.join(upload_folder, file.filename)
    file.save(file_path)

    try:
        file_metadata = {
            'name': file.filename,
            'parents': [DRIVE_FOLDER_ID]
        }
        media = MediaFileUpload(file_path, mimetype='video/mp4')
        drive_service.files().create(
            body=file_metadata,
            media_body=media,
            fields='id'
        ).execute()
        logger.info(f"ファイルが正常に保存されました: {file_path}")
        return render_template('input_data.html', message="アップロードが完了しました。動画解析画面に遷移しますか？", file_uploaded=True)
    except Exception as e:
        logger.error(f"アップロード中にエラーが発生しました: {e}")
        return render_template('input_data.html', message="アップロード中にエラーが発生しました", error=True)

@app.route('/input_data', methods=['GET', 'POST'])
def input_data():
    """ 入力データ画面 """
    return render_template('input_data.html')

@app.route('/video_analysis')
def video_analysis():
    """ 動画解析ページ """
    return render_template('video_analysis.html')

# Google Cloud Functions 用エントリポイント
def app_entry_point(request):
    try:
        with app.request_context(environ=request.environ):
            return app.full_dispatch_request()
    except Exception as e:
        logger.error(f"An unexpected error occurred: {e}")
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 8080))
    app.run(host="0.0.0.0", port=port, debug=True)
