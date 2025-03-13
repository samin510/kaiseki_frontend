<?php
// 設定ファイルを最初に読み込む（セッション開始より前に）
require_once __DIR__.'/config.php'; // 正しいパスに修正

// セッションチェック
if (isset($_SESSION["user"]) && !empty($_SESSION["user"])) {
    header("Location: /views/dashboard.php");
    exit();
} else {
    header("Location: /views/login.php");
    exit();
}
?>