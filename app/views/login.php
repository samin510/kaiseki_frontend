<?php
// config.phpがまだ読み込まれていない場合のみ読み込む
if (!defined('ACCOUNTS_SPREADSHEET_ID')) {
    require_once __DIR__.'/../config.php';
}


// ここではsession_start()を呼び出さない（config.phpで既に呼び出されている）

if (isset($_SESSION["user"]) && !empty($_SESSION["user"])) {
    header("Location:dashboard.php");
    exit();
}




if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? '';
    $password = $_POST["password"] ?? '';
    
    try {
        // $service ではなく $gc を使用する
        $spreadsheetId = ACCOUNTS_SPREADSHEET_ID;
        $range = "アカウント"; // ワークシート名
        
        // スプレッドシートからデータを取得
        $response = $gc->spreadsheets_values->get($spreadsheetId, $range);
        $records = $response->getValues();
        
        // ヘッダー行を取得（最初の行）
        $headers = array_shift($records);
        
        // 各行をヘッダーと対応付けて連想配列に変換
        $formattedRecords = [];
        foreach ($records as $row) {
            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = $row[$index] ?? '';
            }
            $formattedRecords[] = $record;
        }
        
        // 認証処理
        foreach ($formattedRecords as $record) {
            if ($record["メールアドレス"] === $email && $record["パスワード"] === $password) {
                $_SESSION["user"] = [
                    "name" => $record["氏名"],
                    "email" => $email
                ];
                
                // ログイン履歴を保存
                $loginSpreadsheetId = LOGIN_INFO_SPREADSHEET_ID;
                $loginRange = "ログイン情報";
                $loginValues = [
                    [$record["氏名"], $email, date("Y-m-d H:i:s")]
                ];
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => $loginValues
                ]);
                $params = [
                    'valueInputOption' => 'RAW'
                ];
                $gc->spreadsheets_values->append(
                    $loginSpreadsheetId, 
                    $loginRange, 
                    $body, 
                    $params
                );
                
                header("Location: dashboard.php");
                exit;
            }
        }
        $error = "メールアドレスまたはパスワードが正しくありません。";
    } catch (Exception $e) {
        error_log("エラーが発生しました: " . $e->getMessage());
        $error = "エラーが発生しました: " . $e->getMessage(); // デバッグ中はエラーメッセージを表示
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="/static/style.css">
</head>
<body>
    <div class="container">
        <h1>ログイン</h1>
        <?php if (!empty($error)): ?>
            <p class="error"> <?= htmlspecialchars($error) ?> </p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="メールアドレス" required>
            <input type="password" name="password" placeholder="パスワード" required>
            <button type="submit">ログイン</button>
        </form>
        <p>
            <a href="register.php">新しいアカウントを作成するにはこちら</a>
        </p>
    </div>
</body>
</html>