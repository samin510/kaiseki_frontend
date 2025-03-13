<?php
// login.phpなどでも同様の条件付きセッション開始を行う
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user"])) {
    header("Location: /views/login.php");
    exit;
}
$name = $_SESSION["user"];

// データ分析用のスプレッドシートURL
define('DATA_ANALYSIS_SHEET_URL', 'https://docs.google.com/spreadsheets/d/1wY4auEBpm3lA_uQ9T37vb9tSOHnUdJhhtzzDkefD-Yk/edit?usp=sharing');
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード</title>
    <link rel="stylesheet" href="/static/style.css">
</head>
<body>
    <div class="container">
        <h1>こんにちは</h1>
        <div class="tabs">
        <a href="<?= DATA_ANALYSIS_SHEET_URL ?>" target="_blank" class="tab">
            <div class="tab-icon">
                <img src="/static/images/thinking.png" alt="データ分析">
            </div>
            <span class="tab-text">データ分析</span>
        </a>
        <a href="video_upload.php" class="tab">
            <div class="tab-icon">
                <img src="/static/images/book.png" alt="動画解析">
            </div>
            <span class="tab-text">動画解析</span>
        </a>
        <a href="structure_creation.php" class="tab">
            <div class="tab-icon">
                <img src="/static/images/puzzle.png" alt="構成案作成">
            </div>
            <span class="tab-text">構成案作成</span>
        </a>
        <div>
    </div>
</body>
</html>

