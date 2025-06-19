<?php
require 'vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('credentials.json');
$client->addScope(Google_Service_Drive::DRIVE_FILE);
$client->setRedirectUri('http://localhost/document_manager_project_final/drive_callback.php');

if (!isset($_GET['code'])) {
    // لم يتم الدخول بعد: إعادة التوجيه لصفحة Google
    header('Location: ' . $client->createAuthUrl());
    exit;
} else {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!is_array($token) || isset($token['error'])) {
            echo "❌ فشل في الحصول على التوكن:<br>";
            echo "<pre>" . htmlspecialchars(print_r($token, true)) . "</pre>";
            exit;
        }

        file_put_contents('token.json', json_encode($token));
        header("Location: dashboard.html");
        exit;
    } catch (Exception $e) {
        echo "❌ استثناء: " . $e->getMessage();
        exit;
    }
}
