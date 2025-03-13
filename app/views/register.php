<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// スプレッドシートIDの定義
define('ACCOUNTS_SPREADSHEET_ID', '1nkJe1DGTiHNccsXWvGhTetvG000f2mmzSqoO7TQHsx8');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"] ?? '';
    $email = $_POST["email"] ?? '';
    $password = $_POST["password"] ?? '';
    
    try {
        $sheetAccounts = $gc->openByKey(ACCOUNTS_SPREADSHEET_ID)->worksheet("アカウント");
        $records = $sheetAccounts->getAllRecords();
        
        foreach ($records as $record) {
            if ($record["メールアドレス"] === $email) {
                $error = "このメールアドレスは既に登録されています。";
                break;
            }
        }
        
        if (!isset($error)) {
            $sheetAccounts->appendRow([$name, $email, $password]);
            header("Location: login.php");
            exit;
        }
    } catch (Exception $e) {
        error_log("エラーが発生しました: " . $e->getMessage());
        $error = "エラーが発生しました。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員登録</title>
    <link rel="stylesheet" href="/app/static/style.css">
</head>
<body>
    <div class="container">
        <h1>会員登録</h1>
        <?php if (!empty($error)): ?>
            <p class="error"> <?= htmlspecialchars($error) ?> </p>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <input type="text" name="name" placeholder="氏名" required>
            <input type="email" name="email" placeholder="メールアドレス" required>
            <input type="password" name="password" placeholder="パスワード" required>
            <button type="submit">登録</button>
        </form>
    </div>
</body>
</html>
