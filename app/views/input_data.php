<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$file_uploaded = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["video_file"])) {
    $file = $_FILES["video_file"];
    
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "ファイルアップロードに失敗しました。";
    } elseif ($file["size"] > 500 * 1024 * 1024) { // 500MB制限
        $message = "ファイルサイズが500MBを超えています。";
    } else {
        $allowed_extensions = ["mp4", "mov", "avi"];
        $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $message = "許可されていないファイル形式です。";
        } else {
            $upload_folder = "/tmp/uploads";
            if (!is_dir($upload_folder)) {
                mkdir($upload_folder, 0777, true);
            }
            $file_path = $upload_folder . "/" . basename($file["name"]);
            
            if (move_uploaded_file($file["tmp_name"], $file_path)) {
                $file_uploaded = true;
                $message = "アップロードが完了しました。動画解析画面に遷移しますか？";
            } else {
                $message = "アップロードに失敗しました。";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>入力データページ</title>
    <link rel="stylesheet" href="/static/style.css">
</head>
<body>
    <div class="container">
        <h1>入力データページ</h1>
        <?php if (!empty($message)): ?>
            <p class="message"> <?= htmlspecialchars($message) ?> </p>
            <?php if ($file_uploaded): ?>
                <div class="options">
                    <button onclick="location.href='video_analysis.php'">はい</button>
                    <button onclick="location.href='input_data.php'">いいえ</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <form action="input_data.php" method="POST" enctype="multipart/form-data">
            <label for="fileInput">動画ファイルをアップロードしてください（500MB以下）:</label>
            <input type="file" id="fileInput" name="video_file" required>
            <p id="error" style="color: red;"></p>
            <button type="submit">アップロード</button>
        </form>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.size > 500 * 1024 * 1024) {
                document.getElementById('error').textContent = "ファイルサイズが500MBを超えています";
                this.value = "";
            } else {
                document.getElementById('error').textContent = "";
            }
        });
    </script>
</body>
</html>
