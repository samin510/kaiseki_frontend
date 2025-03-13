<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Google Drive 設定
define('DRIVE_FOLDER_ID', '1N_Bxkx3_kyhnPVwFGOV6S1AygUCre_yH');

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
                try {
                    $fileMetadata = new Google_Service_Drive_DriveFile([
                        'name' => $file["name"],
                        'parents' => [DRIVE_FOLDER_ID]
                    ]);
                    $media = new Google_Http_MediaFileUpload(
                        $driveService,
                        $fileMetadata,
                        [
                            'data' => file_get_contents($file_path),
                            'mimeType' => 'video/mp4',
                            'uploadType' => 'multipart'
                        ]
                    );
                    $driveService->files->create($fileMetadata, $media);
                    
                    $file_uploaded = true;
                    $message = "アップロードが完了しました。動画解析画面に遷移しますか？";
                } catch (Exception $e) {
                    error_log("アップロード中にエラーが発生しました: " . $e->getMessage());
                    $message = "アップロード中にエラーが発生しました。";
                }
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
    <title>動画アップロード</title>
    <link rel="stylesheet" href="/static/style.css">
</head>
<body>
    <div class="container">
        <h1>動画アップロード</h1>
        <?php if (!empty($message)): ?>
            <p class="message"> <?= htmlspecialchars($message) ?> </p>
            <?php if ($file_uploaded): ?>
                <div class="options">
                    <button onclick="location.href='video_analysis.php'">はい</button>
                    <button onclick="location.href='video_upload.php'">いいえ</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <form action="video_upload.php" method="POST" enctype="multipart/form-data">
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
