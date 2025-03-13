<?php
// require 'vendor/autoload.php'; // 必要なら
require_once 'vendor/autoload.php';

// セッション設定を最初に行う
ini_set("session.save_path", "/tmp");

// その後セッションを開始
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('ACCOUNTS_SPREADSHEET_ID', '1nkJe1DGTiHNccsXWvGhTetvG000f2mmzSqoO7TQHsx8');
define('LOGIN_INFO_SPREADSHEET_ID', '1nkJe1DGTiHNccsXWvGhTetvG000f2mmzSqoO7TQHsx8');

use Google\Client;
use Google\Service\Sheets;

// Google認証情報の設定（service-account.jsonのパスを確認）
putenv('GOOGLE_APPLICATION_CREDENTIALS=service-account.json');

$client = new Google\Client();
$client->setApplicationName("Google Sheets API PHP");
$client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
// $client->setAuthConfig('service-account.json');
$client->setAuthConfig('/Users/r0017/Desktop/douga_kaiseki/app/service-account.json');
$client->setAccessType('offline');
$gc = new Google_Service_Sheets($client);
?>




